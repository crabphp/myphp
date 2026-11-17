/*
 * phpsdl's renderer: the toolkit's boxes, borders, gradients, shadows, text, icons
 * and images in one ordered list, drawn with as few draw calls as textures allow.
 *
 * Text is shaped by HarfBuzz (ligatures, marks, complex scripts), falls back through
 * a list of fonts per character, and is rasterized by HarfBuzz into an 8-bit atlas
 * shared with icons, which are white SVG masks tinted when drawn.
 *
 * The list draws with SDL_GPU (Vulkan, Metal, Direct3D 12), with shaders/ui.hlsl.
 * Frames, on screen or off, start and end here too, and canvas paths are drawn in the
 * same list (rasterized by raster.c when they are not plain boxes).
 */

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include <php.h>
#include <zend_exceptions.h>
#include <math.h>
#include <hb.h>
#include <hb-ot.h>
#include <hb-raster.h>

/* Icons: nanosvg parses them (its implementation lives here now that the SVG module is gone). */
#define NANOSVG_IMPLEMENTATION
#include "nanosvg.h"
#define NANOSVGRAST_IMPLEMENTATION
#include "nanosvgrast.h"
#include "stb_image.h"
#include "stb_image_write.h"
#include "shaders/ui_shaders.h"

#include "render.h"
#include "path.h"
#include "raster.h"

/* A texture: an SDL_GPUTexture. */
typedef uintptr_t pr_tex;

/* Fonts --------------------------------------------------------------------- */

#define MAX_FONTS 48
typedef struct {
    char name[64];
    hb_face_t *face;
    hb_font_t *font;
    float upem, ascender, descender, gap; /* in em */
    float weight;  /* 100 to 900 */
    int variable;  /* has a weight axis: fallbacks take the requested font's weight */
} pr_font;

static pr_font fonts[MAX_FONTS];
static int font_count = 0;
static int fallbacks[MAX_FONTS];
static int fallback_count = 0;
static hb_buffer_t *shape_buffer = NULL;

static int font_load(const char *name, const char *path, unsigned int index)
{
    if (font_count >= MAX_FONTS) return -1;
    hb_blob_t *blob = hb_blob_create_from_file_or_fail(path);
    if (!blob) return -1;
    hb_face_t *face = hb_face_create(blob, index);
    hb_blob_destroy(blob);
    if (!face || hb_face_get_glyph_count(face) == 0) { hb_face_destroy(face); return -1; }
    pr_font *f = &fonts[font_count];
    memset(f, 0, sizeof *f);
    strncpy(f->name, name, sizeof f->name - 1);
    f->face = face;
    f->font = hb_font_create(face);
    f->upem = (float) hb_face_get_upem(face);
    hb_font_extents_t ext;
    hb_font_set_scale(f->font, (int) f->upem, (int) f->upem);
    f->weight = hb_style_get_value(f->font, HB_STYLE_TAG_WEIGHT);
    if (f->weight <= 0) f->weight = 400;
    hb_ot_var_axis_info_t axis;
    f->variable = hb_ot_var_find_axis_info(face, HB_TAG('w', 'g', 'h', 't'), &axis);
    if (hb_font_get_h_extents(f->font, &ext)) {
        f->ascender = ext.ascender / f->upem;
        f->descender = ext.descender / f->upem;
        f->gap = ext.line_gap / f->upem;
    } else {
        f->ascender = 0.8f; f->descender = -0.2f;
    }
    return font_count++;
}

static int font_find(const char *name, size_t len)
{
    for (int i = 0; i < font_count; i++) {
        if (strlen(fonts[i].name) == len && memcmp(fonts[i].name, name, len) == 0) return i;
    }
    return -1;
}

/* Decode one UTF-8 character; returns its length (1 for invalid bytes). */
static int utf8_next(const unsigned char *s, size_t len, uint32_t *cp)
{
    unsigned char c = s[0];
    if (c < 0x80) { *cp = c; return 1; }
    int n = (c & 0xE0) == 0xC0 ? 2 : (c & 0xF0) == 0xE0 ? 3 : (c & 0xF8) == 0xF0 ? 4 : 1;
    if (n == 1 || (size_t) n > len) { *cp = 0xFFFD; return 1; }
    uint32_t v = c & (0xFF >> (n + 1));
    for (int i = 1; i < n; i++) {
        if ((s[i] & 0xC0) != 0x80) { *cp = 0xFFFD; return 1; }
        v = (v << 6) | (s[i] & 0x3F);
    }
    *cp = v;
    return n;
}

static int has_glyph(int f, uint32_t cp)
{
    hb_codepoint_t g;
    return hb_font_get_nominal_glyph(fonts[f].font, cp, &g);
}

/* The font for a character: the requested one, else the first fallback that has it. */
static int font_for(int primary, uint32_t cp)
{
    if (cp < 0x20 || has_glyph(primary, cp)) return primary;
    for (int i = 0; i < fallback_count; i++) if (has_glyph(fallbacks[i], cp)) return fallbacks[i];
    return primary;
}

static int is_mark(uint32_t cp)
{
    hb_unicode_general_category_t c = hb_unicode_general_category(hb_unicode_funcs_get_default(), cp);
    return c == HB_UNICODE_GENERAL_CATEGORY_NON_SPACING_MARK || c == HB_UNICODE_GENERAL_CATEGORY_SPACING_MARK
        || c == HB_UNICODE_GENERAL_CATEGORY_ENCLOSING_MARK || cp == 0x200D || (cp >= 0xFE00 && cp <= 0xFE0F);
}

/* A fallback with a weight axis is set to the weight it stands in for; 0 means the font's own. */
static void font_weight(int font, int weight)
{
    if (!fonts[font].variable) return;
    if (weight > 0) {
        hb_variation_t v = {HB_TAG('w', 'g', 'h', 't'), (float) weight};
        hb_font_set_variations(fonts[font].font, &v, 1);
    } else {
        hb_font_set_variations(fonts[font].font, NULL, 0);
    }
}

typedef struct {
    int font;
    int weight;
    hb_codepoint_t glyph;
    float x, y;        /* pen position of the glyph, in pixels at the shaped size */
    uint32_t cluster;  /* byte offset of its cluster */
} pr_glyph;

typedef struct {
    pr_glyph *glyphs;
    int count, cap;
    float width;
} pr_run;

static void run_push(pr_run *run, pr_glyph g)
{
    if (run->count == run->cap) {
        run->cap = run->cap ? run->cap * 2 : 64;
        run->glyphs = perealloc(run->glyphs, sizeof(pr_glyph) * run->cap, 1);
    }
    run->glyphs[run->count++] = g;
}

/*
 * Shape $text at $size pixels: split it into runs by font (marks stay with their
 * base), shape each run with HarfBuzz, and lay the glyphs out left to right.
 * Tracking is added after each cluster, as CSS letter-spacing.
 */
static void shape(const char *text, size_t len, int primary, float size, float tracking, pr_run *run)
{
    run->count = 0;
    run->width = 0;
    if (!shape_buffer) shape_buffer = hb_buffer_create();
    float pen = 0;
    size_t start = 0;
    while (start < len) {
        uint32_t cp;
        int n = utf8_next((const unsigned char *) text + start, len - start, &cp);
        int font = font_for(primary, cp);
        size_t end = start + n;
        while (end < len) {
            int m = utf8_next((const unsigned char *) text + end, len - end, &cp);
            int next = is_mark(cp) ? font : font_for(primary, cp);
            if (next != font) break;
            end += m;
        }
        pr_font *f = &fonts[font];
        int weight = font != primary && f->variable ? (int) fonts[primary].weight : 0;
        font_weight(font, weight);
        int scale = (int) lroundf(size * 64.0f);
        hb_font_set_scale(f->font, scale, scale);
        hb_buffer_clear_contents(shape_buffer);
        hb_buffer_add_utf8(shape_buffer, text, (int) len, (unsigned int) start, (int) (end - start));
        hb_buffer_guess_segment_properties(shape_buffer);
        hb_shape(f->font, shape_buffer, NULL, 0);
        unsigned int count;
        hb_glyph_info_t *info = hb_buffer_get_glyph_infos(shape_buffer, &count);
        hb_glyph_position_t *pos = hb_buffer_get_glyph_positions(shape_buffer, &count);
        int rtl = hb_buffer_get_direction(shape_buffer) == HB_DIRECTION_RTL;
        for (unsigned int i = 0; i < count; i++) {
            pr_glyph g = {font, weight, info[i].codepoint, pen + pos[i].x_offset / 64.0f, -pos[i].y_offset / 64.0f, info[i].cluster};
            run_push(run, g);
            pen += pos[i].x_advance / 64.0f;
            int last_of_cluster = rtl ? (i == 0 || info[i - 1].cluster != info[i].cluster)
                : (i + 1 == count || info[i + 1].cluster != info[i].cluster);
            if (last_of_cluster) pen += tracking;
        }
        start = end;
    }
    run->width = pen > 0 && tracking != 0 ? pen - tracking : pen;
}

static pr_run scratch = {0};

/* Glyph and icon atlas ---------------------------------------------------- */

#define ATLAS 2048
#define SLOTS 16384
typedef struct {
    uint64_t key;      /* 0: empty */
    int16_t x, y, w, h;
    int16_t left, top; /* offset of the bitmap from the pen, in device pixels (top: up from the baseline) */
} pr_slot;

static unsigned char *atlas = NULL;
static pr_tex atlas_tex = 0;
static int shelf_x = 0, shelf_y = 0, shelf_h = 0;
static int dirty_top = ATLAS, dirty_bottom = 0;
static pr_slot slots[SLOTS];
static int slot_used = 0;
static int atlas_generation = 0;
static hb_raster_draw_t *raster = NULL;

static void atlas_reset(void)
{
    if (!atlas) atlas = pecalloc(ATLAS * ATLAS, 1, 1);
    else memset(atlas, 0, ATLAS * ATLAS);
    memset(slots, 0, sizeof slots);
    slot_used = 0;
    shelf_x = shelf_y = shelf_h = 0;
    dirty_top = 0; dirty_bottom = ATLAS;
    atlas_generation++;
}

static pr_slot *slot_find(uint64_t key, int *found)
{
    uint64_t h = key * 0x9E3779B97F4A7C15ull;
    for (int i = 0; i < SLOTS; i++) {
        pr_slot *s = &slots[(h + i) & (SLOTS - 1)];
        if (s->key == key) { *found = 1; return s; }
        if (s->key == 0) { *found = 0; return s; }
    }
    *found = 0;
    return NULL;
}

/* Room for a w×h bitmap; 0 when the atlas is full. */
static int atlas_place(int w, int h, int *x, int *y)
{
    if (w > ATLAS || h > ATLAS) return 0;
    if (shelf_x + w + 1 > ATLAS) { shelf_y += shelf_h + 1; shelf_x = 0; shelf_h = 0; }
    if (shelf_y + h + 1 > ATLAS) return 0;
    *x = shelf_x; *y = shelf_y;
    shelf_x += w + 1;
    if (h > shelf_h) shelf_h = h;
    if (*y < dirty_top) dirty_top = *y;
    if (*y + h > dirty_bottom) dirty_bottom = *y + h;
    return 1;
}

/* Copy a bitmap into the atlas, flipping rows when $bottom_up. */
static void atlas_copy(int x, int y, int w, int h, const unsigned char *src, int stride, int step, int bottom_up)
{
    for (int r = 0; r < h; r++) {
        const unsigned char *row = src + (size_t) (bottom_up ? h - 1 - r : r) * stride;
        unsigned char *dst = atlas + (size_t) (y + r) * ATLAS + x;
        for (int c = 0; c < w; c++) dst[c] = row[c * step];
    }
}

/* A glyph's slot, rasterized on first use: $px is the size in device pixels, $sub the quarter-pixel offset. */
static pr_slot *glyph_slot(int font, int weight, hb_codepoint_t glyph, float px, int sub)
{
    if (!atlas) atlas_reset();
    int size_q = (int) lroundf(px * 4.0f);
    uint64_t key = ((uint64_t) 1 << 63) | ((uint64_t) ((weight / 50) & 0x1F) << 56) | ((uint64_t) (font & 0x3F) << 50)
        | ((uint64_t) (size_q & 0xFFFF) << 32) | ((uint64_t) sub << 30) | (glyph & 0x3FFFFFFF);
    int found;
    pr_slot *s = slot_find(key, &found);
    if (found) return s;
    if (!s || slot_used > SLOTS * 3 / 4) return NULL;

    if (!raster) raster = hb_raster_draw_create_or_fail();
    if (!raster) return NULL;
    pr_font *f = &fonts[font];
    font_weight(font, weight);
    int scale = (int) lroundf(size_q / 4.0f * 64.0f);
    hb_font_set_scale(f->font, scale, scale);
    hb_raster_draw_reset(raster);
    hb_raster_draw_set_scale_factor(raster, 64.0f, 64.0f);
    hb_raster_draw_set_transform(raster, 1.f, 0.f, 0.f, 1.f, sub * 16.0f, 0.f);
    hb_raster_draw_glyph(raster, f->font, glyph);
    hb_raster_image_t *img = hb_raster_draw_render(raster);
    s->key = key;
    slot_used++;
    if (!img) { s->w = s->h = 0; return s; }
    hb_raster_extents_t ext;
    hb_raster_image_get_extents(img, &ext);
    int ax, ay;
    if (ext.width == 0 || ext.height == 0) {
        s->w = s->h = 0;
    } else if (atlas_place((int) ext.width, (int) ext.height, &ax, &ay)) {
        atlas_copy(ax, ay, (int) ext.width, (int) ext.height, hb_raster_image_get_buffer(img), (int) ext.stride, 1, 1);
        s->x = ax; s->y = ay; s->w = ext.width; s->h = ext.height;
        s->left = ext.x_origin; s->top = ext.y_origin + (int) ext.height;
    } else {
        s->key = 0; slot_used--;
        hb_raster_draw_recycle_image(raster, img);
        return NULL;
    }
    hb_raster_draw_recycle_image(raster, img);
    return s;
}

