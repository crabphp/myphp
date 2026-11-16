/*
 * phpsdl's renderer: the toolkit's boxes, borders, gradients, shadows, text, icons
 * and images in one ordered list, drawn with as few draw calls as textures allow.
 *
 * Text is shaped by HarfBuzz (ligatures, marks, complex scripts), falls back through
 * a list of fonts per character, and is rasterized by HarfBuzz into an 8-bit atlas
 * shared with icons, which are white SVG masks tinted when drawn.
 *
 * The list draws with SDL_GPU (Vulkan, Metal, Direct3D 12) when MYPHP_GPU=1, and
 * with OpenGL 3.3 otherwise; both run the same shader (shaders/ui.hlsl, and the GLSL
 * here). Frames, on screen or off, start and end here too.
 */

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include <php.h>
#include <zend_exceptions.h>
#include <math.h>
#include <glad/glad.h>
#include <hb.h>
#include <hb-ot.h>
#include <hb-raster.h>

#include "nanosvg.h"
#define NANOSVGRAST_IMPLEMENTATION
#include "nanosvgrast.h"
#include "stb_image.h"
#include "stb_image_write.h"
#include "shaders/ui_shaders.h"

#include "render.h"

/* A texture: a GL name, or an SDL_GPUTexture. */
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

bool phpsdl_gpu = false;
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
    int width, height;
    bool clear;
    SDL_FColor color;
} frame;