/* An icon's slot: the SVG rasterized white at $px device pixels, kept as coverage. */
static pr_slot *icon_slot(zend_string *svg, int px)
{
    if (!atlas) atlas_reset();
    uint64_t key = ((uint64_t) ZSTR_HASH(svg) & 0x3FFFFFFFFFFull) << 16 | (uint64_t) (px & 0xFFFF);
    key &= ~((uint64_t) 1 << 63);
    if (key == 0) key = 1;
    int found;
    pr_slot *s = slot_find(key, &found);
    if (found) return s;
    if (!s || slot_used > SLOTS * 3 / 4 || px <= 0 || px > 1024) return NULL;
    char *copy = estrndup(ZSTR_VAL(svg), ZSTR_LEN(svg));
    NSVGimage *image = nsvgParse(copy, "px", 96.0f);
    efree(copy);
    if (!image) return NULL;
    NSVGrasterizer *r = nsvgCreateRasterizer();
    unsigned char *rgba = ecalloc((size_t) px * px, 4);
    float scale = image->width > 0 ? px / image->width : 1;
    nsvgRasterize(r, image, 0, 0, scale, rgba, px, px, px * 4);
    nsvgDeleteRasterizer(r);
    nsvgDelete(image);
    int ax, ay;
    if (!atlas_place(px, px, &ax, &ay)) { efree(rgba); return NULL; }
    atlas_copy(ax, ay, px, px, rgba + 3, px * 4, 4, 0);
    efree(rgba);
    s->key = key;
    slot_used++;
    s->x = ax; s->y = ay; s->w = px; s->h = px; s->left = 0; s->top = 0;
    return s;
}

/* The GPU device ---------------------------------------------------------- */

static SDL_GPUDevice *device = NULL;
static SDL_GPUShader *gpu_vs = NULL, *gpu_fs = NULL;
static SDL_GPUSampler *gpu_sampler = NULL;
static struct { SDL_GPUTextureFormat format; SDL_GPUGraphicsPipeline *pipeline; } gpu_pipelines[4];
static int gpu_pipeline_count = 0;
static SDL_GPUBuffer *gpu_instances = NULL;
static SDL_GPUTransferBuffer *gpu_instances_upload = NULL, *gpu_atlas_upload = NULL;
static Uint32 gpu_instances_size = 0;

/* The frame being drawn: into a window's swapchain texture, or off screen. */
static struct {
    SDL_GPUCommandBuffer *cmd;
    SDL_GPUTexture *target;
    SDL_GPUTexture *offscreen;
    SDL_GPUTextureFormat format;
    int width, height;          /* asked for */
    Uint32 target_w, target_h;  /* what the target is */
    bool clear;
    SDL_FColor color;
} frame;

const char *phpsdl_backend(void)
{
    return device ? SDL_GetGPUDeviceDriver(device) : "none";
}

bool phpsdl_gpu_claim(SDL_Window *window)
{
    if (!device) {
        const char *debug = getenv("MYPHP_GPU_DEBUG");
        device = SDL_CreateGPUDevice(SDL_GPU_SHADERFORMAT_SPIRV | SDL_GPU_SHADERFORMAT_DXIL | SDL_GPU_SHADERFORMAT_MSL,
            debug && *debug && strcmp(debug, "0") != 0, NULL);
        if (!device) return false;
    }
    return SDL_ClaimWindowForGPUDevice(device, window);
}

void phpsdl_gpu_release(SDL_Window *window)
{
    if (!device) return;
    if (frame.cmd && !frame.offscreen) { SDL_SubmitGPUCommandBuffer(frame.cmd); memset(&frame, 0, sizeof frame); }
    SDL_WaitForGPUIdle(device);
    SDL_ReleaseWindowFromGPUDevice(device, window);
}

void phpsdl_gpu_vsync(SDL_Window *window, bool on)
{
    if (!device) return;
    SDL_GPUPresentMode mode = SDL_GPU_PRESENTMODE_VSYNC;
    if (!on) {
        if (SDL_WindowSupportsGPUPresentMode(device, window, SDL_GPU_PRESENTMODE_IMMEDIATE)) mode = SDL_GPU_PRESENTMODE_IMMEDIATE;
        else if (SDL_WindowSupportsGPUPresentMode(device, window, SDL_GPU_PRESENTMODE_MAILBOX)) mode = SDL_GPU_PRESENTMODE_MAILBOX;
    }
    SDL_SetGPUSwapchainParameters(device, window, SDL_GPU_SWAPCHAINCOMPOSITION_SDR, mode);
}

static SDL_GPUShader *gpu_shader(SDL_GPUShaderStage stage)
{
    SDL_GPUShaderFormat formats = SDL_GetGPUShaderFormats(device);
    bool vs = stage == SDL_GPU_SHADERSTAGE_VERTEX;
    SDL_GPUShaderCreateInfo info = {0};
    info.stage = stage;
    info.num_uniform_buffers = 1;
    info.num_samplers = vs ? 0 : 1;
    if (formats & SDL_GPU_SHADERFORMAT_SPIRV) {
        info.format = SDL_GPU_SHADERFORMAT_SPIRV;
        info.code = vs ? ui_vs_spv : ui_ps_spv;
        info.code_size = vs ? sizeof ui_vs_spv : sizeof ui_ps_spv;
        info.entrypoint = vs ? "VSMain" : "PSMain";
    } else if (formats & SDL_GPU_SHADERFORMAT_DXIL) {
        info.format = SDL_GPU_SHADERFORMAT_DXIL;
        info.code = vs ? ui_vs_dxil : ui_ps_dxil;
        info.code_size = vs ? sizeof ui_vs_dxil : sizeof ui_ps_dxil;
        info.entrypoint = vs ? "VSMain" : "PSMain";
    } else if (formats & SDL_GPU_SHADERFORMAT_MSL) {
        info.format = SDL_GPU_SHADERFORMAT_MSL;
        info.code = vs ? ui_vs_msl : ui_ps_msl;
        info.code_size = (vs ? sizeof ui_vs_msl : sizeof ui_ps_msl) - 1;
        info.entrypoint = "main0";
    } else {
        return NULL;
    }
    return SDL_CreateGPUShader(device, &info);
}

/* Shaders, the sampler and the atlas, made once there is a device. */
static bool gpu_init(void)
{
    if (gpu_sampler) return true;
    if (!device) return false;
    gpu_vs = gpu_shader(SDL_GPU_SHADERSTAGE_VERTEX);
    gpu_fs = gpu_shader(SDL_GPU_SHADERSTAGE_FRAGMENT);
    if (!gpu_vs || !gpu_fs) { zend_throw_error(NULL, "Renderer shaders: %s", SDL_GetError()); return false; }
    SDL_GPUSamplerCreateInfo s = {0};
    s.min_filter = SDL_GPU_FILTER_LINEAR;
    s.mag_filter = SDL_GPU_FILTER_LINEAR;
    s.mipmap_mode = SDL_GPU_SAMPLERMIPMAPMODE_LINEAR;
    s.address_mode_u = s.address_mode_v = s.address_mode_w = SDL_GPU_SAMPLERADDRESSMODE_CLAMP_TO_EDGE;
    s.max_lod = 1000;
    gpu_sampler = SDL_CreateGPUSampler(device, &s);
    SDL_GPUTextureCreateInfo t = {0};
    t.type = SDL_GPU_TEXTURETYPE_2D;
    t.format = SDL_GPU_TEXTUREFORMAT_R8_UNORM;
    t.usage = SDL_GPU_TEXTUREUSAGE_SAMPLER;
    t.width = t.height = ATLAS;
    t.layer_count_or_depth = 1;
    t.num_levels = 1;
    atlas_tex = (pr_tex) SDL_CreateGPUTexture(device, &t);
    SDL_GPUTransferBufferCreateInfo b = {SDL_GPU_TRANSFERBUFFERUSAGE_UPLOAD, ATLAS * ATLAS, 0};
    gpu_atlas_upload = SDL_CreateGPUTransferBuffer(device, &b);
    if (!gpu_sampler || !atlas_tex || !gpu_atlas_upload) { zend_throw_error(NULL, "Renderer: %s", SDL_GetError()); return false; }
    if (!atlas) atlas_reset();
    dirty_top = 0; dirty_bottom = ATLAS;
    return true;
}

static SDL_GPUGraphicsPipeline *gpu_pipeline(SDL_GPUTextureFormat format)
{
    for (int i = 0; i < gpu_pipeline_count; i++) if (gpu_pipelines[i].format == format) return gpu_pipelines[i].pipeline;
    if (gpu_pipeline_count == 4) return NULL;
    /* One instance per quad: eight float4 attributes, 128 bytes, a 4-vertex strip each. */
    SDL_GPUVertexBufferDescription buffer = {0, 128, SDL_GPU_VERTEXINPUTRATE_INSTANCE, 0};
    SDL_GPUVertexAttribute attributes[8];
    for (Uint32 i = 0; i < 8; i++) {
        attributes[i] = (SDL_GPUVertexAttribute) {i, 0, SDL_GPU_VERTEXELEMENTFORMAT_FLOAT4, i * 16};
    }
    SDL_GPUColorTargetDescription target = {0};
    target.format = format;
    target.blend_state.enable_blend = true; /* premultiplied */
    target.blend_state.src_color_blendfactor = SDL_GPU_BLENDFACTOR_ONE;
    target.blend_state.dst_color_blendfactor = SDL_GPU_BLENDFACTOR_ONE_MINUS_SRC_ALPHA;
    target.blend_state.color_blend_op = SDL_GPU_BLENDOP_ADD;
    target.blend_state.src_alpha_blendfactor = SDL_GPU_BLENDFACTOR_ONE;
    target.blend_state.dst_alpha_blendfactor = SDL_GPU_BLENDFACTOR_ONE_MINUS_SRC_ALPHA;
    target.blend_state.alpha_blend_op = SDL_GPU_BLENDOP_ADD;
    SDL_GPUGraphicsPipelineCreateInfo info = {0};
    info.vertex_shader = gpu_vs;
    info.fragment_shader = gpu_fs;
    info.vertex_input_state.vertex_buffer_descriptions = &buffer;
    info.vertex_input_state.num_vertex_buffers = 1;
    info.vertex_input_state.vertex_attributes = attributes;
    info.vertex_input_state.num_vertex_attributes = 8;
    info.primitive_type = SDL_GPU_PRIMITIVETYPE_TRIANGLESTRIP;
    info.rasterizer_state.fill_mode = SDL_GPU_FILLMODE_FILL;
    info.rasterizer_state.cull_mode = SDL_GPU_CULLMODE_NONE;
    info.target_info.color_target_descriptions = &target;
    info.target_info.num_color_targets = 1;
    SDL_GPUGraphicsPipeline *pipeline = SDL_CreateGPUGraphicsPipeline(device, &info);
    if (!pipeline) { zend_throw_error(NULL, "Renderer pipeline: %s", SDL_GetError()); return NULL; }
    gpu_pipelines[gpu_pipeline_count].format = format;
    gpu_pipelines[gpu_pipeline_count++].pipeline = pipeline;
    return pipeline;
}

/* A texture uploaded on a command buffer of its own: RGBA ($bpp 4) or coverage ($bpp 1),
 * with mipmaps when $mips. */
static SDL_GPUTexture *gpu_texture(int w, int h, int bpp, bool mips, const unsigned char *pixels)
{
    Uint32 levels = 1;
    if (mips) for (int s = w > h ? w : h; s > 1; s >>= 1) levels++;
    SDL_GPUTextureCreateInfo t = {0};
    t.type = SDL_GPU_TEXTURETYPE_2D;
    t.format = bpp == 4 ? SDL_GPU_TEXTUREFORMAT_R8G8B8A8_UNORM : SDL_GPU_TEXTUREFORMAT_R8_UNORM;
    t.usage = SDL_GPU_TEXTUREUSAGE_SAMPLER | (levels > 1 ? SDL_GPU_TEXTUREUSAGE_COLOR_TARGET : 0);
    t.width = (Uint32) w;
    t.height = (Uint32) h;
    t.layer_count_or_depth = 1;
    t.num_levels = levels;
    SDL_GPUTexture *texture = SDL_CreateGPUTexture(device, &t);
    if (!texture) return NULL;
    SDL_GPUTransferBufferCreateInfo b = {SDL_GPU_TRANSFERBUFFERUSAGE_UPLOAD, (Uint32) w * (Uint32) h * (Uint32) bpp, 0};
    SDL_GPUTransferBuffer *upload = SDL_CreateGPUTransferBuffer(device, &b);
    void *map = upload ? SDL_MapGPUTransferBuffer(device, upload, false) : NULL;
    if (!map) {
        if (upload) SDL_ReleaseGPUTransferBuffer(device, upload);
        SDL_ReleaseGPUTexture(device, texture);
        return NULL;
    }
    memcpy(map, pixels, b.size);
    SDL_UnmapGPUTransferBuffer(device, upload);
    SDL_GPUCommandBuffer *cmd = SDL_AcquireGPUCommandBuffer(device);
    SDL_GPUCopyPass *copy = SDL_BeginGPUCopyPass(cmd);
    SDL_GPUTextureTransferInfo src = {upload, 0, 0, 0};
    SDL_GPUTextureRegion dst = {0};
    dst.texture = texture; dst.w = (Uint32) w; dst.h = (Uint32) h; dst.d = 1;
    SDL_UploadToGPUTexture(copy, &src, &dst, false);
    SDL_EndGPUCopyPass(copy);
    if (levels > 1) SDL_GenerateMipmapsForGPUTexture(cmd, texture);
    SDL_SubmitGPUCommandBuffer(cmd);
    SDL_ReleaseGPUTransferBuffer(device, upload);
    return texture;
}

/* A render pass into the frame, cleared first if nothing was drawn yet. */
static SDL_GPURenderPass *gpu_begin_pass(void)
{
    if (!frame.cmd || !frame.target) return NULL;
    SDL_GPUColorTargetInfo target = {0};
    target.texture = frame.target;
    target.clear_color = frame.color;
    target.load_op = frame.clear ? SDL_GPU_LOADOP_CLEAR : SDL_GPU_LOADOP_LOAD;
    target.store_op = SDL_GPU_STOREOP_STORE;
    frame.clear = false;
    return SDL_BeginGPURenderPass(frame.cmd, &target, 1, NULL);
}

/* Images ------------------------------------------------------------------ */

typedef struct {
    zend_string *path;
    pr_tex tex;
    int w, h;
} pr_image;

#define MAX_IMAGES 256
static pr_image images[MAX_IMAGES];
static int image_count = 0;

static pr_image *image_load(zend_string *path)
{
    for (int i = 0; i < image_count; i++) if (zend_string_equals(images[i].path, path)) return &images[i];
    if (image_count >= MAX_IMAGES) return NULL;
    int w, h, n;
    unsigned char *px = stbi_load(ZSTR_VAL(path), &w, &h, &n, 4);
    if (!px) return NULL;
    pr_image *img = &images[image_count++];
    img->path = zend_string_init(ZSTR_VAL(path), ZSTR_LEN(path), 1);
    img->w = w; img->h = h;
    img->tex = (pr_tex) gpu_texture(w, h, 4, true, px);
    stbi_image_free(px);
    return img;
}

/* The draw list --------------------------------------------------------------- */

/* Floats per instance: rect, radii, color top, color bottom (or uv), border (or box), clip, params, kind. */
#define STRIDE 32
enum { KIND_BOX = 0, KIND_MASK = 1, KIND_IMAGE = 2 };

typedef struct { pr_tex tex; int first, count; } pr_command;

typedef struct {
    float *data;
    int count, cap;
    pr_command *commands;
    int command_count, command_cap;
    float width, height, ratio;
} pr_renderer;

static int renderer_init(pr_renderer *r)
{
    (void) r;
    return gpu_init();
}

static float *instance(pr_renderer *r, pr_tex tex)
{
    if (r->count == r->cap) {
        r->cap = r->cap ? r->cap * 2 : 1024;
        r->data = erealloc(r->data, sizeof(float) * STRIDE * r->cap);
    }
    /* Boxes need no texture: they join whatever command is open. */
    pr_command *last = r->command_count ? &r->commands[r->command_count - 1] : NULL;
    if (!last || (tex && last->tex && last->tex != tex)) {
        if (r->command_count == r->command_cap) {
            r->command_cap = r->command_cap ? r->command_cap * 2 : 64;
            r->commands = erealloc(r->commands, sizeof(pr_command) * r->command_cap);
        }
        last = &r->commands[r->command_count++];
        last->tex = tex; last->first = r->count; last->count = 0;
    } else if (tex && !last->tex) {
        last->tex = tex;
    }
    last->count++;
    float *p = r->data + (size_t) STRIDE * r->count++;
    memset(p, 0, sizeof(float) * STRIDE);
    return p;
}

static void put4(float *p, const float v[4]) { memcpy(p, v, sizeof(float) * 4); }

static const float NO_CLIP[4] = {-1e6f, -1e6f, 2e6f, 2e6f};

static void renderer_flush_gpu(pr_renderer *r);

static void renderer_flush(pr_renderer *r)
{
    if (r->count == 0) { r->command_count = 0; return; }
    renderer_flush_gpu(r);
}

/* The list on SDL_GPU: instances and new atlas rows go up in a copy pass, then one
 * render pass draws them, one call per texture run. */
static void renderer_flush_gpu(pr_renderer *r)
{
    SDL_GPUGraphicsPipeline *pipeline = NULL;
    if (!frame.cmd || !frame.target || !gpu_init() || !(pipeline = gpu_pipeline(frame.format))) {
        r->count = r->command_count = 0;
        return;
    }
    Uint32 bytes = (Uint32) (sizeof(float) * STRIDE * r->count);
    if (bytes > gpu_instances_size) {
        Uint32 size = gpu_instances_size ? gpu_instances_size : 128 * 1024;
        while (size < bytes) size *= 2;
        if (gpu_instances) SDL_ReleaseGPUBuffer(device, gpu_instances);
        if (gpu_instances_upload) SDL_ReleaseGPUTransferBuffer(device, gpu_instances_upload);
        SDL_GPUBufferCreateInfo b = {SDL_GPU_BUFFERUSAGE_VERTEX, size, 0};
        SDL_GPUTransferBufferCreateInfo t = {SDL_GPU_TRANSFERBUFFERUSAGE_UPLOAD, size, 0};
        gpu_instances = SDL_CreateGPUBuffer(device, &b);
        gpu_instances_upload = SDL_CreateGPUTransferBuffer(device, &t);
        gpu_instances_size = gpu_instances && gpu_instances_upload ? size : 0;
        if (!gpu_instances_size) { r->count = r->command_count = 0; return; }
    }

    SDL_GPUCopyPass *copy = SDL_BeginGPUCopyPass(frame.cmd);
    void *map = SDL_MapGPUTransferBuffer(device, gpu_instances_upload, true);
    if (map) {
        memcpy(map, r->data, bytes);
        SDL_UnmapGPUTransferBuffer(device, gpu_instances_upload);
        SDL_GPUTransferBufferLocation src = {gpu_instances_upload, 0};
        SDL_GPUBufferRegion dst = {gpu_instances, 0, bytes};
        SDL_UploadToGPUBuffer(copy, &src, &dst, true);
    }
    if (dirty_bottom > dirty_top && (map = SDL_MapGPUTransferBuffer(device, gpu_atlas_upload, true))) {
        Uint32 rows = (Uint32) (dirty_bottom - dirty_top);
        memcpy(map, atlas + (size_t) dirty_top * ATLAS, (size_t) rows * ATLAS);
        SDL_UnmapGPUTransferBuffer(device, gpu_atlas_upload);
        SDL_GPUTextureTransferInfo src = {gpu_atlas_upload, 0, ATLAS, rows};
        SDL_GPUTextureRegion dst = {0};
        dst.texture = (SDL_GPUTexture *) atlas_tex;
        dst.y = (Uint32) dirty_top; dst.w = ATLAS; dst.h = rows; dst.d = 1;
        /* Not cycled: the rows above, drawn from already, stay. */
        SDL_UploadToGPUTexture(copy, &src, &dst, false);
        dirty_top = ATLAS; dirty_bottom = 0;
    }
    SDL_EndGPUCopyPass(copy);

    float view[4] = {r->width, r->height, 0, 0};
    float scale[4] = {r->ratio, 0, 0, 0};
    SDL_PushGPUVertexUniformData(frame.cmd, 0, view, sizeof view);
    SDL_PushGPUFragmentUniformData(frame.cmd, 0, scale, sizeof scale);
    SDL_GPURenderPass *pass = gpu_begin_pass();
    if (!pass) { r->count = r->command_count = 0; return; }
    SDL_GPUViewport viewport = {0, 0, r->width * r->ratio, r->height * r->ratio, 0, 1};
    SDL_SetGPUViewport(pass, &viewport);
    SDL_BindGPUGraphicsPipeline(pass, pipeline);
    SDL_GPUBufferBinding binding = {gpu_instances, 0};
    SDL_BindGPUVertexBuffers(pass, 0, &binding, 1);
    for (int c = 0; c < r->command_count; c++) {
        pr_command *cmd = &r->commands[c];
        SDL_GPUTextureSamplerBinding sampler = {(SDL_GPUTexture *) (cmd->tex ? cmd->tex : atlas_tex), gpu_sampler};
        SDL_BindGPUFragmentSamplers(pass, 0, &sampler, 1);
        SDL_DrawGPUPrimitives(pass, 4, (Uint32) cmd->count, 0, (Uint32) cmd->first);
    }
    SDL_EndGPURenderPass(pass);
    r->count = 0;
    r->command_count = 0;
}

/* Text drawn into the list: glyph quads from the atlas, in logical units. */
static void renderer_text(pr_renderer *r, const char *text, size_t len, int font, float size, float x, float baseline, const float color[4], float tracking, const float clip[4])
{
    /* Glyphs name the atlas texture, which must exist before they are queued. */
    if (!renderer_init(r)) return;
    float ratio = r->ratio > 0 ? r->ratio : 1;
    float px = size * ratio;
    shape(text, len, font, px, tracking * ratio, &scratch);
    float ox = x * ratio, oy = baseline * ratio;
    int generation = atlas_generation;
    for (int i = 0; i < scratch.count; i++) {
        pr_glyph *g = &scratch.glyphs[i];
        float gx = ox + g->x;
        float fx = floorf(gx);
        int sub = (int) floorf((gx - fx) * 4.0f) & 3;
        pr_slot *s = glyph_slot(g->font, g->weight, g->glyph, px, sub);
        if (!s) {
            /* Full: draw what is queued, start the atlas again, and retry. */
            renderer_flush(r);
            atlas_reset();
            generation = atlas_generation;
            s = glyph_slot(g->font, g->weight, g->glyph, px, sub);
            if (!s) continue;
        }
        (void) generation;
        if (s->w == 0) continue;
        float left = (fx + s->left) / ratio;
        float top = (roundf(oy + g->y) - s->top) / ratio;
        float *p = instance(r, atlas_tex);
        float rect[4] = {left, top, s->w / ratio, s->h / ratio};
        float uv[4] = {(float) s->x / ATLAS, (float) s->y / ATLAS, (float) (s->x + s->w) / ATLAS, (float) (s->y + s->h) / ATLAS};
        put4(p, rect);
        put4(p + 8, color);
        put4(p + 12, uv);
        put4(p + 20, clip);
        p[28] = KIND_MASK;
    }
}

/* Canvas paths ----------------------------------------------------------------
 * PhpUi\Canvas draws paths here. A lone rectangle, rounded rectangle or circle in a
 * plain color under an axis-aligned transform is a box (exact, no rasterizing);
 * anything else is rasterized on the CPU (coverage, clip, paint, shadow) into a
 * texture kept while it is drawn, so an unchanged canvas costs nothing. */

typedef struct {
    uint64_t key;
    SDL_GPUTexture *tex;
    int x, y, w, h;   /* device pixels */
    bool rgba;        /* premultiplied color; else coverage for a color */
    uint64_t used;    /* frame it was last drawn in */
} pr_mask_entry;

#define MAX_MASKS 512
static pr_mask_entry masks[MAX_MASKS];
static int mask_count = 0;
static uint64_t frame_number = 0;

static pr_mask_entry *mask_find(uint64_t key)
{
    for (int i = 0; i < mask_count; i++) if (masks[i].key == key) { masks[i].used = frame_number; return &masks[i]; }
    return NULL;
}

static void mask_release(int i)
{
    if (masks[i].tex && device) SDL_ReleaseGPUTexture(device, masks[i].tex);
    masks[i] = masks[--mask_count];
}

/* Textures not drawn for a while go; SDL keeps them until frames using them finish. */
static void masks_evict(void)
{
    for (int i = mask_count - 1; i >= 0; i--) if (masks[i].used + 120 < frame_number) mask_release(i);
}

static pr_mask_entry *mask_store(uint64_t key, const phpsdl_mask *m, const unsigned char *pixels, bool rgba)
{
    if (mask_count == MAX_MASKS) {
        int oldest = 0;
        for (int i = 1; i < mask_count; i++) if (masks[i].used < masks[oldest].used) oldest = i;
        mask_release(oldest);
    }
    SDL_GPUTexture *tex = gpu_texture(m->w, m->h, rgba ? 4 : 1, false, pixels);
    if (!tex) return NULL;
    pr_mask_entry *e = &masks[mask_count++];
    *e = (pr_mask_entry) {key, tex, m->x, m->y, m->w, m->h, rgba, frame_number};
    return e;
}

/* Images for patterns: their pixels, kept on the CPU. */
typedef struct { zend_string *path; unsigned char *px; int w, h; } pr_pixels;
#define MAX_PIXELS 64
static pr_pixels pixels[MAX_PIXELS];
static int pixels_count = 0;