const char *phpsdl_backend(void)
{
    if (!phpsdl_gpu) return "opengl";
    return device ? SDL_GetGPUDeviceDriver(device) : "gpu";
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

/* An RGBA texture with mipmaps, uploaded on a command buffer of its own. */
static SDL_GPUTexture *gpu_texture_rgba(int w, int h, const unsigned char *pixels)
{
    Uint32 levels = 1;
    for (int s = w > h ? w : h; s > 1; s >>= 1) levels++;
    SDL_GPUTextureCreateInfo t = {0};
    t.type = SDL_GPU_TEXTURETYPE_2D;
    t.format = SDL_GPU_TEXTUREFORMAT_R8G8B8A8_UNORM;
    t.usage = SDL_GPU_TEXTUREUSAGE_SAMPLER | SDL_GPU_TEXTUREUSAGE_COLOR_TARGET;
    t.width = (Uint32) w;
    t.height = (Uint32) h;
    t.layer_count_or_depth = 1;
    t.num_levels = levels;
    SDL_GPUTexture *texture = SDL_CreateGPUTexture(device, &t);
    if (!texture) return NULL;
    SDL_GPUTransferBufferCreateInfo b = {SDL_GPU_TRANSFERBUFFERUSAGE_UPLOAD, (Uint32) w * (Uint32) h * 4, 0};
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
    if (phpsdl_gpu) {
        img->tex = (pr_tex) gpu_texture_rgba(w, h, px);
        stbi_image_free(px);
        return img;
    }
    GLuint tex;
    glGenTextures(1, &tex);
    img->tex = tex;
    glBindTexture(GL_TEXTURE_2D, img->tex);
    glPixelStorei(GL_UNPACK_ALIGNMENT, 4);
    glTexImage2D(GL_TEXTURE_2D, 0, GL_RGBA8, w, h, 0, GL_RGBA, GL_UNSIGNED_BYTE, px);
    glGenerateMipmap(GL_TEXTURE_2D);
    glTexParameteri(GL_TEXTURE_2D, GL_TEXTURE_MIN_FILTER, GL_LINEAR_MIPMAP_LINEAR);
    glTexParameteri(GL_TEXTURE_2D, GL_TEXTURE_MAG_FILTER, GL_LINEAR);
    glTexParameteri(GL_TEXTURE_2D, GL_TEXTURE_WRAP_S, GL_CLAMP_TO_EDGE);
    glTexParameteri(GL_TEXTURE_2D, GL_TEXTURE_WRAP_T, GL_CLAMP_TO_EDGE);
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
    GLuint program, vao, vbo;
    GLint u_view, u_scale, u_tex;
} pr_renderer;

static const char *VERTEX =
    "#version 330 core\n"
    "layout(location = 0) in vec4 aRect;\n"
    "layout(location = 1) in vec4 aRadii;\n"
    "layout(location = 2) in vec4 aTop;\n"
    "layout(location = 3) in vec4 aBottom;\n"
    "layout(location = 4) in vec4 aBorder;\n"
    "layout(location = 5) in vec4 aClip;\n"
    "layout(location = 6) in vec4 aParams;\n"
    "layout(location = 7) in vec4 aKind;\n"
    "uniform vec2 uView;\n"
    "out vec2 vPos;\n"
    "out vec2 vUv;\n"
    "flat out vec4 vRect, vRadii, vTop, vBottom, vBorder, vParams, vKind;\n"
    "void main() {\n"
    "    vec2 corner = vec2(gl_VertexID & 1, gl_VertexID >> 1);\n"
    "    float pad = aKind.x == 0.0 ? aParams.y + 1.0 : 0.0;\n"
    /* Clip by shrinking the quad, and keep texture coordinates on the unclipped rectangle. */
    "    vec2 lo = max(aRect.xy - pad, aClip.xy);\n"
    "    vec2 hi = max(lo, min(aRect.xy + aRect.zw + pad, aClip.xy + aClip.zw));\n"
    "    vPos = mix(lo, hi, corner);\n"
    "    vec2 t = (vPos - aRect.xy) / max(aRect.zw, vec2(1e-6));\n"
    "    vUv = mix(aBottom.xy, aBottom.zw, t);\n"
    "    vRect = aRect; vRadii = aRadii; vTop = aTop; vBottom = aBottom; vBorder = aBorder; vParams = aParams; vKind = aKind;\n"
    "    gl_Position = vec4(vPos.x / uView.x * 2.0 - 1.0, 1.0 - vPos.y / uView.y * 2.0, 0.0, 1.0);\n"
    "}\n";

static const char *FRAGMENT =
    "#version 330 core\n"
    "in vec2 vPos;\n"
    "in vec2 vUv;\n"
    "flat in vec4 vRect, vRadii, vTop, vBottom, vBorder, vParams, vKind;\n"
    "uniform float uScale;\n"
    "uniform sampler2D uTex;\n"
    "out vec4 fragColor;\n"
    /* Signed distance to a rounded box; radii are top-left, top-right, bottom-right, bottom-left. */
    "float roundedBox(vec2 p, vec2 center, vec2 halfSize, vec4 r) {\n"
    "    vec2 q = p - center;\n"
    "    float radius = q.x < 0.0 ? (q.y < 0.0 ? r.x : r.w) : (q.y < 0.0 ? r.y : r.z);\n"
    "    radius = min(radius, min(halfSize.x, halfSize.y));\n"
    "    vec2 d = abs(q) - halfSize + radius;\n"
    "    return length(max(d, 0.0)) + min(max(d.x, d.y), 0.0) - radius;\n"
    "}\n"
    "vec4 premul(vec4 c) { return vec4(c.rgb * c.a, c.a); }\n"
    "void main() {\n"
    "    if (vKind.x == 1.0) {\n"
    "        fragColor = premul(vTop) * texture(uTex, vUv).r;\n"
    "        return;\n"
    "    }\n"
    "    if (vKind.x == 2.0) {\n"
    /* An image: its corners follow the box it fills (border slot), and it fades with the top color's alpha. */
    "        vec2 bh = vBorder.zw * 0.5;\n"
    "        float d = roundedBox(vPos, vBorder.xy + bh, bh, vRadii);\n"
    "        vec4 c = texture(uTex, vUv);\n"
    "        fragColor = premul(c) * vTop.a * clamp(0.5 - d * uScale, 0.0, 1.0);\n"
    "        return;\n"
    "    }\n"
    "    vec2 halfSize = vRect.zw * 0.5;\n"
    "    float d = roundedBox(vPos, vRect.xy + halfSize, halfSize, vRadii);\n"
    "    float blur = vParams.y;\n"
    "    if (blur > 0.0) {\n"
    /* A shadow is not drawn under its element: the element is the shadow moved back by its offset and spread. */
    "        float spread = vParams.z;\n"
    "        vec2 holeHalf = max(halfSize - spread, vec2(0.0));\n"
    "        vec2 holeCenter = vRect.xy + halfSize - vec2(0.0, vParams.w);\n"
    "        float hole = roundedBox(vPos, holeCenter, holeHalf, max(vRadii - spread, vec4(0.0)));\n"
    "        float outside = clamp(0.5 + hole * uScale, 0.0, 1.0);\n"
    "        fragColor = premul(vTop) * (1.0 - smoothstep(-0.5 * blur, 0.5 * blur, d)) * outside;\n"
    "        return;\n"
    "    }\n"
    "    float outer = clamp(0.5 - d * uScale, 0.0, 1.0);\n"
    "    float t = clamp((vPos.y - vRect.y) / max(vRect.w, 1.0), 0.0, 1.0);\n"
    "    vec4 fill = premul(mix(vTop, vBottom, t)) * outer;\n"
    "    float bw = vParams.x;\n"
    "    if (bw > 0.0) {\n"
    "        float inner = clamp(0.5 - (d + bw) * uScale, 0.0, 1.0);\n"
    "        vec4 band = premul(vBorder) * (outer - inner);\n"
    "        fill = band + fill * (1.0 - band.a);\n"
    "    }\n"
    "    fragColor = fill;\n"
    "}\n";

static GLuint compile(GLenum type, const char *src)
{
    GLuint s = glCreateShader(type);
    glShaderSource(s, 1, &src, NULL);
    glCompileShader(s);
    GLint ok;
    glGetShaderiv(s, GL_COMPILE_STATUS, &ok);
    if (!ok) {
        char log[2048];
        glGetShaderInfoLog(s, sizeof log, NULL, log);
        zend_throw_error(NULL, "Renderer shader: %s", log);
        glDeleteShader(s);
        return 0;
    }
    return s;
}

static int renderer_init(pr_renderer *r)
{
    if (phpsdl_gpu) return gpu_init();
    if (r->program) return 1;
    GLuint vs = compile(GL_VERTEX_SHADER, VERTEX);
    if (!vs) return 0;
    GLuint fs = compile(GL_FRAGMENT_SHADER, FRAGMENT);
    if (!fs) { glDeleteShader(vs); return 0; }
    r->program = glCreateProgram();
    glAttachShader(r->program, vs);
    glAttachShader(r->program, fs);
    glLinkProgram(r->program);
    glDeleteShader(vs);
    glDeleteShader(fs);
    GLint ok;
    glGetProgramiv(r->program, GL_LINK_STATUS, &ok);
    if (!ok) { zend_throw_error(NULL, "Renderer program does not link"); return 0; }
    r->u_view = glGetUniformLocation(r->program, "uView");
    r->u_scale = glGetUniformLocation(r->program, "uScale");
    r->u_tex = glGetUniformLocation(r->program, "uTex");
    glGenVertexArrays(1, &r->vao);
    glGenBuffers(1, &r->vbo);
    if (!atlas_tex) {
        GLuint tex;
        glGenTextures(1, &tex);
        atlas_tex = tex;
        glBindTexture(GL_TEXTURE_2D, tex);
        glTexImage2D(GL_TEXTURE_2D, 0, GL_R8, ATLAS, ATLAS, 0, GL_RED, GL_UNSIGNED_BYTE, NULL);
        glTexParameteri(GL_TEXTURE_2D, GL_TEXTURE_MIN_FILTER, GL_LINEAR);
        glTexParameteri(GL_TEXTURE_2D, GL_TEXTURE_MAG_FILTER, GL_LINEAR);
        glTexParameteri(GL_TEXTURE_2D, GL_TEXTURE_WRAP_S, GL_CLAMP_TO_EDGE);
        glTexParameteri(GL_TEXTURE_2D, GL_TEXTURE_WRAP_T, GL_CLAMP_TO_EDGE);
        if (!atlas) atlas_reset();
        dirty_top = 0; dirty_bottom = ATLAS;
    }
    return 1;
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

static void renderer_flush_gpu(pr_renderer *r);

static void renderer_flush(pr_renderer *r)
{
    if (r->count == 0) { r->command_count = 0; return; }
    if (phpsdl_gpu) { renderer_flush_gpu(r); return; }
    if (!renderer_init(r)) { r->count = r->command_count = 0; return; }
    if (dirty_bottom > dirty_top) {
        glBindTexture(GL_TEXTURE_2D, atlas_tex);
        glPixelStorei(GL_UNPACK_ALIGNMENT, 1);
        glTexSubImage2D(GL_TEXTURE_2D, 0, 0, dirty_top, ATLAS, dirty_bottom - dirty_top, GL_RED, GL_UNSIGNED_BYTE, atlas + (size_t) dirty_top * ATLAS);
        glPixelStorei(GL_UNPACK_ALIGNMENT, 4);
        dirty_top = ATLAS; dirty_bottom = 0;
    }
    glUseProgram(r->program);
    glUniform2f(r->u_view, r->width, r->height);
    glUniform1f(r->u_scale, r->ratio);
    glUniform1i(r->u_tex, 0);
    glBindVertexArray(r->vao);
    glBindBuffer(GL_ARRAY_BUFFER, r->vbo);
    glBufferData(GL_ARRAY_BUFFER, sizeof(float) * STRIDE * r->count, r->data, GL_STREAM_DRAW);
    glEnable(GL_BLEND);
    glBlendFunc(GL_ONE, GL_ONE_MINUS_SRC_ALPHA); /* premultiplied */
    glDisable(GL_DEPTH_TEST);
    glDisable(GL_CULL_FACE);
    glDisable(GL_STENCIL_TEST);
    glDisable(GL_SCISSOR_TEST);
    glActiveTexture(GL_TEXTURE0);
    for (int c = 0; c < r->command_count; c++) {
        pr_command *cmd = &r->commands[c];
        glBindTexture(GL_TEXTURE_2D, cmd->tex ? cmd->tex : atlas_tex);
        /* GL 3.3 has no base instance: point the attributes at this command's instances. */
        for (int i = 0; i < 8; i++) {
            glEnableVertexAttribArray(i);
            glVertexAttribPointer(i, 4, GL_FLOAT, GL_FALSE, STRIDE * sizeof(float), (void *) (sizeof(float) * ((size_t) cmd->first * STRIDE + i * 4)));
            glVertexAttribDivisor(i, 1);
        }
        glDrawArraysInstanced(GL_TRIANGLE_STRIP, 0, 4, cmd->count);
    }
    glBindVertexArray(0);
    glUseProgram(0);
    glBindTexture(GL_TEXTURE_2D, 0);
    r->count = 0;
    r->command_count = 0;
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

    SDL_GPUColorTargetInfo target = {0};
    target.texture = frame.target;
    target.clear_color = frame.color;
    target.load_op = frame.clear ? SDL_GPU_LOADOP_CLEAR : SDL_GPU_LOADOP_LOAD;
    target.store_op = SDL_GPU_STOREOP_STORE;
    frame.clear = false;
    float view[4] = {r->width, r->height, 0, 0};
    float scale[4] = {r->ratio, 0, 0, 0};
    SDL_PushGPUVertexUniformData(frame.cmd, 0, view, sizeof view);
    SDL_PushGPUFragmentUniformData(frame.cmd, 0, scale, sizeof scale);
    SDL_GPURenderPass *pass = SDL_BeginGPURenderPass(frame.cmd, &target, 1, NULL);
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

/* Frames ------------------------------------------------------------------- */

static GLuint capture_fbo = 0, capture_rbo[2] = {0, 0};
static int capture_w = 0, capture_h = 0;

bool phpsdl_frame_begin(SDL_Window *window, int width, int height, const float clear[4])
{
    if (width < 1) width = 1;
    if (height < 1) height = 1;
    if (!phpsdl_gpu) {
        if (window) {
            glBindFramebuffer(GL_FRAMEBUFFER, 0);
        } else {
            /* Off screen: color, and stencil for NanoVG. */
            glGenFramebuffers(1, &capture_fbo);
            glGenRenderbuffers(2, capture_rbo);
            glBindRenderbuffer(GL_RENDERBUFFER, capture_rbo[0]);
            glRenderbufferStorage(GL_RENDERBUFFER, GL_RGBA8, width, height);
            glBindRenderbuffer(GL_RENDERBUFFER, capture_rbo[1]);
            glRenderbufferStorage(GL_RENDERBUFFER, GL_DEPTH24_STENCIL8, width, height);
            glBindFramebuffer(GL_FRAMEBUFFER, capture_fbo);
            glFramebufferRenderbuffer(GL_FRAMEBUFFER, GL_COLOR_ATTACHMENT0, GL_RENDERBUFFER, capture_rbo[0]);
            glFramebufferRenderbuffer(GL_FRAMEBUFFER, GL_DEPTH_STENCIL_ATTACHMENT, GL_RENDERBUFFER, capture_rbo[1]);
            capture_w = width; capture_h = height;
            if (glCheckFramebufferStatus(GL_FRAMEBUFFER) != GL_FRAMEBUFFER_COMPLETE) return false;
        }
        glViewport(0, 0, width, height);
        glClearColor(clear[0], clear[1], clear[2], clear[3]);
        glClear(GL_COLOR_BUFFER_BIT | GL_STENCIL_BUFFER_BIT);
        return true;
    }

    if (!device) return false;
    /* A frame left open (an exception mid-draw) is sent as it is. */
    if (frame.cmd) {
        SDL_SubmitGPUCommandBuffer(frame.cmd);
        if (frame.offscreen) SDL_ReleaseGPUTexture(device, frame.offscreen);
    }
    memset(&frame, 0, sizeof frame);
    frame.cmd = SDL_AcquireGPUCommandBuffer(device);
    if (!frame.cmd) return false;
    frame.width = width; frame.height = height;
    frame.clear = true;
    frame.color = (SDL_FColor) {clear[0], clear[1], clear[2], clear[3]};
    if (window) {
        Uint32 w, h;
        if (!SDL_WaitAndAcquireGPUSwapchainTexture(frame.cmd, window, &frame.target, &w, &h)) frame.target = NULL;
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
    if (!phpsdl_gpu) {
        if (window) { SDL_GL_SwapWindow(window); return true; }
        if (!capture_fbo) return false;
        int w = capture_w, h = capture_h;
        size_t row = (size_t) w * 4;
        unsigned char *pixels = emalloc(row * h), *tmp = emalloc(row);
        glPixelStorei(GL_PACK_ALIGNMENT, 1);
        glReadPixels(0, 0, w, h, GL_RGBA, GL_UNSIGNED_BYTE, pixels);
        glPixelStorei(GL_PACK_ALIGNMENT, 4);
        /* OpenGL's rows start at the bottom. */
        for (int y = 0; y < h / 2; y++) {
            memcpy(tmp, pixels + y * row, row);
            memcpy(pixels + y * row, pixels + (h - 1 - y) * row, row);
            memcpy(pixels + (h - 1 - y) * row, tmp, row);
        }
        bool ok = png ? save_png(png, w, h, pixels) : true;
        efree(tmp);
        efree(pixels);
        glBindFramebuffer(GL_FRAMEBUFFER, 0);
        glDeleteFramebuffers(1, &capture_fbo);
        glDeleteRenderbuffers(2, capture_rbo);
        capture_fbo = 0;
        return ok;
    }

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
    /* GL objects stay: the shared context may already be gone, and they are few. */
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

static const float NO_CLIP[4] = {-1e6f, -1e6f, 2e6f, 2e6f};

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
    HashTable *box, *radii, *clip = NULL;
    ZEND_PARSE_PARAMETERS_START(7, 9)
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
    ZEND_PARSE_PARAMETERS_END();
    pr_renderer *r = renderer_of(ZEND_THIS);
    if (!renderer_init(r)) return;
    pr_image *img = image_load(path);
    if (!img) return;
    float *p = instance(r, img->tex);
    float rect[4] = {(float) x, (float) y, (float) w, (float) h}, v[4];
    float uv[4] = {0, 0, 1, 1};
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

void phpsdl_render_register(zend_class_entry *renderer_ce)
{
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
    if (scratch.glyphs) { pefree(scratch.glyphs, 1); scratch.glyphs = NULL; scratch.cap = 0; }
}