static pr_pixels *pixels_load(zend_string *path)
{
    for (int i = 0; i < pixels_count; i++) if (zend_string_equals(pixels[i].path, path)) return &pixels[i];
    if (pixels_count == MAX_PIXELS) {
        free(pixels[0].px);
        zend_string_release(pixels[0].path);
        memmove(pixels, pixels + 1, sizeof(pr_pixels) * (MAX_PIXELS - 1));
        pixels_count--;
    }
    int w, h, n;
    unsigned char *loaded = stbi_load(ZSTR_VAL(path), &w, &h, &n, 4);
    if (!loaded) return NULL;
    /* stb_image allocates per request (emalloc); the cache outlives requests. */
    unsigned char *px = malloc((size_t) w * (size_t) h * 4);
    if (px) memcpy(px, loaded, (size_t) w * (size_t) h * 4);
    stbi_image_free(loaded);
    if (!px) return NULL;
    pr_pixels *p = &pixels[pixels_count++];
    *p = (pr_pixels) {zend_string_init(ZSTR_VAL(path), ZSTR_LEN(path), 1), px, w, h};
    return p;
}

static bool invert(const float m[6], float out[6])
{
    float det = m[0] * m[3] - m[1] * m[2];
    if (fabsf(det) < 1e-12f) return false;
    float id = 1.0f / det;
    out[0] = m[3] * id; out[1] = -m[1] * id; out[2] = -m[2] * id; out[3] = m[0] * id;
    out[4] = -(out[0] * m[4] + out[2] * m[5]);
    out[5] = -(out[1] * m[4] + out[3] * m[5]);
    return true;
}

static int read_floats(HashTable *ht, float *out, int n)
{
    int i = 0;
    zval *v;
    if (!ht) return 0;
    ZEND_HASH_FOREACH_VAL(ht, v) {
        if (i >= n) break;
        out[i++] = (float) zval_get_double(v);
    } ZEND_HASH_FOREACH_END();
    return i;
}

static zval *array_at(HashTable *ht, zend_ulong i) { return ht ? zend_hash_index_find(ht, i) : NULL; }

/* Stops: [[offset, [r, g, b, a]], ...] in the order added; canvas keeps them sorted. */
static void parse_stops(zval *zstops, phpsdl_paint *p, uint64_t *hash)
{
    float offsets[64], colors[64][4];
    int n = 0;
    if (zstops && Z_TYPE_P(zstops) == IS_ARRAY) {
        zval *stop;
        ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(zstops), stop) {
            if (n >= 64 || Z_TYPE_P(stop) != IS_ARRAY) continue;
            zval *o = array_at(Z_ARRVAL_P(stop), 0), *c = array_at(Z_ARRVAL_P(stop), 1);
            if (!o || !c || Z_TYPE_P(c) != IS_ARRAY) continue;
            offsets[n] = fmaxf(0, fminf(1, (float) zval_get_double(o)));
            float def[4] = {0, 0, 0, 1};
            memcpy(colors[n], def, sizeof def);
            read_floats(Z_ARRVAL_P(c), colors[n], 4);
            /* Insertion keeps equal offsets in the order they came. */
            int j = n;
            while (j > 0 && offsets[j - 1] > offsets[j]) {
                float to = offsets[j]; offsets[j] = offsets[j - 1]; offsets[j - 1] = to;
                float tc[4]; memcpy(tc, colors[j], sizeof tc); memcpy(colors[j], colors[j - 1], sizeof tc); memcpy(colors[j - 1], tc, sizeof tc);
                j--;
            }
            n++;
        } ZEND_HASH_FOREACH_END();
    }
    if (n == 0) { offsets[0] = 0; memset(colors[0], 0, sizeof colors[0]); n = 1; }
    phpsdl_paint_stops(p, offsets, (const float (*)[4]) colors, n);
    *hash = phpsdl_hash_bytes(offsets, sizeof(float) * n, *hash);
    *hash = phpsdl_hash_bytes(colors, sizeof(float) * 4 * n, *hash);
}

/* A paint: [r, g, b, a], or ['linear', x0, y0, x1, y1, stops], ['radial', x0, y0, r0,
 * x1, y1, r1, stops], ['conic', angle, x, y, stops], ['pattern', file, repetition,
 * matrix?]. $hash identifies everything but a plain color. */
static bool parse_paint(HashTable *ht, phpsdl_paint *p, uint64_t *hash, bool smooth)
{
    memset(p, 0, sizeof *p);
    p->color[3] = 1;
    *hash = 0x9E3779B97F4A7C15ull;
    zval *first = array_at(ht, 0);
    if (!first || Z_TYPE_P(first) != IS_STRING) {
        read_floats(ht, p->color, 4);
        p->type = PAINT_SOLID;
        return true;
    }
    zend_string *type = Z_STR_P(first);
    float g[8] = {0};
    int i = 0;
    zval *v;
    ZEND_HASH_FOREACH_VAL(ht, v) {
        if (i >= 1 && i <= 6 && (Z_TYPE_P(v) == IS_LONG || Z_TYPE_P(v) == IS_DOUBLE)) g[i - 1] = (float) zval_get_double(v);
        i++;
    } ZEND_HASH_FOREACH_END();
    if (zend_string_equals_literal(type, "linear")) {
        p->type = PAINT_LINEAR; memcpy(p->g, g, sizeof(float) * 4);
        parse_stops(array_at(ht, 5), p, hash);
    } else if (zend_string_equals_literal(type, "radial")) {
        p->type = PAINT_RADIAL; memcpy(p->g, g, sizeof(float) * 6);
        parse_stops(array_at(ht, 7), p, hash);
    } else if (zend_string_equals_literal(type, "conic")) {
        p->type = PAINT_CONIC; memcpy(p->g, g, sizeof(float) * 3);
        parse_stops(array_at(ht, 4), p, hash);
    } else if (zend_string_equals_literal(type, "pattern")) {
        zval *file = array_at(ht, 1), *rep = array_at(ht, 2), *mat = array_at(ht, 3);
        if (!file || Z_TYPE_P(file) != IS_STRING) return false;
        pr_pixels *px = pixels_load(Z_STR_P(file));
        if (!px) return false;
        p->type = PAINT_PATTERN;
        p->image = px->px; p->iw = px->w; p->ih = px->h;
        const char *r = rep && Z_TYPE_P(rep) == IS_STRING ? Z_STRVAL_P(rep) : "repeat";
        p->repeat_x = !strcmp(r, "repeat") || !strcmp(r, "repeat-x");
        p->repeat_y = !strcmp(r, "repeat") || !strcmp(r, "repeat-y");
        p->smooth = smooth;
        float m[6] = {1, 0, 0, 1, 0, 0};
        if (mat && Z_TYPE_P(mat) == IS_ARRAY) read_floats(Z_ARRVAL_P(mat), m, 6);
        if (!invert(m, p->pattern_inv)) return false;
        *hash = phpsdl_hash_bytes(Z_STRVAL_P(file), Z_STRLEN_P(file), *hash);
        *hash = phpsdl_hash_bytes(r, strlen(r), *hash);
        *hash = phpsdl_hash_bytes(m, sizeof m, *hash);
        *hash ^= smooth ? 0x51 : 0x17;
    } else {
        return false;
    }
    *hash = phpsdl_hash_bytes(&p->type, sizeof p->type, *hash);
    *hash = phpsdl_hash_bytes(p->g, sizeof p->g, *hash);
    return true;
}

static void mask_instance(pr_renderer *r, pr_mask_entry *e, const float color[4])
{
    float ratio = r->ratio;
    float *p = instance(r, (pr_tex) e->tex);
    float rect[4] = {e->x / ratio, e->y / ratio, e->w / ratio, e->h / ratio};
    float uv[4] = {0, 0, 1, 1};
    put4(p, rect);
    put4(p + 8, color);
    put4(p + 12, uv);
    put4(p + 20, NO_CLIP);
    p[28] = KIND_MASK;
    p[29] = e->rgba ? 1 : 0;
}

/* $a after $b: x -> a(b(x)). */
static void mat_mul(const float a[6], const float b[6], float out[6])
{
    float r[6] = {
        a[0] * b[0] + a[2] * b[1], a[1] * b[0] + a[3] * b[1],
        a[0] * b[2] + a[2] * b[3], a[1] * b[2] + a[3] * b[3],
        a[0] * b[4] + a[2] * b[5] + a[4], a[1] * b[4] + a[3] * b[5] + a[5],
    };
    memcpy(out, r, sizeof r);
}

typedef struct {
    float m[6];           /* the path's space (a canvas's own pixels) to frame units */
    float ctm[6];         /* the canvas's current transform: user units to the path's space */
    phpsdl_stroke_style stroke;
    bool stroking;
    int rule;
    phpsdl_paint paint;
    uint64_t paint_hash;
    float alpha;
    float clip[4];        /* frame units */
    bool has_clip;
    HashTable *clip_paths; /* [[Path, matrix, rule], ...] */
    float shadow_color[4], shadow_blur, shadow_x, shadow_y;
    float dash[64];
} pr_path_style;

static phpsdl_path *path_of(zval *obj);

/* The clip paths' coverage (intersected), in device pixels; false when none or empty. */
static bool clip_mask(pr_renderer *r, HashTable *clips, phpsdl_mask *out, uint64_t *hash, bool *empty)
{
    *empty = false;
    bool have = false;
    zval *c;
    ZEND_HASH_FOREACH_VAL(clips, c) {
        if (Z_TYPE_P(c) != IS_ARRAY) continue;
        zval *zp = array_at(Z_ARRVAL_P(c), 0), *zm = array_at(Z_ARRVAL_P(c), 1), *zr = array_at(Z_ARRVAL_P(c), 2);
        if (!zp || Z_TYPE_P(zp) != IS_OBJECT) continue;
        float m[6] = {1, 0, 0, 1, 0, 0};
        if (zm && Z_TYPE_P(zm) == IS_ARRAY) read_floats(Z_ARRVAL_P(zm), m, 6);
        for (int k = 0; k < 6; k++) m[k] *= r->ratio;
        int rule = zr ? (int) zval_get_long(zr) : PATH_NONZERO;
        phpsdl_path dp = {0};
        phpsdl_path_transform(path_of(zp), m, &dp);
        *hash = phpsdl_path_hash(&dp, *hash) ^ (uint64_t) (rule + 1) * 0x2545F4914F6CDD1Dull;
        phpsdl_mask cm;
        bool ok = phpsdl_raster_fill(&dp, rule, &cm);
        phpsdl_path_free(&dp);
        if (!ok) { if (have) phpsdl_mask_free(out); *empty = true; return false; }
        if (!have) { *out = cm; have = true; }
        else {
            bool left = phpsdl_mask_intersect(out, &cm);
            phpsdl_mask_free(&cm);
            if (!left) { *empty = true; return false; }
        }
    } ZEND_HASH_FOREACH_END();
    return have;
}

static void renderer_path(pr_renderer *r, phpsdl_path *path, pr_path_style *st)
{
    if (!renderer_init(r) || path->ncmds == 0 || st->alpha <= 0) return;
    float ratio = r->ratio;
    const float *m = st->m;
    bool shadow = st->shadow_color[3] > 0 && (st->shadow_blur > 0 || st->shadow_x != 0 || st->shadow_y != 0);

    const float *ctm = st->ctm;
    bool ctm_plain = ctm[1] == 0 && ctm[2] == 0 && ctm[0] > 0 && fabsf(ctm[0] - ctm[3]) < 1e-6f;
    /* Exact boxes: one shape, one color, axis-aligned with uniform scale, nothing else. */
    if (path->shape >= PATH_SHAPE_RECT && path->shape <= PATH_SHAPE_CIRCLE && st->paint.type == PAINT_SOLID
        && !st->clip_paths && !shadow && m[1] == 0 && m[2] == 0 && m[0] > 0 && fabsf(m[0] - m[3]) < 1e-6f
        && (!st->stroking || (st->stroke.ndash == 0 && ctm_plain))) {
        float k = m[0], *a = path->shape_args;
        float x = a[0] * k + m[4], y = a[1] * k + m[5], w = a[2] * k, h = a[3] * k;
        float radii[4] = {a[4] * k, a[5] * k, a[6] * k, a[7] * k};
        float color[4] = {st->paint.color[0], st->paint.color[1], st->paint.color[2], st->paint.color[3] * st->alpha};
        float none[4] = {0, 0, 0, 0}, bw = 0;
        const float *clip = st->has_clip ? st->clip : NO_CLIP;
        if (st->stroking) {
            float hw = st->stroke.width * ctm[0] * k / 2;
            if (hw <= 0) return;
            bool square = path->shape == PATH_SHAPE_RECT && st->stroke.join != PATH_JOIN_ROUND;
            x -= hw; y -= hw; w += 2 * hw; h += 2 * hw;
            for (int i = 0; i < 4; i++) radii[i] = square ? 0 : (radii[i] > 0 || path->shape != PATH_SHAPE_RECT ? radii[i] + hw : hw);
            bw = 2 * hw;
        }
        float *p = instance(r, 0);
        float rect[4] = {x, y, w, h};
        put4(p, rect);
        put4(p + 4, radii);
        put4(p + 8, st->stroking ? none : color);
        put4(p + 12, st->stroking ? none : color);
        put4(p + 16, st->stroking ? color : none);
        put4(p + 20, clip);
        p[24] = bw;
        p[28] = KIND_BOX;
        return;
    }

    /* The shape in device pixels. A stroke is outlined in user units (the path taken
     * back through the current transform), so the pen turns and stretches with it. */
    float dm[6], um[6], ictm[6];
    for (int k = 0; k < 6; k++) dm[k] = m[k] * ratio;
    mat_mul(dm, ctm, um);
    if (!invert(ctm, ictm)) return;
    phpsdl_path device_path = {0};
    int rule = st->rule;
    if (st->stroking) {
        float scale = sqrtf(fmaxf(um[0] * um[0] + um[1] * um[1], um[2] * um[2] + um[3] * um[3]));
        phpsdl_path user = {0}, outline = {0};
        phpsdl_path_transform(path, ictm, &user);
        phpsdl_path_stroke(&user, &st->stroke, 0.2f / fmaxf(scale, 1e-6f), &outline);
        phpsdl_path_transform(&outline, um, &device_path);
        phpsdl_path_free(&outline);
        phpsdl_path_free(&user);
        rule = PATH_NONZERO;
    } else {
        phpsdl_path_transform(path, dm, &device_path);
    }
    /* What the result is cut to: the clip rectangle and the frame. */
    int cx0 = 0, cy0 = 0, cx1 = (int) ceilf(r->width * ratio), cy1 = (int) ceilf(r->height * ratio);
    if (st->has_clip) {
        cx0 = (int) fmaxf((float) cx0, floorf(st->clip[0] * ratio));
        cy0 = (int) fmaxf((float) cy0, floorf(st->clip[1] * ratio));
        cx1 = (int) fminf((float) cx1, ceilf((st->clip[0] + st->clip[2]) * ratio));
        cy1 = (int) fminf((float) cy1, ceilf((st->clip[1] + st->clip[3]) * ratio));
    }
    if (cx1 <= cx0 || cy1 <= cy0) { phpsdl_path_free(&device_path); return; }
    uint64_t shape_key = phpsdl_path_hash(&device_path, (uint64_t) rule * 0x9E3779B97F4A7C15ull + 1);
    int crop[4] = {cx0, cy0, cx1, cy1};
    uint64_t clip_key = phpsdl_hash_bytes(crop, sizeof crop, 0);
    phpsdl_mask clip = {0};
    bool have_clip = false, empty = false;
    if (st->clip_paths) {
        have_clip = clip_mask(r, st->clip_paths, &clip, &clip_key, &empty);
        if (empty) { phpsdl_path_free(&device_path); return; }
    }
    bool rgba = st->paint.type != PAINT_SOLID;
    uint64_t key = shape_key ^ (clip_key * 0x100000001b3ull);
    if (rgba) {
        key = phpsdl_hash_bytes(um, sizeof um, key ^ st->paint_hash);
        key = phpsdl_hash_bytes(&st->alpha, sizeof st->alpha, key);
    }

    /* The shadow first: the shape's coverage blurred and moved, then clipped. */
    if (shadow) {
        float sp[4] = {st->shadow_blur, st->shadow_x, st->shadow_y, ratio};
        uint64_t skey = phpsdl_hash_bytes(sp, sizeof sp, shape_key ^ (clip_key * 0x9E37ull) ^ 0x5AD0ull);
        pr_mask_entry *se = mask_find(skey);
        if (!se) {
            phpsdl_mask sm;
            if (phpsdl_raster_fill(&device_path, rule, &sm)) {
                phpsdl_mask_blur(&sm, st->shadow_blur / 2 * ratio);
                sm.x += (int) lroundf(st->shadow_x * ratio);
                sm.y += (int) lroundf(st->shadow_y * ratio);
                if (phpsdl_mask_crop(&sm, cx0, cy0, cx1, cy1) && (!have_clip || phpsdl_mask_intersect(&sm, &clip))) {
                    se = mask_store(skey, &sm, sm.a, false);
                }
                phpsdl_mask_free(&sm);
            }
        }
        if (se) {
            float color[4] = {st->shadow_color[0], st->shadow_color[1], st->shadow_color[2], st->shadow_color[3] * st->alpha};
            if (st->paint.type == PAINT_SOLID) color[3] *= st->paint.color[3];
            mask_instance(r, se, color);
        }
    }

    pr_mask_entry *e = mask_find(key);
    if (!e) {
        phpsdl_mask mk;
        if (phpsdl_raster_fill(&device_path, rule, &mk)) {
            if (phpsdl_mask_crop(&mk, cx0, cy0, cx1, cy1) && (!have_clip || phpsdl_mask_intersect(&mk, &clip))) {
                if (rgba) {
                    float inv[6];
                    if (invert(um, inv)) {
                        unsigned char *px = phpsdl_paint_bake(&mk, &st->paint, inv, st->alpha);
                        if (px) { e = mask_store(key, &mk, px, true); free(px); }
                    }
                } else {
                    e = mask_store(key, &mk, mk.a, false);
                }
            }
            phpsdl_mask_free(&mk);
        }
    }
    if (have_clip) phpsdl_mask_free(&clip);
    phpsdl_path_free(&device_path);
    if (!e) return;
    if (rgba) {
        float one[4] = {1, 1, 1, 1};
        mask_instance(r, e, one);
    } else {
        float color[4] = {st->paint.color[0], st->paint.color[1], st->paint.color[2], st->paint.color[3] * st->alpha};
        mask_instance(r, e, color);
    }
}

/* Text as outlines (for text a canvas rotates, clips to a path, strokes or fills with
 * a gradient): shaped like all text, each glyph's outline added to the path. */
typedef struct { phpsdl_path *p; float ox, oy, k; } pr_outline;

static void outline_move(hb_draw_funcs_t *f, void *d, hb_draw_state_t *st, float x, float y, void *u)
{ (void) f; (void) st; (void) u; pr_outline *o = d; phpsdl_path_move(o->p, o->ox + x * o->k, o->oy - y * o->k); }
static void outline_line(hb_draw_funcs_t *f, void *d, hb_draw_state_t *st, float x, float y, void *u)
{ (void) f; (void) st; (void) u; pr_outline *o = d; phpsdl_path_line(o->p, o->ox + x * o->k, o->oy - y * o->k); }
static void outline_quad(hb_draw_funcs_t *f, void *d, hb_draw_state_t *st, float cx, float cy, float x, float y, void *u)
{ (void) f; (void) st; (void) u; pr_outline *o = d; phpsdl_path_quad(o->p, o->ox + cx * o->k, o->oy - cy * o->k, o->ox + x * o->k, o->oy - y * o->k); }
static void outline_cubic(hb_draw_funcs_t *f, void *d, hb_draw_state_t *st, float c1x, float c1y, float c2x, float c2y, float x, float y, void *u)
{
    (void) f; (void) st; (void) u; pr_outline *o = d;
    phpsdl_path_cubic(o->p, o->ox + c1x * o->k, o->oy - c1y * o->k, o->ox + c2x * o->k, o->oy - c2y * o->k, o->ox + x * o->k, o->oy - y * o->k);
}
static void outline_close(hb_draw_funcs_t *f, void *d, hb_draw_state_t *st, void *u)
{ (void) f; (void) st; (void) u; phpsdl_path_close(((pr_outline *) d)->p); }

static hb_draw_funcs_t *outline_funcs = NULL;

static void path_text(phpsdl_path *p, const char *text, size_t len, int font, float size, float x, float baseline, float tracking)
{
    if (!outline_funcs) {
        outline_funcs = hb_draw_funcs_create();
        hb_draw_funcs_set_move_to_func(outline_funcs, outline_move, NULL, NULL);
        hb_draw_funcs_set_line_to_func(outline_funcs, outline_line, NULL, NULL);
        hb_draw_funcs_set_quadratic_to_func(outline_funcs, outline_quad, NULL, NULL);
        hb_draw_funcs_set_cubic_to_func(outline_funcs, outline_cubic, NULL, NULL);
        hb_draw_funcs_set_close_path_func(outline_funcs, outline_close, NULL, NULL);
        hb_draw_funcs_make_immutable(outline_funcs);
    }
    shape(text, len, font, size, tracking, &scratch);
    /* Outlines at 64 units a pixel for precision; the shaped run is copied first, as
     * drawing changes the fonts' scale. */
    int count = scratch.count;
    pr_glyph *glyphs = emalloc(sizeof(pr_glyph) * (count ? count : 1));
    memcpy(glyphs, scratch.glyphs, sizeof(pr_glyph) * count);
    int scale = (int) lroundf(size * 64);
    for (int i = 0; i < count; i++) {
        pr_glyph *g = &glyphs[i];
        pr_font *f = &fonts[g->font];
        font_weight(g->font, g->weight);
        hb_font_set_scale(f->font, scale, scale);
        pr_outline o = {p, x + g->x, baseline + g->y, 1.0f / 64};
        hb_font_draw_glyph(f->font, g->glyph, outline_funcs, &o);
    }
    efree(glyphs);
}

/* Frames ------------------------------------------------------------------- */

bool phpsdl_frame_begin(SDL_Window *window, int width, int height, const float clear[4])
{
    if (width < 1) width = 1;
    if (height < 1) height = 1;
    if (!device) return false;
    /* A frame left open (an exception mid-draw) is sent as it is. */
    if (frame.cmd) {
        SDL_SubmitGPUCommandBuffer(frame.cmd);
        if (frame.offscreen) SDL_ReleaseGPUTexture(device, frame.offscreen);
    }
    memset(&frame, 0, sizeof frame);
    frame_number++;
    masks_evict();
    frame.cmd = SDL_AcquireGPUCommandBuffer(device);
    if (!frame.cmd) return false;
    frame.width = width; frame.height = height;
    frame.clear = true;
    frame.color = (SDL_FColor) {clear[0], clear[1], clear[2], clear[3]};
    if (window) {
        Uint32 w, h;
        if (!SDL_WaitAndAcquireGPUSwapchainTexture(frame.cmd, window, &frame.target, &w, &h)) frame.target = NULL;
        frame.target_w = w; frame.target_h = h;
        frame.format = SDL_GetGPUSwapchainTextureFormat(device, window);
    } else {
        SDL_GPUTextureCreateInfo t = {0};
        t.type = SDL_GPU_TEXTURETYPE_2D;
        t.format = frame.format = SDL_GPU_TEXTUREFORMAT_R8G8B8A8_UNORM;
        t.usage = SDL_GPU_TEXTUREUSAGE_COLOR_TARGET | SDL_GPU_TEXTUREUSAGE_SAMPLER;
        t.width = (Uint32) width;
        t.height = (Uint32) height;
        t.layer_count_or_depth = 1;
        t.num_levels = 1;
        frame.target = frame.offscreen = SDL_CreateGPUTexture(device, &t);
        frame.target_w = (Uint32) width; frame.target_h = (Uint32) height;
    }
    /* Nothing to draw into (a minimized window): the frame is still ended. */
    return frame.target != NULL;
}

static bool save_png(const char *path, int w, int h, const unsigned char *rgba)
{
    return stbi_write_png(path, w, h, 4, rgba, w * 4) != 0;
}

bool phpsdl_frame_end(SDL_Window *window, const char *png)
{
    if (!frame.cmd) return false;
    if (frame.clear && frame.target) {
        /* Nothing was drawn: the frame is just its background. */
        SDL_GPUColorTargetInfo target = {0};
        target.texture = frame.target;
        target.clear_color = frame.color;
        target.load_op = SDL_GPU_LOADOP_CLEAR;
        target.store_op = SDL_GPU_STOREOP_STORE;
        SDL_EndGPURenderPass(SDL_BeginGPURenderPass(frame.cmd, &target, 1, NULL));
    }
    if (!frame.offscreen) {
        SDL_SubmitGPUCommandBuffer(frame.cmd);
        memset(&frame, 0, sizeof frame);
        return true;
    }
    bool ok = false;
    Uint32 bytes = (Uint32) frame.width * (Uint32) frame.height * 4;
    SDL_GPUTransferBufferCreateInfo b = {SDL_GPU_TRANSFERBUFFERUSAGE_DOWNLOAD, bytes, 0};
    SDL_GPUTransferBuffer *download = SDL_CreateGPUTransferBuffer(device, &b);
    if (download) {
        SDL_GPUCopyPass *copy = SDL_BeginGPUCopyPass(frame.cmd);
        SDL_GPUTextureRegion src = {0};
        src.texture = frame.offscreen; src.w = (Uint32) frame.width; src.h = (Uint32) frame.height; src.d = 1;
        SDL_GPUTextureTransferInfo dst = {download, 0, 0, 0};
        SDL_DownloadFromGPUTexture(copy, &src, &dst);
        SDL_EndGPUCopyPass(copy);
    }
    SDL_GPUFence *fence = SDL_SubmitGPUCommandBufferAndAcquireFence(frame.cmd);
    if (fence) {
        SDL_WaitForGPUFences(device, true, &fence, 1);
        SDL_ReleaseGPUFence(device, fence);
    }
    if (download) {
        unsigned char *map = SDL_MapGPUTransferBuffer(device, download, false);
        if (map) {
            ok = png ? save_png(png, frame.width, frame.height, map) : true;
            SDL_UnmapGPUTransferBuffer(device, download);
        }
        SDL_ReleaseGPUTransferBuffer(device, download);
    }
    SDL_ReleaseGPUTexture(device, frame.offscreen);
    memset(&frame, 0, sizeof frame);
    return ok;
}

/* PHP: PhpSdl\Text and PhpSdl\Renderer --------------------------------------- */

typedef struct {
    pr_renderer r;
    zend_object std;
} pr_renderer_object;

static zend_object_handlers renderer_handlers;
zend_class_entry *phpsdl_renderer_ce;

static inline pr_renderer *renderer_of(zval *obj)
{
    return &((pr_renderer_object *) ((char *) Z_OBJ_P(obj) - XtOffsetOf(pr_renderer_object, std)))->r;
}

static zend_object *renderer_create(zend_class_entry *ce)
{
    pr_renderer_object *o = zend_object_alloc(sizeof(pr_renderer_object), ce);
    memset(&o->r, 0, sizeof o->r);
    o->r.ratio = 1;
    zend_object_std_init(&o->std, ce);
    object_properties_init(&o->std, ce);
    o->std.handlers = &renderer_handlers;
    return &o->std;
}

static void renderer_free(zend_object *obj)
{
    pr_renderer_object *o = (pr_renderer_object *) ((char *) obj - XtOffsetOf(pr_renderer_object, std));
    if (o->r.data) efree(o->r.data);
    if (o->r.commands) efree(o->r.commands);
    /* GPU objects are shared by every renderer and released at shutdown. */
    zend_object_std_dtor(obj);
}

static int read4(HashTable *ht, float out[4], const float def[4])
{
    for (int i = 0; i < 4; i++) out[i] = def ? def[i] : 0;
    if (!ht) return 1;
    zval *v;
    int i = 0;
    ZEND_HASH_FOREACH_VAL(ht, v) {
        if (i >= 4) break;
        out[i++] = (float) zval_get_double(v);
    } ZEND_HASH_FOREACH_END();
    return 1;
}

static int font_arg(zend_string *name)
{
    int f = font_find(ZSTR_VAL(name), ZSTR_LEN(name));
    if (f < 0) zend_throw_error(NULL, "Unknown font: %s", ZSTR_VAL(name));
    return f;
}

PHP_METHOD(PhpSdl_Text, addFont)
{
    zend_string *name, *path;
    zend_long index = 0;
    ZEND_PARSE_PARAMETERS_START(2, 3)
        Z_PARAM_STR(name)
        Z_PARAM_STR(path)
        Z_PARAM_OPTIONAL
        Z_PARAM_LONG(index)
    ZEND_PARSE_PARAMETERS_END();
    if (font_find(ZSTR_VAL(name), ZSTR_LEN(name)) >= 0) RETURN_TRUE;
    RETURN_BOOL(font_load(ZSTR_VAL(name), ZSTR_VAL(path), (unsigned int) index) >= 0);
}

PHP_METHOD(PhpSdl_Text, addFallback)
{
    zend_string *path;
    zend_long index = 0;
    ZEND_PARSE_PARAMETERS_START(1, 2)
        Z_PARAM_STR(path)
        Z_PARAM_OPTIONAL
        Z_PARAM_LONG(index)
    ZEND_PARSE_PARAMETERS_END();
    if (fallback_count >= MAX_FONTS) RETURN_FALSE;
    char name[64];
    snprintf(name, sizeof name, "\x01" "fallback%d", fallback_count);
    int f = font_load(name, ZSTR_VAL(path), (unsigned int) index);
    if (f < 0) RETURN_FALSE;
    fallbacks[fallback_count++] = f;
    RETURN_TRUE;
}

PHP_METHOD(PhpSdl_Text, width)
{
    zend_string *text, *font;
    double size, tracking = 0;
    ZEND_PARSE_PARAMETERS_START(3, 4)
        Z_PARAM_STR(text)
        Z_PARAM_STR(font)
        Z_PARAM_DOUBLE(size)
        Z_PARAM_OPTIONAL
        Z_PARAM_DOUBLE(tracking)
    ZEND_PARSE_PARAMETERS_END();
    int f = font_arg(font);
    if (f < 0) RETURN_THROWS();
    if (ZSTR_LEN(text) == 0) RETURN_DOUBLE(0);
    shape(ZSTR_VAL(text), ZSTR_LEN(text), f, (float) size, (float) tracking, &scratch);
    RETURN_DOUBLE(scratch.width);
}

PHP_METHOD(PhpSdl_Text, carets)
{
    zend_string *text, *font;
    double size, tracking = 0;
    ZEND_PARSE_PARAMETERS_START(3, 4)
        Z_PARAM_STR(text)
        Z_PARAM_STR(font)
        Z_PARAM_DOUBLE(size)
        Z_PARAM_OPTIONAL
        Z_PARAM_DOUBLE(tracking)
    ZEND_PARSE_PARAMETERS_END();
    int f = font_arg(font);
    if (f < 0) RETURN_THROWS();
    const char *s = ZSTR_VAL(text);
    size_t len = ZSTR_LEN(text);
    /* Character index of every byte, and the x where each character starts. */
    int *char_at = emalloc(sizeof(int) * (len + 1));
    int chars = 0;
    for (size_t b = 0; b < len;) {
        uint32_t cp;
        int n = utf8_next((const unsigned char *) s + b, len - b, &cp);
        for (int k = 0; k < n; k++) char_at[b + k] = chars;
        b += n;
        chars++;
    }
    char_at[len] = chars;
    float *x = emalloc(sizeof(float) * (chars + 1));
    for (int i = 0; i <= chars; i++) x[i] = -1;
    shape(s, len, f, (float) size, (float) tracking, &scratch);
    for (int i = 0; i < scratch.count; i++) {
        int c = char_at[scratch.glyphs[i].cluster < len ? scratch.glyphs[i].cluster : len];
        float gx = scratch.glyphs[i].x;
        if (x[c] < 0 || gx < x[c]) x[c] = gx;
    }
    x[0] = 0;
    x[chars] = scratch.width;
    /* Characters inside a cluster (a ligature, a base and its marks) share its width evenly. */
    for (int i = 1; i < chars;) {
        if (x[i] >= 0) { i++; continue; }
        int j = i;
        while (j < chars && x[j] < 0) j++;
        float a = x[i - 1], b = x[j];
        for (int k = i; k < j; k++) x[k] = a + (b - a) * (k - i + 1) / (j - i + 1);
        i = j;
    }
    array_init_size(return_value, chars + 1);
    for (int i = 0; i <= chars; i++) add_next_index_double(return_value, x[i]);
    efree(x);
    efree(char_at);
}

PHP_METHOD(PhpSdl_Text, metrics)
{
    zend_string *font;
    double size;
    ZEND_PARSE_PARAMETERS_START(2, 2)
        Z_PARAM_STR(font)
        Z_PARAM_DOUBLE(size)
    ZEND_PARSE_PARAMETERS_END();
    int f = font_arg(font);
    if (f < 0) RETURN_THROWS();
    array_init(return_value);
    add_next_index_double(return_value, fonts[f].ascender * size);
    add_next_index_double(return_value, fonts[f].descender * size);
    add_next_index_double(return_value, fonts[f].gap * size);
}

/* Canvas measureText: the advance, and the ink's box around the pen (left and right of
 * x, ascent above and descent below the baseline), in pixels. */
PHP_METHOD(PhpSdl_Text, measure)
{
    zend_string *text, *font;
    double size, tracking = 0;
    ZEND_PARSE_PARAMETERS_START(3, 4)
        Z_PARAM_STR(text) Z_PARAM_STR(font) Z_PARAM_DOUBLE(size) Z_PARAM_OPTIONAL Z_PARAM_DOUBLE(tracking)
    ZEND_PARSE_PARAMETERS_END();
    int f = font_arg(font);
    if (f < 0) RETURN_THROWS();
    float left = 0, right = 0, ascent = 0, descent = 0, width = 0;
    if (ZSTR_LEN(text)) {
        shape(ZSTR_VAL(text), ZSTR_LEN(text), f, (float) size, (float) tracking, &scratch);
        width = scratch.width;
        int count = scratch.count;
        pr_glyph *glyphs = emalloc(sizeof(pr_glyph) * (count ? count : 1));
        memcpy(glyphs, scratch.glyphs, sizeof(pr_glyph) * count);
        int scale = (int) lroundf((float) size * 64);
        bool any = false;
        for (int i = 0; i < count; i++) {
            pr_glyph *g = &glyphs[i];
            font_weight(g->font, g->weight);
            hb_font_set_scale(fonts[g->font].font, scale, scale);
            hb_glyph_extents_t ext;
            if (!hb_font_get_glyph_extents(fonts[g->font].font, g->glyph, &ext) || ext.width == 0) continue;
            float x0 = g->x + ext.x_bearing / 64.0f, x1 = x0 + ext.width / 64.0f;
            float top = ext.y_bearing / 64.0f - g->y, bottom = -(ext.y_bearing + ext.height) / 64.0f + g->y;
            if (!any) { left = -x0; right = x1; ascent = top; descent = bottom; any = true; }
            else { left = fmaxf(left, -x0); right = fmaxf(right, x1); ascent = fmaxf(ascent, top); descent = fmaxf(descent, bottom); }
        }
        efree(glyphs);
    }
    array_init(return_value);
    add_assoc_double(return_value, "width", width);
    add_assoc_double(return_value, "left", left);
    add_assoc_double(return_value, "right", right);
    add_assoc_double(return_value, "ascent", ascent);
    add_assoc_double(return_value, "descent", descent);
}

PHP_METHOD(PhpSdl_Renderer, begin)
{
    double w, h, ratio;
    ZEND_PARSE_PARAMETERS_START(3, 3)
        Z_PARAM_DOUBLE(w)
        Z_PARAM_DOUBLE(h)
        Z_PARAM_DOUBLE(ratio)
    ZEND_PARSE_PARAMETERS_END();
    pr_renderer *r = renderer_of(ZEND_THIS);
    r->width = (float) w; r->height = (float) h; r->ratio = (float) (ratio > 0 ? ratio : 1);
    r->count = r->command_count = 0;
}

PHP_METHOD(PhpSdl_Renderer, box)
{
    double x, y, w, h, bw = 0;
    HashTable *radii, *top, *bottom = NULL, *border = NULL, *clip = NULL;
    ZEND_PARSE_PARAMETERS_START(6, 10)
        Z_PARAM_DOUBLE(x)
        Z_PARAM_DOUBLE(y)
        Z_PARAM_DOUBLE(w)
        Z_PARAM_DOUBLE(h)
        Z_PARAM_ARRAY_HT(radii)
        Z_PARAM_ARRAY_HT(top)
        Z_PARAM_OPTIONAL
        Z_PARAM_ARRAY_HT_OR_NULL(bottom)
        Z_PARAM_ARRAY_HT_OR_NULL(border)
        Z_PARAM_DOUBLE(bw)
        Z_PARAM_ARRAY_HT_OR_NULL(clip)
    ZEND_PARSE_PARAMETERS_END();
    pr_renderer *r = renderer_of(ZEND_THIS);
    float *p = instance(r, 0);
    float rect[4] = {(float) x, (float) y, (float) w, (float) h}, v[4];
    put4(p, rect);
    read4(radii, v, NULL); put4(p + 4, v);
    read4(top, v, NULL); put4(p + 8, v);
    read4(bottom ? bottom : top, v, NULL); put4(p + 12, v);
    read4(border, v, NULL); put4(p + 16, v);
    read4(clip, v, NO_CLIP); put4(p + 20, v);
    p[24] = (float) bw;
    p[28] = KIND_BOX;
}

PHP_METHOD(PhpSdl_Renderer, shadow)
{
    double x, y, w, h, blur, spread = 0, offset = 0;
    HashTable *radii, *color, *clip = NULL;
    ZEND_PARSE_PARAMETERS_START(7, 10)
        Z_PARAM_DOUBLE(x)
        Z_PARAM_DOUBLE(y)
        Z_PARAM_DOUBLE(w)
        Z_PARAM_DOUBLE(h)
        Z_PARAM_ARRAY_HT(radii)
        Z_PARAM_ARRAY_HT(color)
        Z_PARAM_DOUBLE(blur)
        Z_PARAM_OPTIONAL
        Z_PARAM_DOUBLE(spread)
        Z_PARAM_DOUBLE(offset)
        Z_PARAM_ARRAY_HT_OR_NULL(clip)
    ZEND_PARSE_PARAMETERS_END();
    pr_renderer *r = renderer_of(ZEND_THIS);
    float *p = instance(r, 0);
    float rect[4] = {(float) x, (float) y, (float) w, (float) h}, v[4];
    put4(p, rect);
    read4(radii, v, NULL); put4(p + 4, v);
    read4(color, v, NULL); put4(p + 8, v); put4(p + 12, v);
    read4(clip, v, NO_CLIP); put4(p + 20, v);
    p[25] = (float) (blur > 0 ? blur : 0.001);
    p[26] = (float) spread;
    p[27] = (float) offset;
    p[28] = KIND_BOX;
}

PHP_METHOD(PhpSdl_Renderer, text)
{
    zend_string *text, *font;
    double size, x, baseline, tracking = 0;
    HashTable *color, *clip = NULL;
    ZEND_PARSE_PARAMETERS_START(6, 8)
        Z_PARAM_STR(text)
        Z_PARAM_STR(font)
        Z_PARAM_DOUBLE(size)
        Z_PARAM_DOUBLE(x)
        Z_PARAM_DOUBLE(baseline)
        Z_PARAM_ARRAY_HT(color)
        Z_PARAM_OPTIONAL
        Z_PARAM_DOUBLE(tracking)
        Z_PARAM_ARRAY_HT_OR_NULL(clip)
    ZEND_PARSE_PARAMETERS_END();
    int f = font_arg(font);
    if (f < 0) RETURN_THROWS();
    if (ZSTR_LEN(text) == 0) return;
    float c[4], cl[4];
    read4(color, c, NULL);
    read4(clip, cl, NO_CLIP);
    renderer_text(renderer_of(ZEND_THIS), ZSTR_VAL(text), ZSTR_LEN(text), f, (float) size, (float) x, (float) baseline, c, (float) tracking, cl);
}

PHP_METHOD(PhpSdl_Renderer, icon)
{
    zend_string *svg;
    double x, y, size;
    HashTable *color, *clip = NULL;
    ZEND_PARSE_PARAMETERS_START(5, 6)
        Z_PARAM_STR(svg)
        Z_PARAM_DOUBLE(x)
        Z_PARAM_DOUBLE(y)
        Z_PARAM_DOUBLE(size)
        Z_PARAM_ARRAY_HT(color)
        Z_PARAM_OPTIONAL
        Z_PARAM_ARRAY_HT_OR_NULL(clip)
    ZEND_PARSE_PARAMETERS_END();
    pr_renderer *r = renderer_of(ZEND_THIS);
    if (!renderer_init(r)) return;
    int px = (int) lroundf((float) size * r->ratio);
    pr_slot *s = icon_slot(svg, px);
    if (!s) { renderer_flush(r); atlas_reset(); s = icon_slot(svg, px); }
    if (!s || s->w == 0) return;
    float *p = instance(r, atlas_tex);
    float rect[4] = {(float) x, (float) y, (float) size, (float) size}, v[4];
    float uv[4] = {(float) s->x / ATLAS, (float) s->y / ATLAS, (float) (s->x + s->w) / ATLAS, (float) (s->y + s->h) / ATLAS};
    put4(p, rect);
    read4(color, v, NULL); put4(p + 8, v);
    put4(p + 12, uv);
    read4(clip, v, NO_CLIP); put4(p + 20, v);
    p[28] = KIND_MASK;
}

PHP_METHOD(PhpSdl_Renderer, image)
{
    zend_string *path;
    double x, y, w, h, opacity = 1;
    HashTable *box, *radii, *clip = NULL, *source = NULL;
    ZEND_PARSE_PARAMETERS_START(7, 10)
        Z_PARAM_STR(path)
        Z_PARAM_DOUBLE(x)
        Z_PARAM_DOUBLE(y)
        Z_PARAM_DOUBLE(w)
        Z_PARAM_DOUBLE(h)
        Z_PARAM_ARRAY_HT(box)
        Z_PARAM_ARRAY_HT(radii)
        Z_PARAM_OPTIONAL
        Z_PARAM_DOUBLE(opacity)
        Z_PARAM_ARRAY_HT_OR_NULL(clip)
        Z_PARAM_ARRAY_HT_OR_NULL(source)
    ZEND_PARSE_PARAMETERS_END();
    pr_renderer *r = renderer_of(ZEND_THIS);
    if (!renderer_init(r)) return;
    pr_image *img = image_load(path);
    if (!img) return;
    float *p = instance(r, img->tex);
    float rect[4] = {(float) x, (float) y, (float) w, (float) h}, v[4];
    float uv[4] = {0, 0, 1, 1};
    /* Part of the image ([x, y, w, h] in its pixels), as canvas's drawImage takes. */
    float src[4];
    if (source && read_floats(source, src, 4) == 4 && img->w > 0 && img->h > 0) {
        uv[0] = src[0] / img->w; uv[1] = src[1] / img->h;
        uv[2] = (src[0] + src[2]) / img->w; uv[3] = (src[1] + src[3]) / img->h;
    }
    float tint[4] = {1, 1, 1, (float) opacity};
    put4(p, rect);
    read4(radii, v, NULL); put4(p + 4, v);
    put4(p + 8, tint);
    put4(p + 12, uv);
    read4(box, v, rect); put4(p + 16, v);
    float cl[4], bx[4];
    read4(clip, cl, NO_CLIP);
    read4(box, bx, rect);
    /* Drawing stops at the box (cover and fill) and at the clip. */
    float x0 = fmaxf(cl[0], bx[0]), y0 = fmaxf(cl[1], bx[1]);
    float x1 = fminf(cl[0] + cl[2], bx[0] + bx[2]), y1 = fminf(cl[1] + cl[3], bx[1] + bx[3]);
    float both[4] = {x0, y0, fmaxf(0, x1 - x0), fmaxf(0, y1 - y0)};
    put4(p + 20, both);
    p[28] = KIND_IMAGE;
}

/* PHP: PhpSdl\Path (a canvas Path2D) and Renderer::path() ---------------------- */

typedef struct {
    phpsdl_path path;
    zend_object std;
} pr_path_object;

static zend_object_handlers path_handlers;
zend_class_entry *phpsdl_path_ce;

static inline pr_path_object *path_object(zend_object *obj)
{
    return (pr_path_object *) ((char *) obj - XtOffsetOf(pr_path_object, std));
}

static phpsdl_path *path_of(zval *obj) { return &path_object(Z_OBJ_P(obj))->path; }
#define THIS_PATH() path_of(ZEND_THIS)

static zend_object *path_create(zend_class_entry *ce)
{
    pr_path_object *o = zend_object_alloc(sizeof(pr_path_object), ce);
    memset(&o->path, 0, sizeof o->path);
    zend_object_std_init(&o->std, ce);
    object_properties_init(&o->std, ce);
    o->std.handlers = &path_handlers;
    return &o->std;
}

static zend_object *path_clone(zend_object *old)
{
    zend_object *obj = path_create(old->ce);
    phpsdl_path_copy(&path_object(old)->path, &path_object(obj)->path);
    zend_objects_clone_members(obj, old);
    return obj;
}

static void path_free(zend_object *obj)
{
    phpsdl_path_free(&path_object(obj)->path);
    zend_object_std_dtor(obj);
}

static bool finite_all(const double *v, int n)
{
    for (int i = 0; i < n; i++) if (!isfinite(v[i])) return false;
    return true;
}

PHP_METHOD(PhpSdl_Path, moveTo)
{
    double v[2];
    ZEND_PARSE_PARAMETERS_START(2, 2) Z_PARAM_DOUBLE(v[0]) Z_PARAM_DOUBLE(v[1]) ZEND_PARSE_PARAMETERS_END();
    if (finite_all(v, 2)) phpsdl_path_move(THIS_PATH(), (float) v[0], (float) v[1]);
}

PHP_METHOD(PhpSdl_Path, lineTo)
{
    double v[2];
    ZEND_PARSE_PARAMETERS_START(2, 2) Z_PARAM_DOUBLE(v[0]) Z_PARAM_DOUBLE(v[1]) ZEND_PARSE_PARAMETERS_END();
    if (finite_all(v, 2)) phpsdl_path_line(THIS_PATH(), (float) v[0], (float) v[1]);
}

PHP_METHOD(PhpSdl_Path, quadraticCurveTo)
{
    double v[4];
    ZEND_PARSE_PARAMETERS_START(4, 4) Z_PARAM_DOUBLE(v[0]) Z_PARAM_DOUBLE(v[1]) Z_PARAM_DOUBLE(v[2]) Z_PARAM_DOUBLE(v[3]) ZEND_PARSE_PARAMETERS_END();
    if (finite_all(v, 4)) phpsdl_path_quad(THIS_PATH(), (float) v[0], (float) v[1], (float) v[2], (float) v[3]);
}

PHP_METHOD(PhpSdl_Path, bezierCurveTo)
{
    double v[6];
    ZEND_PARSE_PARAMETERS_START(6, 6)
        Z_PARAM_DOUBLE(v[0]) Z_PARAM_DOUBLE(v[1]) Z_PARAM_DOUBLE(v[2]) Z_PARAM_DOUBLE(v[3]) Z_PARAM_DOUBLE(v[4]) Z_PARAM_DOUBLE(v[5])
    ZEND_PARSE_PARAMETERS_END();
    if (finite_all(v, 6)) phpsdl_path_cubic(THIS_PATH(), (float) v[0], (float) v[1], (float) v[2], (float) v[3], (float) v[4], (float) v[5]);
}

PHP_METHOD(PhpSdl_Path, arc)
{
    double v[5];
    bool ccw = false;
    ZEND_PARSE_PARAMETERS_START(5, 6)
        Z_PARAM_DOUBLE(v[0]) Z_PARAM_DOUBLE(v[1]) Z_PARAM_DOUBLE(v[2]) Z_PARAM_DOUBLE(v[3]) Z_PARAM_DOUBLE(v[4])
        Z_PARAM_OPTIONAL Z_PARAM_BOOL(ccw)
    ZEND_PARSE_PARAMETERS_END();
    if (!finite_all(v, 5)) return;
    if (v[2] < 0) { zend_value_error("The radius is negative"); RETURN_THROWS(); }
    phpsdl_path_ellipse(THIS_PATH(), (float) v[0], (float) v[1], (float) v[2], (float) v[2], 0, (float) v[3], (float) v[4], ccw);
}

PHP_METHOD(PhpSdl_Path, ellipse)
{
    double v[7];
    bool ccw = false;
    ZEND_PARSE_PARAMETERS_START(7, 8)
        Z_PARAM_DOUBLE(v[0]) Z_PARAM_DOUBLE(v[1]) Z_PARAM_DOUBLE(v[2]) Z_PARAM_DOUBLE(v[3]) Z_PARAM_DOUBLE(v[4]) Z_PARAM_DOUBLE(v[5]) Z_PARAM_DOUBLE(v[6])
        Z_PARAM_OPTIONAL Z_PARAM_BOOL(ccw)
    ZEND_PARSE_PARAMETERS_END();
    if (!finite_all(v, 7)) return;
    if (v[2] < 0 || v[3] < 0) { zend_value_error("A radius is negative"); RETURN_THROWS(); }
    phpsdl_path_ellipse(THIS_PATH(), (float) v[0], (float) v[1], (float) v[2], (float) v[3], (float) v[4], (float) v[5], (float) v[6], ccw);
}

PHP_METHOD(PhpSdl_Path, arcTo)
{
    double v[5];
    ZEND_PARSE_PARAMETERS_START(5, 5) Z_PARAM_DOUBLE(v[0]) Z_PARAM_DOUBLE(v[1]) Z_PARAM_DOUBLE(v[2]) Z_PARAM_DOUBLE(v[3]) Z_PARAM_DOUBLE(v[4]) ZEND_PARSE_PARAMETERS_END();
    if (!finite_all(v, 5)) return;
    if (v[4] < 0) { zend_value_error("The radius is negative"); RETURN_THROWS(); }
    phpsdl_path_arc_to(THIS_PATH(), (float) v[0], (float) v[1], (float) v[2], (float) v[3], (float) v[4]);
}

PHP_METHOD(PhpSdl_Path, rect)
{
    double v[4];
    ZEND_PARSE_PARAMETERS_START(4, 4) Z_PARAM_DOUBLE(v[0]) Z_PARAM_DOUBLE(v[1]) Z_PARAM_DOUBLE(v[2]) Z_PARAM_DOUBLE(v[3]) ZEND_PARSE_PARAMETERS_END();
    if (finite_all(v, 4)) phpsdl_path_rect(THIS_PATH(), (float) v[0], (float) v[1], (float) v[2], (float) v[3]);
}

/* Radii as canvas takes them: one, or [all], [tl-br, tr-bl], [tl, tr-bl, br], [tl, tr, br, bl]. */
PHP_METHOD(PhpSdl_Path, roundRect)
{
    double v[4];
    zval *radii = NULL;
    ZEND_PARSE_PARAMETERS_START(4, 5)
        Z_PARAM_DOUBLE(v[0]) Z_PARAM_DOUBLE(v[1]) Z_PARAM_DOUBLE(v[2]) Z_PARAM_DOUBLE(v[3])
        Z_PARAM_OPTIONAL Z_PARAM_ZVAL(radii)
    ZEND_PARSE_PARAMETERS_END();
    if (!finite_all(v, 4)) return;
    float r[4] = {0, 0, 0, 0}, in[4] = {0, 0, 0, 0};
    int n = 1;
    if (radii && Z_TYPE_P(radii) == IS_ARRAY) n = read_floats(Z_ARRVAL_P(radii), in, 4);
    else if (radii) in[0] = (float) zval_get_double(radii);
    if (n < 1 || n > 4) { zend_value_error("roundRect() takes 1 to 4 radii"); RETURN_THROWS(); }
    for (int i = 0; i < n; i++) if (in[i] < 0) { zend_value_error("A radius is negative"); RETURN_THROWS(); }
    switch (n) {
        case 1: r[0] = r[1] = r[2] = r[3] = in[0]; break;
        case 2: r[0] = r[2] = in[0]; r[1] = r[3] = in[1]; break;
        case 3: r[0] = in[0]; r[1] = r[3] = in[1]; r[2] = in[2]; break;
        default: memcpy(r, in, sizeof r);
    }
    phpsdl_path_rounded(THIS_PATH(), (float) v[0], (float) v[1], (float) v[2], (float) v[3], r);
}

PHP_METHOD(PhpSdl_Path, closePath)
{
    ZEND_PARSE_PARAMETERS_NONE();
    phpsdl_path_close(THIS_PATH());
}

PHP_METHOD(PhpSdl_Path, addPath)
{
    zval *other;
    HashTable *matrix = NULL;
    ZEND_PARSE_PARAMETERS_START(1, 2)
        Z_PARAM_OBJECT_OF_CLASS(other, phpsdl_path_ce)
        Z_PARAM_OPTIONAL Z_PARAM_ARRAY_HT_OR_NULL(matrix)
    ZEND_PARSE_PARAMETERS_END();
    float m[6] = {1, 0, 0, 1, 0, 0};
    if (matrix) read_floats(matrix, m, 6);
    phpsdl_path copy = {0};
    phpsdl_path_copy(path_of(other), &copy); /* the same path may be added to itself */
    phpsdl_path_add(THIS_PATH(), &copy, m);
    phpsdl_path_free(&copy);
}

PHP_METHOD(PhpSdl_Path, text)
{
    zend_string *text, *font;
    double size, x, y, tracking = 0;
    ZEND_PARSE_PARAMETERS_START(5, 6)
        Z_PARAM_STR(text) Z_PARAM_STR(font) Z_PARAM_DOUBLE(size) Z_PARAM_DOUBLE(x) Z_PARAM_DOUBLE(y)
        Z_PARAM_OPTIONAL Z_PARAM_DOUBLE(tracking)
    ZEND_PARSE_PARAMETERS_END();
    int f = font_arg(font);
    if (f < 0) RETURN_THROWS();
    if (ZSTR_LEN(text) && size > 0) path_text(THIS_PATH(), ZSTR_VAL(text), ZSTR_LEN(text), f, (float) size, (float) x, (float) y, (float) tracking);
}

PHP_METHOD(PhpSdl_Path, setTransform)
{
    HashTable *matrix = NULL;
    ZEND_PARSE_PARAMETERS_START(0, 1) Z_PARAM_OPTIONAL Z_PARAM_ARRAY_HT_OR_NULL(matrix) ZEND_PARSE_PARAMETERS_END();
    float m[6] = {1, 0, 0, 1, 0, 0};
    if (matrix) read_floats(matrix, m, 6);
    phpsdl_path_set_transform(THIS_PATH(), m);
}

PHP_METHOD(PhpSdl_Path, clear)
{
    ZEND_PARSE_PARAMETERS_NONE();
    phpsdl_path_clear(THIS_PATH());
}

PHP_METHOD(PhpSdl_Path, isEmpty)
{
    ZEND_PARSE_PARAMETERS_NONE();
    RETURN_BOOL(THIS_PATH()->ncmds == 0);
}

/* Hit tests, in the path's own units. */
PHP_METHOD(PhpSdl_Path, contains)
{
    double x, y;
    zend_long rule = PATH_NONZERO;
    ZEND_PARSE_PARAMETERS_START(2, 3) Z_PARAM_DOUBLE(x) Z_PARAM_DOUBLE(y) Z_PARAM_OPTIONAL Z_PARAM_LONG(rule) ZEND_PARSE_PARAMETERS_END();
    RETURN_BOOL(phpsdl_path_contains(THIS_PATH(), (float) x, (float) y, (int) rule, 0.1f));
}

static void read_stroke(HashTable *ht, phpsdl_stroke_style *s, float *dash)
{
    /* [width, join, cap, miterLimit, dashes, dashOffset] */
    s->width = 1; s->join = PATH_JOIN_MITER; s->cap = PATH_CAP_BUTT; s->miter_limit = 10;
    s->dash = dash; s->ndash = 0; s->dash_offset = 0;
    zval *v;
    if ((v = array_at(ht, 0))) s->width = (float) zval_get_double(v);
    if ((v = array_at(ht, 1))) s->join = (int) zval_get_long(v);
    if ((v = array_at(ht, 2))) s->cap = (int) zval_get_long(v);
    if ((v = array_at(ht, 3))) s->miter_limit = (float) zval_get_double(v);
    if ((v = array_at(ht, 4)) && Z_TYPE_P(v) == IS_ARRAY) s->ndash = read_floats(Z_ARRVAL_P(v), dash, 64);
    if ((v = array_at(ht, 5))) s->dash_offset = (float) zval_get_double(v);
    for (int i = 0; i < s->ndash; i++) if (!(dash[i] >= 0)) { s->ndash = 0; break; }
}

PHP_METHOD(PhpSdl_Path, containsStroke)
{
    double x, y;
    HashTable *stroke;
    ZEND_PARSE_PARAMETERS_START(3, 3) Z_PARAM_DOUBLE(x) Z_PARAM_DOUBLE(y) Z_PARAM_ARRAY_HT(stroke) ZEND_PARSE_PARAMETERS_END();
    phpsdl_stroke_style s;
    float dash[64];
    read_stroke(stroke, &s, dash);
    phpsdl_path outline = {0};
    phpsdl_path_stroke(THIS_PATH(), &s, 0.1f, &outline);
    bool in = phpsdl_path_contains(&outline, (float) x, (float) y, PATH_NONZERO, 0.1f);
    phpsdl_path_free(&outline);
    RETURN_BOOL(in);
}

/* [x0, y0, x1, y1] of the points, control points included; null when empty. */
PHP_METHOD(PhpSdl_Path, bounds)
{
    ZEND_PARSE_PARAMETERS_NONE();
    float b[4];
    if (!phpsdl_path_bounds(THIS_PATH(), b)) RETURN_NULL();
    array_init(return_value);
    for (int i = 0; i < 4; i++) add_next_index_double(return_value, b[i]);
}

/* Draws $path as $style says: ['stroke' => [width, join, cap, miterLimit, dashes,
 * offset] or absent to fill, 'rule' => 0 non-zero or 1 even-odd, 'transform' => [a, b,
 * c, d, e, f], 'paint' => see parse_paint, 'alpha', 'smooth', 'clip' => [x, y, w, h],
 * 'clipPaths' => [[Path, matrix, rule], ...], 'shadow' => [color, blur, x, y]]. */
PHP_METHOD(PhpSdl_Renderer, path)
{
    zval *zpath;
    HashTable *style;
    ZEND_PARSE_PARAMETERS_START(2, 2)
        Z_PARAM_OBJECT_OF_CLASS(zpath, phpsdl_path_ce)
        Z_PARAM_ARRAY_HT(style)
    ZEND_PARSE_PARAMETERS_END();
    pr_path_style st;
    memset(&st, 0, sizeof st);
    float id[6] = {1, 0, 0, 1, 0, 0};
    memcpy(st.m, id, sizeof id);
    memcpy(st.ctm, id, sizeof id);
    st.alpha = 1;
    zval *v;
    if ((v = zend_hash_str_find(style, "transform", sizeof "transform" - 1)) && Z_TYPE_P(v) == IS_ARRAY) read_floats(Z_ARRVAL_P(v), st.m, 6);
    if ((v = zend_hash_str_find(style, "ctm", sizeof "ctm" - 1)) && Z_TYPE_P(v) == IS_ARRAY) read_floats(Z_ARRVAL_P(v), st.ctm, 6);
    if ((v = zend_hash_str_find(style, "stroke", sizeof "stroke" - 1)) && Z_TYPE_P(v) == IS_ARRAY) {
        st.stroking = true;
        read_stroke(Z_ARRVAL_P(v), &st.stroke, st.dash);
        if (!(st.stroke.width > 0)) return;
    }
    if ((v = zend_hash_str_find(style, "rule", sizeof "rule" - 1))) st.rule = (int) zval_get_long(v);
    if ((v = zend_hash_str_find(style, "alpha", sizeof "alpha" - 1))) st.alpha = (float) zval_get_double(v);
    bool smooth = true;
    if ((v = zend_hash_str_find(style, "smooth", sizeof "smooth" - 1))) smooth = zend_is_true(v);
    if ((v = zend_hash_str_find(style, "paint", sizeof "paint" - 1)) && Z_TYPE_P(v) == IS_ARRAY) {
        if (!parse_paint(Z_ARRVAL_P(v), &st.paint, &st.paint_hash, smooth)) return;
    } else {
        st.paint.color[3] = 1;
    }
    if ((v = zend_hash_str_find(style, "clip", sizeof "clip" - 1)) && Z_TYPE_P(v) == IS_ARRAY) st.has_clip = read_floats(Z_ARRVAL_P(v), st.clip, 4) == 4;
    if ((v = zend_hash_str_find(style, "clipPaths", sizeof "clipPaths" - 1)) && Z_TYPE_P(v) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(v))) st.clip_paths = Z_ARRVAL_P(v);
    if ((v = zend_hash_str_find(style, "shadow", sizeof "shadow" - 1)) && Z_TYPE_P(v) == IS_ARRAY) {
        zval *c = array_at(Z_ARRVAL_P(v), 0);
        if (c && Z_TYPE_P(c) == IS_ARRAY) read_floats(Z_ARRVAL_P(c), st.shadow_color, 4);
        float rest[3] = {0, 0, 0};
        for (int i = 0; i < 3; i++) if ((c = array_at(Z_ARRVAL_P(v), i + 1))) rest[i] = (float) zval_get_double(c);
        st.shadow_blur = fmaxf(0, rest[0]); st.shadow_x = rest[1]; st.shadow_y = rest[2];
    }
    renderer_path(renderer_of(ZEND_THIS), path_of(zpath), &st);
}

/* Images and patterns changed on disk (hot reload): drop them, so they load again. */
PHP_METHOD(PhpSdl_Renderer, forget)
{
    ZEND_PARSE_PARAMETERS_NONE();
    for (int i = 0; i < image_count; i++) {
        if (images[i].tex && device) SDL_ReleaseGPUTexture(device, (SDL_GPUTexture *) images[i].tex);
        zend_string_release(images[i].path);
    }
    image_count = 0;
    for (int i = 0; i < pixels_count; i++) { free(pixels[i].px); zend_string_release(pixels[i].path); }
    pixels_count = 0;
    while (mask_count) mask_release(mask_count - 1);
}

PHP_METHOD(PhpSdl_Renderer, flush)
{
    ZEND_PARSE_PARAMETERS_NONE();
    renderer_flush(renderer_of(ZEND_THIS));
}

PHP_METHOD(PhpSdl_Renderer, imageSize)
{
    zend_string *path;
    ZEND_PARSE_PARAMETERS_START(1, 1)
        Z_PARAM_STR(path)
    ZEND_PARSE_PARAMETERS_END();
    int w = 0, h = 0, n;
    if (!stbi_info(ZSTR_VAL(path), &w, &h, &n)) { w = h = 0; }
    array_init(return_value);
    add_next_index_long(return_value, w);
    add_next_index_long(return_value, h);
}

void phpsdl_render_register(zend_class_entry *renderer_ce, zend_class_entry *path_ce)
{
    phpsdl_path_ce = path_ce;
    path_ce->create_object = path_create;
    memcpy(&path_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
    path_handlers.offset = XtOffsetOf(pr_path_object, std);
    path_handlers.free_obj = path_free;
    path_handlers.clone_obj = path_clone;
    phpsdl_renderer_ce = renderer_ce;
    renderer_ce->create_object = renderer_create;
    memcpy(&renderer_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));
    renderer_handlers.offset = XtOffsetOf(pr_renderer_object, std);
    renderer_handlers.free_obj = renderer_free;
    renderer_handlers.clone_obj = NULL;
}

void phpsdl_render_shutdown(void)
{
    for (int i = 0; i < font_count; i++) {
        hb_font_destroy(fonts[i].font);
        hb_face_destroy(fonts[i].face);
    }
    font_count = fallback_count = 0;
    if (shape_buffer) { hb_buffer_destroy(shape_buffer); shape_buffer = NULL; }
    if (raster) { hb_raster_draw_destroy(raster); raster = NULL; }
    if (device) {
        SDL_WaitForGPUIdle(device);
        if (frame.offscreen) SDL_ReleaseGPUTexture(device, frame.offscreen);
        for (int i = 0; i < image_count; i++) if (images[i].tex) SDL_ReleaseGPUTexture(device, (SDL_GPUTexture *) images[i].tex);
        while (mask_count) mask_release(mask_count - 1);
        if (atlas_tex) SDL_ReleaseGPUTexture(device, (SDL_GPUTexture *) atlas_tex);
        for (int i = 0; i < gpu_pipeline_count; i++) SDL_ReleaseGPUGraphicsPipeline(device, gpu_pipelines[i].pipeline);
        if (gpu_vs) SDL_ReleaseGPUShader(device, gpu_vs);
        if (gpu_fs) SDL_ReleaseGPUShader(device, gpu_fs);
        if (gpu_sampler) SDL_ReleaseGPUSampler(device, gpu_sampler);
        if (gpu_instances) SDL_ReleaseGPUBuffer(device, gpu_instances);
        if (gpu_instances_upload) SDL_ReleaseGPUTransferBuffer(device, gpu_instances_upload);
        if (gpu_atlas_upload) SDL_ReleaseGPUTransferBuffer(device, gpu_atlas_upload);
        SDL_DestroyGPUDevice(device);
        device = NULL;
        gpu_vs = gpu_fs = NULL; gpu_sampler = NULL; gpu_pipeline_count = 0;
        gpu_instances = NULL; gpu_instances_upload = gpu_atlas_upload = NULL; gpu_instances_size = 0;
        memset(&frame, 0, sizeof frame);
    }
    for (int i = 0; i < image_count; i++) zend_string_release(images[i].path);
    image_count = 0;
    if (atlas) { pefree(atlas, 1); atlas = NULL; }
    atlas_tex = 0;
    mask_count = 0;
    for (int i = 0; i < pixels_count; i++) { free(pixels[i].px); zend_string_release(pixels[i].path); }
    pixels_count = 0;
    if (outline_funcs) { hb_draw_funcs_destroy(outline_funcs); outline_funcs = NULL; }
    phpsdl_raster_shutdown();
    if (scratch.glyphs) { pefree(scratch.glyphs, 1); scratch.glyphs = NULL; scratch.cap = 0; }
}
