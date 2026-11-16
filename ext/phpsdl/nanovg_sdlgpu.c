/*
 * NanoVG on SDL_GPU (Vulkan, Metal, Direct3D 12), for canvases when phpsdl draws with
 * SDL_GPU. A port of PHP-GLFW's nanovg_gl.h (GL3): calls are recorded the same way,
 * and at flush every GL step becomes a draw with the stencil state GL would have had,
 * so fills (non-zero and even-odd), strokes, stencil strokes and clips (nvgClip, with
 * one saved level) work as there. SDL_GPU has no triangle fans and no stencil clears
 * inside a pass: fans and strips become index lists, and clears are full-view quads.
 */

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include <php.h>
#include <stdlib.h>
#include <string.h>
#include <math.h>
#include <SDL3/SDL.h>

#include "nanovg.h"
#include "nanovg_sdlgpu.h"
#include "render.h"
#include "shaders/nanovg_shaders.h"

enum { CALL_NONE = 0, CALL_FILL, CALL_CONVEXFILL, CALL_STROKE, CALL_TRIANGLES, CALL_CLIP, CALL_RESETCLIP, CALL_SAVECLIP, CALL_RESTORECLIP };
enum { SHADER_FILLGRAD, SHADER_FILLIMG, SHADER_SIMPLE, SHADER_IMG };
enum { MODE_FAN, MODE_STRIP, MODE_TRIANGLES };

/* Stencil bits, as in nanovg_gl.h: the clip, one saved clip, and winding below. */
#define CLIP_BIT 0x80
#define CLIPSAVE_BIT 0x40
#define WIND_MASK 0x3f

typedef struct {
    SDL_GPUBlendFactor src_rgb, dst_rgb, src_alpha, dst_alpha;
} gn_blend;

typedef struct {
    int type, evenodd, image;
    int pathOffset, pathCount;
    int triangleOffset, triangleCount;
    int uniformOffset; /* an index into uniforms */
    gn_blend blend;
} gn_call;

typedef struct {
    int fillOffset, fillCount, strokeOffset, strokeCount;
} gn_path;

typedef union {
    struct {
        float scissorMat[12];
        float paintMat[12];
        NVGcolor innerCol;
        NVGcolor outerCol;
        float scissorExt[2];
        float scissorScale[2];
        float extent[2];
        float radius, feather, strokeMult, strokeThr, texType, type;
    };
    float array[11][4];
} gn_frag;

typedef struct {
    int id;
    SDL_GPUTexture *tex;
    int width, height, type, flags, levels;
} gn_texture;

/* The fixed-function state a draw is made with, like GL's: part of a pipeline. */
typedef struct {
    Uint8 color, stencil, cull;
    Uint8 compare, read_mask, write_mask;
    Uint8 front_fail, front_pass, back_fail, back_pass;
    Uint8 blend[4];
} gn_state;

typedef struct {
    gn_state state;
    SDL_GPUTextureFormat format;
    SDL_GPUGraphicsPipeline *pipeline;
} gn_pipeline;

typedef struct {
    int pipeline;
    Uint8 ref;
    int uniform, image;
    Uint32 first, count;
} gn_draw;

typedef struct {
    SDL_GPUDevice *device;
    SDL_GPUShader *vs, *fs;
    gn_pipeline *pipelines;
    int npipelines, cpipelines;
    SDL_GPUSampler *samplers[16];
    gn_texture *textures;
    int ntextures, ctextures, textureId;
    int dummyTex;
    float view[2], ratio;
    int flags;
    int clipActive, clipSaveActive;

    gn_call *calls;
    int ccalls, ncalls;
    gn_path *paths;
    int cpaths, npaths;
    NVGvertex *verts;
    int cverts, nverts;
    gn_frag *uniforms;
    int cuniforms, nuniforms;

    /* Built at flush. */
    gn_state st;
    Uint8 ref;
    Uint32 *indices;
    int nindices, cindices;
    gn_draw *draws;
    int ndraws, cdraws;
    int quad, simple; /* the full-view quad and a plain uniform, for stencil clears */

    SDL_GPUBuffer *vbuf, *ibuf;
    SDL_GPUTransferBuffer *upload;
    Uint32 vbuf_size, ibuf_size, upload_size;
} gn_context;

static int gn__maxi(int a, int b) { return a > b ? a : b; }

/* Growing arrays ------------------------------------------------------------ */

static int gn__grow(void **items, int *cap, int need, size_t size, int minimum)
{
    if (need <= *cap) return 1;
    int c = gn__maxi(need, minimum) + *cap / 2;
    void *p = realloc(*items, size * (size_t) c);
    if (!p) return 0;
    *items = p;
    *cap = c;
    return 1;
}

static gn_call *gn__allocCall(gn_context *gl)
{
    if (!gn__grow((void **) &gl->calls, &gl->ccalls, gl->ncalls + 1, sizeof(gn_call), 128)) return NULL;
    gn_call *ret = &gl->calls[gl->ncalls++];
    memset(ret, 0, sizeof *ret);
    return ret;
}

static int gn__allocPaths(gn_context *gl, int n)
{
    if (!gn__grow((void **) &gl->paths, &gl->cpaths, gl->npaths + n, sizeof(gn_path), 128)) return -1;
    int ret = gl->npaths;
    gl->npaths += n;
    return ret;
}

static int gn__allocVerts(gn_context *gl, int n)
{
    if (!gn__grow((void **) &gl->verts, &gl->cverts, gl->nverts + n, sizeof(NVGvertex), 4096)) return -1;
    int ret = gl->nverts;
    gl->nverts += n;
    return ret;
}

static int gn__allocFragUniforms(gn_context *gl, int n)
{
    if (!gn__grow((void **) &gl->uniforms, &gl->cuniforms, gl->nuniforms + n, sizeof(gn_frag), 128)) return -1;
    int ret = gl->nuniforms;
    gl->nuniforms += n;
    return ret;
}

static void gn__vset(NVGvertex *vtx, float x, float y, float u, float v)
{
    vtx->x = x; vtx->y = y; vtx->u = u; vtx->v = v;
}

/* Textures ------------------------------------------------------------------- */

static gn_texture *gn__allocTexture(gn_context *gl)
{
    gn_texture *tex = NULL;
    for (int i = 0; i < gl->ntextures; i++) {
        if (gl->textures[i].id == 0) { tex = &gl->textures[i]; break; }
    }
    if (!tex) {
        if (!gn__grow((void **) &gl->textures, &gl->ctextures, gl->ntextures + 1, sizeof(gn_texture), 4)) return NULL;
        tex = &gl->textures[gl->ntextures++];
    }
    memset(tex, 0, sizeof *tex);
    tex->id = ++gl->textureId;
    return tex;
}

static gn_texture *gn__findTexture(gn_context *gl, int id)
{
    for (int i = 0; i < gl->ntextures; i++) if (gl->textures[i].id == id) return &gl->textures[i];
    return NULL;
}

/* Rows $y..$y+$h, columns $x..$x+$w of $data (the whole image) into the texture, on the
 * frame's command buffer when one is open, so it lands between the frame's passes. */
static int gn__upload(gn_context *gl, gn_texture *tex, int x, int y, int w, int h, const unsigned char *data)
{
    int bpp = tex->type == NVG_TEXTURE_RGBA ? 4 : 1;
    Uint32 row = (Uint32) (w * bpp), size = row * (Uint32) h;
    if (size == 0) return 1;
    SDL_GPUTransferBufferCreateInfo b = {SDL_GPU_TRANSFERBUFFERUSAGE_UPLOAD, size, 0};
    SDL_GPUTransferBuffer *upload = SDL_CreateGPUTransferBuffer(gl->device, &b);
    if (!upload) return 0;
    unsigned char *map = SDL_MapGPUTransferBuffer(gl->device, upload, false);
    if (!map) { SDL_ReleaseGPUTransferBuffer(gl->device, upload); return 0; }
    for (int r = 0; r < h; r++) {
        if (data) memcpy(map + (size_t) r * row, data + ((size_t) (y + r) * tex->width + x) * bpp, row);
        else memset(map + (size_t) r * row, 0, row);
    }
    SDL_UnmapGPUTransferBuffer(gl->device, upload);
    SDL_GPUCommandBuffer *frame = phpsdl_gpu_frame_cmd();
    SDL_GPUCommandBuffer *cmd = frame ? frame : SDL_AcquireGPUCommandBuffer(gl->device);
    if (!cmd) { SDL_ReleaseGPUTransferBuffer(gl->device, upload); return 0; }
    SDL_GPUCopyPass *copy = SDL_BeginGPUCopyPass(cmd);
    SDL_GPUTextureTransferInfo src = {upload, 0, (Uint32) w, (Uint32) h};
    SDL_GPUTextureRegion dst = {0};
    dst.texture = tex->tex;
    dst.x = (Uint32) x; dst.y = (Uint32) y; dst.w = (Uint32) w; dst.h = (Uint32) h; dst.d = 1;
    SDL_UploadToGPUTexture(copy, &src, &dst, false);
    SDL_EndGPUCopyPass(copy);
    if (tex->levels > 1) SDL_GenerateMipmapsForGPUTexture(cmd, tex->tex);
    if (!frame) SDL_SubmitGPUCommandBuffer(cmd);
    SDL_ReleaseGPUTransferBuffer(gl->device, upload);
    return 1;
}

static int gn__renderCreateTexture(void *uptr, int type, int w, int h, int imageFlags, const unsigned char *data)
{
    gn_context *gl = (gn_context *) uptr;
    gn_texture *tex = gn__allocTexture(gl);
    if (!tex) return 0;
    tex->width = w; tex->height = h; tex->type = type; tex->flags = imageFlags;
    tex->levels = 1;
    if (imageFlags & NVG_IMAGE_GENERATE_MIPMAPS) for (int s = w > h ? w : h; s > 1; s >>= 1) tex->levels++;
    SDL_GPUTextureCreateInfo t = {0};
    t.type = SDL_GPU_TEXTURETYPE_2D;
    t.format = type == NVG_TEXTURE_RGBA ? SDL_GPU_TEXTUREFORMAT_R8G8B8A8_UNORM : SDL_GPU_TEXTUREFORMAT_R8_UNORM;
    t.usage = SDL_GPU_TEXTUREUSAGE_SAMPLER | (tex->levels > 1 ? SDL_GPU_TEXTUREUSAGE_COLOR_TARGET : 0);
    t.width = (Uint32) w; t.height = (Uint32) h;
    t.layer_count_or_depth = 1;
    t.num_levels = (Uint32) tex->levels;
    tex->tex = SDL_CreateGPUTexture(gl->device, &t);
    if (!tex->tex) { tex->id = 0; return 0; }
    gn__upload(gl, tex, 0, 0, w, h, data);
    return tex->id;
}

static int gn__renderDeleteTexture(void *uptr, int image)
{
    gn_context *gl = (gn_context *) uptr;
    gn_texture *tex = gn__findTexture(gl, image);
    if (!tex) return 0;
    if (tex->tex && phpsdl_gpu_device() == gl->device) SDL_ReleaseGPUTexture(gl->device, tex->tex);
    memset(tex, 0, sizeof *tex);
    return 1;
}

static int gn__renderUpdateTexture(void *uptr, int image, int x, int y, int w, int h, const unsigned char *data)
{
    gn_context *gl = (gn_context *) uptr;
    gn_texture *tex = gn__findTexture(gl, image);
    if (!tex) return 0;
    return gn__upload(gl, tex, x, y, w, h, data);
}

static int gn__renderGetTextureSize(void *uptr, int image, int *w, int *h)
{
    gn_texture *tex = gn__findTexture((gn_context *) uptr, image);
    if (!tex) return 0;
    *w = tex->width;
    *h = tex->height;
    return 1;
}

static SDL_GPUSampler *gn__sampler(gn_context *gl, int flags, int levels)
{
    int i = ((flags & NVG_IMAGE_NEAREST) ? 1 : 0) | ((flags & NVG_IMAGE_REPEATX) ? 2 : 0)
        | ((flags & NVG_IMAGE_REPEATY) ? 4 : 0) | (levels > 1 ? 8 : 0);
    if (gl->samplers[i]) return gl->samplers[i];
    SDL_GPUSamplerCreateInfo s = {0};
    SDL_GPUFilter filter = (flags & NVG_IMAGE_NEAREST) ? SDL_GPU_FILTER_NEAREST : SDL_GPU_FILTER_LINEAR;
    s.min_filter = s.mag_filter = filter;
    s.mipmap_mode = (flags & NVG_IMAGE_NEAREST) ? SDL_GPU_SAMPLERMIPMAPMODE_NEAREST : SDL_GPU_SAMPLERMIPMAPMODE_LINEAR;
    /* GL clamps to a transparent border; SDL_GPU has no border, the edge is close. */
    s.address_mode_u = (flags & NVG_IMAGE_REPEATX) ? SDL_GPU_SAMPLERADDRESSMODE_REPEAT : SDL_GPU_SAMPLERADDRESSMODE_CLAMP_TO_EDGE;
    s.address_mode_v = (flags & NVG_IMAGE_REPEATY) ? SDL_GPU_SAMPLERADDRESSMODE_REPEAT : SDL_GPU_SAMPLERADDRESSMODE_CLAMP_TO_EDGE;
    s.address_mode_w = SDL_GPU_SAMPLERADDRESSMODE_CLAMP_TO_EDGE;
    s.max_lod = levels > 1 ? 1000.0f : 0.0f;
    gl->samplers[i] = SDL_CreateGPUSampler(gl->device, &s);
    return gl->samplers[i];
}

/* Paint ------------------------------------------------------------------------ */

static void gn__xformToMat3x4(float *m3, const float *t)
{
    m3[0] = t[0]; m3[1] = t[1]; m3[2] = 0.0f; m3[3] = 0.0f;
    m3[4] = t[2]; m3[5] = t[3]; m3[6] = 0.0f; m3[7] = 0.0f;
    m3[8] = t[4]; m3[9] = t[5]; m3[10] = 1.0f; m3[11] = 0.0f;
}

static NVGcolor gn__premulColor(NVGcolor c)
{
    c.r *= c.a; c.g *= c.a; c.b *= c.a;
    return c;
}

static int gn__convertPaint(gn_context *gl, gn_frag *frag, NVGpaint *paint, NVGscissor *scissor, float width, float fringe, float strokeThr)
{
    float invxform[6];
    memset(frag, 0, sizeof *frag);
    frag->innerCol = gn__premulColor(paint->innerColor);
    frag->outerCol = gn__premulColor(paint->outerColor);
    if (scissor->extent[0] < -0.5f || scissor->extent[1] < -0.5f) {
        memset(frag->scissorMat, 0, sizeof frag->scissorMat);
        frag->scissorExt[0] = frag->scissorExt[1] = 1.0f;
        frag->scissorScale[0] = frag->scissorScale[1] = 1.0f;
    } else {
        nvgTransformInverse(invxform, scissor->xform);
        gn__xformToMat3x4(frag->scissorMat, invxform);
        frag->scissorExt[0] = scissor->extent[0];
        frag->scissorExt[1] = scissor->extent[1];
        frag->scissorScale[0] = sqrtf(scissor->xform[0] * scissor->xform[0] + scissor->xform[2] * scissor->xform[2]) / fringe;
        frag->scissorScale[1] = sqrtf(scissor->xform[1] * scissor->xform[1] + scissor->xform[3] * scissor->xform[3]) / fringe;
    }
    memcpy(frag->extent, paint->extent, sizeof frag->extent);
    /* The shader always has edge antialiasing; without it, the stroke mask is 1. */
    frag->strokeMult = (gl->flags & NVG_ANTIALIAS) ? (width * 0.5f + fringe * 0.5f) / fringe : 1e6f;
    frag->strokeThr = strokeThr;
    if (paint->image != 0) {
        gn_texture *tex = gn__findTexture(gl, paint->image);
        if (!tex) return 0;
        if (tex->flags & NVG_IMAGE_FLIPY) {
            float m1[6], m2[6];
            nvgTransformTranslate(m1, 0.0f, frag->extent[1] * 0.5f);
            nvgTransformMultiply(m1, paint->xform);
            nvgTransformScale(m2, 1.0f, -1.0f);
            nvgTransformMultiply(m2, m1);
            nvgTransformTranslate(m1, 0.0f, -frag->extent[1] * 0.5f);
            nvgTransformMultiply(m1, m2);
            nvgTransformInverse(invxform, m1);
        } else {
            nvgTransformInverse(invxform, paint->xform);
        }
        frag->type = SHADER_FILLIMG;
        if (tex->type == NVG_TEXTURE_RGBA) frag->texType = (tex->flags & NVG_IMAGE_PREMULTIPLIED) ? 0.0f : 1.0f;
        else frag->texType = 2.0f;
    } else {
        frag->type = SHADER_FILLGRAD;
        frag->radius = paint->radius;
        frag->feather = paint->feather;
        nvgTransformInverse(invxform, paint->xform);
    }
    gn__xformToMat3x4(frag->paintMat, invxform);
    return 1;
}

static SDL_GPUBlendFactor gn__blendFactor(int factor)
{
    switch (factor) {
        case NVG_ZERO: return SDL_GPU_BLENDFACTOR_ZERO;
        case NVG_ONE: return SDL_GPU_BLENDFACTOR_ONE;
        case NVG_SRC_COLOR: return SDL_GPU_BLENDFACTOR_SRC_COLOR;
        case NVG_ONE_MINUS_SRC_COLOR: return SDL_GPU_BLENDFACTOR_ONE_MINUS_SRC_COLOR;
        case NVG_DST_COLOR: return SDL_GPU_BLENDFACTOR_DST_COLOR;
        case NVG_ONE_MINUS_DST_COLOR: return SDL_GPU_BLENDFACTOR_ONE_MINUS_DST_COLOR;
        case NVG_SRC_ALPHA: return SDL_GPU_BLENDFACTOR_SRC_ALPHA;
        case NVG_ONE_MINUS_SRC_ALPHA: return SDL_GPU_BLENDFACTOR_ONE_MINUS_SRC_ALPHA;
        case NVG_DST_ALPHA: return SDL_GPU_BLENDFACTOR_DST_ALPHA;
        case NVG_ONE_MINUS_DST_ALPHA: return SDL_GPU_BLENDFACTOR_ONE_MINUS_DST_ALPHA;
        case NVG_SRC_ALPHA_SATURATE: return SDL_GPU_BLENDFACTOR_SRC_ALPHA_SATURATE;
    }
    return SDL_GPU_BLENDFACTOR_INVALID;
}

static gn_blend gn__blendCompositeOperation(NVGcompositeOperationState op)
{
    gn_blend b = {gn__blendFactor(op.srcRGB), gn__blendFactor(op.dstRGB), gn__blendFactor(op.srcAlpha), gn__blendFactor(op.dstAlpha)};
    if (b.src_rgb == SDL_GPU_BLENDFACTOR_INVALID || b.dst_rgb == SDL_GPU_BLENDFACTOR_INVALID
        || b.src_alpha == SDL_GPU_BLENDFACTOR_INVALID || b.dst_alpha == SDL_GPU_BLENDFACTOR_INVALID) {
        b.src_rgb = b.src_alpha = SDL_GPU_BLENDFACTOR_ONE;
        b.dst_rgb = b.dst_alpha = SDL_GPU_BLENDFACTOR_ONE_MINUS_SRC_ALPHA;
    }
    return b;
}

/* GL's state, kept while a flush is planned -------------------------------------- */

static void st_func(gn_context *gl, SDL_GPUCompareOp compare, Uint8 ref, Uint8 mask)
{
    gl->st.compare = (Uint8) compare;
    gl->ref = ref;
    gl->st.read_mask = mask;
}

static void st_op(gn_context *gl, SDL_GPUStencilOp fail, SDL_GPUStencilOp pass)
{
    gl->st.front_fail = gl->st.back_fail = (Uint8) fail;
    gl->st.front_pass = gl->st.back_pass = (Uint8) pass;
}

static void st_op_separate(gn_context *gl, SDL_GPUStencilOp front_pass, SDL_GPUStencilOp back_pass)
{
    gl->st.front_fail = gl->st.back_fail = (Uint8) SDL_GPU_STENCILOP_KEEP;
    gl->st.front_pass = (Uint8) front_pass;
    gl->st.back_pass = (Uint8) back_pass;
}

static int gn__pipeline(gn_context *gl, SDL_GPUTextureFormat format)
{
    gn_state key;
    memset(&key, 0, sizeof key);
    key = gl->st;
    if (!key.stencil) {
        /* Stencil settings mean nothing without the test: one pipeline for them all. */
        key.compare = key.read_mask = key.write_mask = 0;
        key.front_fail = key.front_pass = key.back_fail = key.back_pass = 0;
    }
    for (int i = 0; i < gl->npipelines; i++) {
        if (gl->pipelines[i].format == format && memcmp(&gl->pipelines[i].state, &key, sizeof key) == 0) return i;
    }
    SDL_GPUVertexBufferDescription buffer = {0, sizeof(NVGvertex), SDL_GPU_VERTEXINPUTRATE_VERTEX, 0};
    SDL_GPUVertexAttribute attributes[2] = {
        {0, 0, SDL_GPU_VERTEXELEMENTFORMAT_FLOAT2, 0},
        {1, 0, SDL_GPU_VERTEXELEMENTFORMAT_FLOAT2, 2 * sizeof(float)},
    };
    SDL_GPUColorTargetDescription target = {0};
    target.format = format;
    target.blend_state.enable_blend = true;
    target.blend_state.src_color_blendfactor = (SDL_GPUBlendFactor) key.blend[0];
    target.blend_state.dst_color_blendfactor = (SDL_GPUBlendFactor) key.blend[1];
    target.blend_state.src_alpha_blendfactor = (SDL_GPUBlendFactor) key.blend[2];
    target.blend_state.dst_alpha_blendfactor = (SDL_GPUBlendFactor) key.blend[3];
    target.blend_state.color_blend_op = target.blend_state.alpha_blend_op = SDL_GPU_BLENDOP_ADD;
    if (!key.color) {
        target.blend_state.enable_color_write_mask = true;
        target.blend_state.color_write_mask = 0;
    }
    SDL_GPUGraphicsPipelineCreateInfo info = {0};
    info.vertex_shader = gl->vs;
    info.fragment_shader = gl->fs;
    info.vertex_input_state.vertex_buffer_descriptions = &buffer;
    info.vertex_input_state.num_vertex_buffers = 1;
    info.vertex_input_state.vertex_attributes = attributes;
    info.vertex_input_state.num_vertex_attributes = 2;
    info.primitive_type = SDL_GPU_PRIMITIVETYPE_TRIANGLELIST;
    info.rasterizer_state.fill_mode = SDL_GPU_FILLMODE_FILL;
    info.rasterizer_state.cull_mode = key.cull ? SDL_GPU_CULLMODE_BACK : SDL_GPU_CULLMODE_NONE;
    info.rasterizer_state.front_face = SDL_GPU_FRONTFACE_COUNTER_CLOCKWISE;
    info.depth_stencil_state.enable_stencil_test = key.stencil != 0;
    info.depth_stencil_state.compare_op = SDL_GPU_COMPAREOP_ALWAYS;
    info.depth_stencil_state.front_stencil_state.fail_op = (SDL_GPUStencilOp) key.front_fail;
    info.depth_stencil_state.front_stencil_state.pass_op = (SDL_GPUStencilOp) key.front_pass;
    info.depth_stencil_state.front_stencil_state.depth_fail_op = SDL_GPU_STENCILOP_KEEP;
    info.depth_stencil_state.front_stencil_state.compare_op = (SDL_GPUCompareOp) key.compare;
    info.depth_stencil_state.back_stencil_state.fail_op = (SDL_GPUStencilOp) key.back_fail;
    info.depth_stencil_state.back_stencil_state.pass_op = (SDL_GPUStencilOp) key.back_pass;
    info.depth_stencil_state.back_stencil_state.depth_fail_op = SDL_GPU_STENCILOP_KEEP;
    info.depth_stencil_state.back_stencil_state.compare_op = (SDL_GPUCompareOp) key.compare;
    info.depth_stencil_state.compare_mask = key.read_mask;
    info.depth_stencil_state.write_mask = key.write_mask;
    info.target_info.color_target_descriptions = &target;
    info.target_info.num_color_targets = 1;
    info.target_info.has_depth_stencil_target = true;
    info.target_info.depth_stencil_format = phpsdl_gpu_stencil_format();
    SDL_GPUGraphicsPipeline *pipeline = SDL_CreateGPUGraphicsPipeline(gl->device, &info);
    if (!pipeline) return -1;
    if (!gn__grow((void **) &gl->pipelines, &gl->cpipelines, gl->npipelines + 1, sizeof(gn_pipeline), 16)) {
        SDL_ReleaseGPUGraphicsPipeline(gl->device, pipeline);
        return -1;
    }
    gl->pipelines[gl->npipelines].state = key;
    gl->pipelines[gl->npipelines].format = format;
    gl->pipelines[gl->npipelines].pipeline = pipeline;
    return gl->npipelines++;
}

/* glDrawArrays, as indices into the vertex buffer and a draw with the current state. */
static void gn__draw(gn_context *gl, int mode, int first, int count, int uniform, int image)
{
    int triangles = mode == MODE_TRIANGLES ? count / 3 : count - 2;
    if (triangles <= 0) return;
    int pipeline = gn__pipeline(gl, phpsdl_gpu_frame_format());
    if (pipeline < 0) return;
    if (!gn__grow((void **) &gl->indices, &gl->cindices, gl->nindices + triangles * 3, sizeof(Uint32), 4096)) return;
    if (!gn__grow((void **) &gl->draws, &gl->cdraws, gl->ndraws + 1, sizeof(gn_draw), 128)) return;
    Uint32 *idx = gl->indices + gl->nindices;
    Uint32 f = (Uint32) first;
    for (int i = 0; i < triangles; i++) {
        Uint32 a, b, c;
        if (mode == MODE_FAN) { a = f; b = f + i + 1; c = f + i + 2; }
        else if (mode == MODE_STRIP) {
            /* GL keeps a strip's winding: odd triangles swap their first two vertices. */
            if (i & 1) { a = f + i + 1; b = f + i; } else { a = f + i; b = f + i + 1; }
            c = f + i + 2;
        } else { a = f + i * 3; b = a + 1; c = a + 2; }
        *idx++ = a; *idx++ = b; *idx++ = c;
    }
    gn_draw *d = &gl->draws[gl->ndraws++];
    d->pipeline = pipeline;
    d->ref = gl->ref;
    d->uniform = uniform;
    d->image = image;
    d->first = (Uint32) gl->nindices;
    d->count = (Uint32) triangles * 3;
    gl->nindices += triangles * 3;
}

/* glClear(GL_STENCIL_BUFFER_BIT) under stencil mask $mask: zero those bits everywhere. */
static void gn__clearStencil(gn_context *gl, Uint8 mask)
{
    gn_state saved = gl->st;
    Uint8 ref = gl->ref;
    gl->st.stencil = 1; gl->st.color = 0; gl->st.cull = 0;
    gl->st.write_mask = mask;
    st_func(gl, SDL_GPU_COMPAREOP_ALWAYS, 0, 0xff);
    st_op(gl, SDL_GPU_STENCILOP_ZERO, SDL_GPU_STENCILOP_ZERO);
    gn__draw(gl, MODE_STRIP, gl->quad, 4, gl->simple, 0);
    gl->st = saved;
    gl->ref = ref;
}

/* The GL backend's steps -------------------------------------------------------- */

static void gn__fill(gn_context *gl, gn_call *call)
{
    gn_path *paths = &gl->paths[call->pathOffset];
    int npaths = call->pathCount;

    gl->st.stencil = 1;
    gl->st.write_mask = WIND_MASK;
    if (gl->clipActive) st_func(gl, SDL_GPU_COMPAREOP_EQUAL, CLIP_BIT, CLIP_BIT);
    else st_func(gl, SDL_GPU_COMPAREOP_ALWAYS, 0, WIND_MASK);
    gl->st.color = 0;
    if (call->evenodd) st_op(gl, SDL_GPU_STENCILOP_KEEP, SDL_GPU_STENCILOP_INVERT);
    else st_op_separate(gl, SDL_GPU_STENCILOP_INCREMENT_AND_WRAP, SDL_GPU_STENCILOP_DECREMENT_AND_WRAP);
    gl->st.cull = 0;
    for (int i = 0; i < npaths; i++) gn__draw(gl, MODE_FAN, paths[i].fillOffset, paths[i].fillCount, call->uniformOffset, 0);
    gl->st.cull = 1;

    gl->st.color = 1;
    if (gl->flags & NVG_ANTIALIAS) {
        if (gl->clipActive) st_func(gl, SDL_GPU_COMPAREOP_EQUAL, CLIP_BIT, CLIP_BIT | WIND_MASK);
        else st_func(gl, SDL_GPU_COMPAREOP_EQUAL, 0x00, WIND_MASK);
        st_op(gl, SDL_GPU_STENCILOP_KEEP, SDL_GPU_STENCILOP_KEEP);
        for (int i = 0; i < npaths; i++) gn__draw(gl, MODE_STRIP, paths[i].strokeOffset, paths[i].strokeCount, call->uniformOffset + 1, call->image);
    }

    st_func(gl, SDL_GPU_COMPAREOP_NOT_EQUAL, 0x0, WIND_MASK);
    st_op(gl, SDL_GPU_STENCILOP_ZERO, SDL_GPU_STENCILOP_ZERO);
    gn__draw(gl, MODE_STRIP, call->triangleOffset, call->triangleCount, call->uniformOffset + 1, call->image);
    gl->st.stencil = 0;
}

/* Strokes, triangles and convex fills: confined to the clip when there is one. */
static void gn__gateOnClip(gn_context *gl)
{
    if (!gl->clipActive) return;
    gl->st.stencil = 1;
    gl->st.write_mask = WIND_MASK;
    st_func(gl, SDL_GPU_COMPAREOP_EQUAL, CLIP_BIT, CLIP_BIT);
    st_op(gl, SDL_GPU_STENCILOP_KEEP, SDL_GPU_STENCILOP_KEEP);
}

static void gn__convexFill(gn_context *gl, gn_call *call)
{
    gn_path *paths = &gl->paths[call->pathOffset];
    gn__gateOnClip(gl);
    for (int i = 0; i < call->pathCount; i++) {
        gn__draw(gl, MODE_FAN, paths[i].fillOffset, paths[i].fillCount, call->uniformOffset, call->image);
        if (paths[i].strokeCount > 0) gn__draw(gl, MODE_STRIP, paths[i].strokeOffset, paths[i].strokeCount, call->uniformOffset, call->image);
    }
    gl->st.stencil = 0;
}

static void gn__clip(gn_context *gl, gn_call *call)
{
    gn_path *paths = &gl->paths[call->pathOffset];
    gn__clearStencil(gl, CLIP_BIT);
    gl->st.stencil = 1;
    gl->st.color = 0;
    gl->st.write_mask = WIND_MASK;
    st_func(gl, SDL_GPU_COMPAREOP_ALWAYS, 0, WIND_MASK);
    st_op_separate(gl, SDL_GPU_STENCILOP_INCREMENT_AND_WRAP, SDL_GPU_STENCILOP_DECREMENT_AND_WRAP);
    gl->st.cull = 0;
    for (int i = 0; i < call->pathCount; i++) gn__draw(gl, MODE_FAN, paths[i].fillOffset, paths[i].fillCount, call->uniformOffset, 0);
    gl->st.cull = 1;
    /* Where the winding is not 0, set the clip bit and clear the winding; else clear all. */
    gl->st.write_mask = 0xff;
    st_func(gl, SDL_GPU_COMPAREOP_NOT_EQUAL, CLIP_BIT, WIND_MASK);
    st_op(gl, SDL_GPU_STENCILOP_ZERO, SDL_GPU_STENCILOP_REPLACE);
    gn__draw(gl, MODE_STRIP, call->triangleOffset, call->triangleCount, call->uniformOffset, 0);
    gl->st.color = 1;
    gl->st.stencil = 0;
    gl->clipActive = 1;
}

static void gn__resetClip(gn_context *gl)
{
    gn__clearStencil(gl, CLIP_BIT);
    gl->clipActive = 0;
}

static void gn__copyClip(gn_context *gl, gn_call *call, Uint8 from, Uint8 to)
{
    gn__clearStencil(gl, to);
    gl->st.stencil = 1;
    gl->st.color = 0;
    gl->st.write_mask = to;
    st_func(gl, SDL_GPU_COMPAREOP_EQUAL, CLIP_BIT | CLIPSAVE_BIT, from);
    st_op(gl, SDL_GPU_STENCILOP_KEEP, SDL_GPU_STENCILOP_REPLACE);
    gn__draw(gl, MODE_STRIP, call->triangleOffset, call->triangleCount, call->uniformOffset, 0);
    gl->st.color = 1;
    gl->st.stencil = 0;
}

static void gn__stroke(gn_context *gl, gn_call *call)
{
    gn_path *paths = &gl->paths[call->pathOffset];
    int npaths = call->pathCount;
    if (gl->flags & NVG_STENCIL_STROKES) {
        Uint8 clipRef = gl->clipActive ? CLIP_BIT : 0x0;
        Uint8 clipMask = gl->clipActive ? (CLIP_BIT | WIND_MASK) : WIND_MASK;
        gl->st.stencil = 1;
        gl->st.write_mask = WIND_MASK;
        /* Fill the stroke base without overlap. */
        st_func(gl, SDL_GPU_COMPAREOP_EQUAL, clipRef, clipMask);
        st_op(gl, SDL_GPU_STENCILOP_KEEP, SDL_GPU_STENCILOP_INCREMENT_AND_CLAMP);
        for (int i = 0; i < npaths; i++) gn__draw(gl, MODE_STRIP, paths[i].strokeOffset, paths[i].strokeCount, call->uniformOffset + 1, call->image);
        /* Antialiased pixels. */
        st_func(gl, SDL_GPU_COMPAREOP_EQUAL, clipRef, clipMask);
        st_op(gl, SDL_GPU_STENCILOP_KEEP, SDL_GPU_STENCILOP_KEEP);
        for (int i = 0; i < npaths; i++) gn__draw(gl, MODE_STRIP, paths[i].strokeOffset, paths[i].strokeCount, call->uniformOffset, call->image);
        /* Clear the stencil. */
        gl->st.color = 0;
        st_func(gl, SDL_GPU_COMPAREOP_ALWAYS, 0x0, WIND_MASK);
        st_op(gl, SDL_GPU_STENCILOP_ZERO, SDL_GPU_STENCILOP_ZERO);
        for (int i = 0; i < npaths; i++) gn__draw(gl, MODE_STRIP, paths[i].strokeOffset, paths[i].strokeCount, call->uniformOffset, call->image);
        gl->st.color = 1;
        gl->st.stencil = 0;
    } else {
        gn__gateOnClip(gl);
        for (int i = 0; i < npaths; i++) gn__draw(gl, MODE_STRIP, paths[i].strokeOffset, paths[i].strokeCount, call->uniformOffset, call->image);
        gl->st.stencil = 0;
    }
}

static void gn__triangles(gn_context *gl, gn_call *call)
{
    gn__gateOnClip(gl);
    gn__draw(gl, MODE_TRIANGLES, call->triangleOffset, call->triangleCount, call->uniformOffset, call->image);
    gl->st.stencil = 0;
}

/* NanoVG's renderer interface --------------------------------------------------- */

static void gn__renderViewport(void *uptr, float width, float height, float devicePixelRatio)
{
    gn_context *gl = (gn_context *) uptr;
    gl->view[0] = width;
    gl->view[1] = height;
    gl->ratio = devicePixelRatio > 0 ? devicePixelRatio : 1;
}

static void gn__renderCancel(void *uptr)
{
    gn_context *gl = (gn_context *) uptr;
    gl->nverts = gl->npaths = gl->ncalls = gl->nuniforms = 0;
    gl->clipActive = 0;
}

static int gn__ensureBuffers(gn_context *gl, Uint32 vbytes, Uint32 ibytes)
{
    if (vbytes > gl->vbuf_size) {
        Uint32 size = gl->vbuf_size ? gl->vbuf_size : 64 * 1024;
        while (size < vbytes) size *= 2;
        if (gl->vbuf) SDL_ReleaseGPUBuffer(gl->device, gl->vbuf);
        SDL_GPUBufferCreateInfo b = {SDL_GPU_BUFFERUSAGE_VERTEX, size, 0};
        gl->vbuf = SDL_CreateGPUBuffer(gl->device, &b);
        gl->vbuf_size = gl->vbuf ? size : 0;
    }
    if (ibytes > gl->ibuf_size) {
        Uint32 size = gl->ibuf_size ? gl->ibuf_size : 64 * 1024;
        while (size < ibytes) size *= 2;
        if (gl->ibuf) SDL_ReleaseGPUBuffer(gl->device, gl->ibuf);
        SDL_GPUBufferCreateInfo b = {SDL_GPU_BUFFERUSAGE_INDEX, size, 0};
        gl->ibuf = SDL_CreateGPUBuffer(gl->device, &b);
        gl->ibuf_size = gl->ibuf ? size : 0;
    }
    if (vbytes + ibytes > gl->upload_size) {
        Uint32 size = gl->upload_size ? gl->upload_size : 128 * 1024;
        while (size < vbytes + ibytes) size *= 2;
        if (gl->upload) SDL_ReleaseGPUTransferBuffer(gl->device, gl->upload);
        SDL_GPUTransferBufferCreateInfo t = {SDL_GPU_TRANSFERBUFFERUSAGE_UPLOAD, size, 0};
        gl->upload = SDL_CreateGPUTransferBuffer(gl->device, &t);
        gl->upload_size = gl->upload ? size : 0;
    }
    return gl->vbuf && gl->ibuf && gl->upload;
}

static void gn__renderFlush(void *uptr)
{
    gn_context *gl = (gn_context *) uptr;
    gl->clipActive = 0;
    SDL_GPUCommandBuffer *cmd = phpsdl_gpu_frame_cmd();
    if (gl->ncalls > 0 && cmd && phpsdl_gpu_device() == gl->device) {
        /* The full-view quad and a plain uniform, for stencil clears. */
        gl->quad = gn__allocVerts(gl, 4);
        gl->simple = gn__allocFragUniforms(gl, 1);
        if (gl->quad >= 0 && gl->simple >= 0) {
            NVGvertex *q = &gl->verts[gl->quad];
            gn__vset(&q[0], gl->view[0], gl->view[1], 0.5f, 1.0f);
            gn__vset(&q[1], gl->view[0], 0.0f, 0.5f, 1.0f);
            gn__vset(&q[2], 0.0f, gl->view[1], 0.5f, 1.0f);
            gn__vset(&q[3], 0.0f, 0.0f, 0.5f, 1.0f);
            gn_frag *simple = &gl->uniforms[gl->simple];
            memset(simple, 0, sizeof *simple);
            simple->strokeThr = -1.0f;
            simple->type = SHADER_SIMPLE;

            memset(&gl->st, 0, sizeof gl->st);
            gl->st.color = 1;
            gl->st.cull = 1;
            gl->st.read_mask = gl->st.write_mask = 0xff;
            gl->st.compare = SDL_GPU_COMPAREOP_ALWAYS;
            gl->ref = 0;
            gl->nindices = gl->ndraws = 0;
            for (int i = 0; i < gl->ncalls; i++) {
                gn_call *call = &gl->calls[i];
                gl->st.blend[0] = (Uint8) call->blend.src_rgb;
                gl->st.blend[1] = (Uint8) call->blend.dst_rgb;
                gl->st.blend[2] = (Uint8) call->blend.src_alpha;
                gl->st.blend[3] = (Uint8) call->blend.dst_alpha;
                switch (call->type) {
                    case CALL_FILL: gn__fill(gl, call); break;
                    case CALL_CONVEXFILL: gn__convexFill(gl, call); break;
                    case CALL_STROKE: gn__stroke(gl, call); break;
                    case CALL_TRIANGLES: gn__triangles(gl, call); break;
                    case CALL_CLIP: gn__clip(gl, call); break;
                    case CALL_RESETCLIP: gn__resetClip(gl); break;
                    case CALL_SAVECLIP: gn__copyClip(gl, call, CLIP_BIT, CLIPSAVE_BIT); gl->clipSaveActive = gl->clipActive; break;
                    case CALL_RESTORECLIP: gn__copyClip(gl, call, CLIPSAVE_BIT, CLIP_BIT); gl->clipActive = gl->clipSaveActive; break;
                }
            }

            Uint32 vbytes = (Uint32) (sizeof(NVGvertex) * gl->nverts), ibytes = (Uint32) (sizeof(Uint32) * gl->nindices);
            if (gl->ndraws > 0 && gn__ensureBuffers(gl, vbytes, ibytes)) {
                unsigned char *map = SDL_MapGPUTransferBuffer(gl->device, gl->upload, true);
                if (map) {
                    memcpy(map, gl->verts, vbytes);
                    memcpy(map + vbytes, gl->indices, ibytes);
                    SDL_UnmapGPUTransferBuffer(gl->device, gl->upload);
                    SDL_GPUCopyPass *copy = SDL_BeginGPUCopyPass(cmd);
                    SDL_GPUTransferBufferLocation vsrc = {gl->upload, 0}, isrc = {gl->upload, vbytes};
                    SDL_GPUBufferRegion vdst = {gl->vbuf, 0, vbytes}, idst = {gl->ibuf, 0, ibytes};
                    SDL_UploadToGPUBuffer(copy, &vsrc, &vdst, true);
                    SDL_UploadToGPUBuffer(copy, &isrc, &idst, true);
                    SDL_EndGPUCopyPass(copy);

                    float view[4] = {gl->view[0], gl->view[1], 0, 0};
                    SDL_PushGPUVertexUniformData(cmd, 0, view, sizeof view);
                    SDL_GPURenderPass *pass = phpsdl_gpu_begin_pass(true);
                    if (pass) {
                        SDL_GPUViewport viewport = {0, 0, gl->view[0] * gl->ratio, gl->view[1] * gl->ratio, 0, 1};
                        SDL_SetGPUViewport(pass, &viewport);
                        SDL_GPUBufferBinding vb = {gl->vbuf, 0}, ib = {gl->ibuf, 0};
                        SDL_BindGPUVertexBuffers(pass, 0, &vb, 1);
                        SDL_BindGPUIndexBuffer(pass, &ib, SDL_GPU_INDEXELEMENTSIZE_32BIT);
                        int pipeline = -1, uniform = -1, ref = -1;
                        SDL_GPUTexture *bound = NULL;
                        for (int i = 0; i < gl->ndraws; i++) {
                            gn_draw *d = &gl->draws[i];
                            if (d->pipeline != pipeline) { SDL_BindGPUGraphicsPipeline(pass, gl->pipelines[d->pipeline].pipeline); pipeline = d->pipeline; }
                            if (d->ref != ref) { SDL_SetGPUStencilReference(pass, d->ref); ref = d->ref; }
                            if (d->uniform != uniform) {
                                SDL_PushGPUFragmentUniformData(cmd, 0, &gl->uniforms[d->uniform], sizeof(gn_frag));
                                uniform = d->uniform;
                            }
                            gn_texture *tex = d->image ? gn__findTexture(gl, d->image) : NULL;
                            if (!tex) tex = gn__findTexture(gl, gl->dummyTex);
                            if (tex && tex->tex != bound) {
                                SDL_GPUTextureSamplerBinding b = {tex->tex, gn__sampler(gl, tex->flags, tex->levels)};
                                SDL_BindGPUFragmentSamplers(pass, 0, &b, 1);
                                bound = tex->tex;
                            }
                            SDL_DrawGPUIndexedPrimitives(pass, d->count, 1, d->first, 0, 0);
                        }
                        SDL_EndGPURenderPass(pass);
                    }
                }
            }
        }
    }
    gl->nverts = gl->npaths = gl->ncalls = gl->nuniforms = 0;
}

static int gn__maxVertCount(const NVGpath *paths, int npaths)
{
    int count = 0;
    for (int i = 0; i < npaths; i++) count += paths[i].nfill + paths[i].nstroke;
    return count;
}

static void gn__renderFillEx(void *uptr, NVGpaint *paint, NVGcompositeOperationState op, NVGscissor *scissor, float fringe,
    const float *bounds, const NVGpath *paths, int npaths, int evenodd)
{
    gn_context *gl = (gn_context *) uptr;
    gn_call *call = gn__allocCall(gl);
    if (!call) return;
    call->type = CALL_FILL;
    call->evenodd = evenodd;
    call->triangleCount = 4;
    call->pathOffset = gn__allocPaths(gl, npaths);
    if (call->pathOffset == -1) goto error;
    call->pathCount = npaths;
    call->image = paint->image;
    call->blend = gn__blendCompositeOperation(op);
    if (npaths == 1 && paths[0].convex) {
        call->type = CALL_CONVEXFILL;
        call->triangleCount = 0;
    }
    int offset = gn__allocVerts(gl, gn__maxVertCount(paths, npaths) + call->triangleCount);
    if (offset == -1) goto error;
    for (int i = 0; i < npaths; i++) {
        gn_path *copy = &gl->paths[call->pathOffset + i];
        const NVGpath *path = &paths[i];
        memset(copy, 0, sizeof *copy);
        if (path->nfill > 0) {
            copy->fillOffset = offset;
            copy->fillCount = path->nfill;
            memcpy(&gl->verts[offset], path->fill, sizeof(NVGvertex) * path->nfill);
            offset += path->nfill;
        }
        if (path->nstroke > 0) {
            copy->strokeOffset = offset;
            copy->strokeCount = path->nstroke;
            memcpy(&gl->verts[offset], path->stroke, sizeof(NVGvertex) * path->nstroke);
            offset += path->nstroke;
        }
    }
    if (call->type == CALL_FILL) {
        call->triangleOffset = offset;
        NVGvertex *quad = &gl->verts[offset];
        gn__vset(&quad[0], bounds[2], bounds[3], 0.5f, 1.0f);
        gn__vset(&quad[1], bounds[2], bounds[1], 0.5f, 1.0f);
        gn__vset(&quad[2], bounds[0], bounds[3], 0.5f, 1.0f);
        gn__vset(&quad[3], bounds[0], bounds[1], 0.5f, 1.0f);
        call->uniformOffset = gn__allocFragUniforms(gl, 2);
        if (call->uniformOffset == -1) goto error;
        gn_frag *frag = &gl->uniforms[call->uniformOffset];
        memset(frag, 0, sizeof *frag);
        frag->strokeThr = -1.0f;
        frag->type = SHADER_SIMPLE;
        gn__convertPaint(gl, &gl->uniforms[call->uniformOffset + 1], paint, scissor, fringe, fringe, -1.0f);
    } else {
        call->uniformOffset = gn__allocFragUniforms(gl, 1);
        if (call->uniformOffset == -1) goto error;
        gn__convertPaint(gl, &gl->uniforms[call->uniformOffset], paint, scissor, fringe, fringe, -1.0f);
    }
    return;
error:
    if (gl->ncalls > 0) gl->ncalls--;
}

static void gn__renderFill(void *uptr, NVGpaint *paint, NVGcompositeOperationState op, NVGscissor *scissor, float fringe,
    const float *bounds, const NVGpath *paths, int npaths)
{
    gn__renderFillEx(uptr, paint, op, scissor, fringe, bounds, paths, npaths, 0);
}

static void gn__renderFillEvenOdd(void *uptr, NVGpaint *paint, NVGcompositeOperationState op, NVGscissor *scissor, float fringe,
    const float *bounds, const NVGpath *paths, int npaths)
{
    gn__renderFillEx(uptr, paint, op, scissor, fringe, bounds, paths, npaths, 1);
}

static void gn__renderStroke(void *uptr, NVGpaint *paint, NVGcompositeOperationState op, NVGscissor *scissor, float fringe,
    float strokeWidth, const NVGpath *paths, int npaths)
{
    gn_context *gl = (gn_context *) uptr;
    gn_call *call = gn__allocCall(gl);
    if (!call) return;
    call->type = CALL_STROKE;
    call->pathOffset = gn__allocPaths(gl, npaths);
    if (call->pathOffset == -1) goto error;
    call->pathCount = npaths;
    call->image = paint->image;
    call->blend = gn__blendCompositeOperation(op);
    int offset = gn__allocVerts(gl, gn__maxVertCount(paths, npaths));
    if (offset == -1) goto error;
    for (int i = 0; i < npaths; i++) {
        gn_path *copy = &gl->paths[call->pathOffset + i];
        const NVGpath *path = &paths[i];
        memset(copy, 0, sizeof *copy);
        if (path->nstroke) {
            copy->strokeOffset = offset;
            copy->strokeCount = path->nstroke;
            memcpy(&gl->verts[offset], path->stroke, sizeof(NVGvertex) * path->nstroke);
            offset += path->nstroke;
        }
    }
    if (gl->flags & NVG_STENCIL_STROKES) {
        call->uniformOffset = gn__allocFragUniforms(gl, 2);
        if (call->uniformOffset == -1) goto error;
        gn__convertPaint(gl, &gl->uniforms[call->uniformOffset], paint, scissor, strokeWidth, fringe, -1.0f);
        gn__convertPaint(gl, &gl->uniforms[call->uniformOffset + 1], paint, scissor, strokeWidth, fringe, 1.0f - 0.5f / 255.0f);
    } else {
        call->uniformOffset = gn__allocFragUniforms(gl, 1);
        if (call->uniformOffset == -1) goto error;
        gn__convertPaint(gl, &gl->uniforms[call->uniformOffset], paint, scissor, strokeWidth, fringe, -1.0f);
    }
    return;
error:
    if (gl->ncalls > 0) gl->ncalls--;
}

static void gn__renderTriangles(void *uptr, NVGpaint *paint, NVGcompositeOperationState op, NVGscissor *scissor,
    const NVGvertex *verts, int nverts, float fringe)
{
    gn_context *gl = (gn_context *) uptr;
    gn_call *call = gn__allocCall(gl);
    if (!call) return;
    call->type = CALL_TRIANGLES;
    call->image = paint->image;
    call->blend = gn__blendCompositeOperation(op);
    call->triangleOffset = gn__allocVerts(gl, nverts);
    if (call->triangleOffset == -1) goto error;
    call->triangleCount = nverts;
    memcpy(&gl->verts[call->triangleOffset], verts, sizeof(NVGvertex) * nverts);
    call->uniformOffset = gn__allocFragUniforms(gl, 1);
    if (call->uniformOffset == -1) goto error;
    gn_frag *frag = &gl->uniforms[call->uniformOffset];
    gn__convertPaint(gl, frag, paint, scissor, 1.0f, fringe, -1.0f);
    frag->type = SHADER_IMG;
    return;
error:
    if (gl->ncalls > 0) gl->ncalls--;
}

static void gn__renderClip(void *uptr, NVGscissor *scissor, const float *bounds, const NVGpath *paths, int npaths)
{
    gn_context *gl = (gn_context *) uptr;
    NVG_NOTUSED(scissor);
    gn_call *call = gn__allocCall(gl);
    if (!call) return;
    call->type = CALL_CLIP;
    call->triangleCount = 4;
    call->pathOffset = gn__allocPaths(gl, npaths);
    if (call->pathOffset == -1) goto error;
    call->pathCount = npaths;
    int offset = gn__allocVerts(gl, gn__maxVertCount(paths, npaths) + call->triangleCount);
    if (offset == -1) goto error;
    for (int i = 0; i < npaths; i++) {
        gn_path *copy = &gl->paths[call->pathOffset + i];
        const NVGpath *path = &paths[i];
        memset(copy, 0, sizeof *copy);
        if (path->nfill > 0) {
            copy->fillOffset = offset;
            copy->fillCount = path->nfill;
            memcpy(&gl->verts[offset], path->fill, sizeof(NVGvertex) * path->nfill);
            offset += path->nfill;
        }
    }
    call->triangleOffset = offset;
    NVGvertex *quad = &gl->verts[offset];
    gn__vset(&quad[0], bounds[2], bounds[3], 0.5f, 1.0f);
    gn__vset(&quad[1], bounds[2], bounds[1], 0.5f, 1.0f);
    gn__vset(&quad[2], bounds[0], bounds[3], 0.5f, 1.0f);
    gn__vset(&quad[3], bounds[0], bounds[1], 0.5f, 1.0f);
    call->uniformOffset = gn__allocFragUniforms(gl, 1);
    if (call->uniformOffset == -1) goto error;
    gn_frag *frag = &gl->uniforms[call->uniformOffset];
    memset(frag, 0, sizeof *frag);
    frag->strokeThr = -1.0f;
    frag->type = SHADER_SIMPLE;
    return;
error:
    if (gl->ncalls > 0) gl->ncalls--;
}

static void gn__renderResetClip(void *uptr)
{
    gn_call *call = gn__allocCall((gn_context *) uptr);
    if (call) call->type = CALL_RESETCLIP;
}

static void gn__renderCopyClip(void *uptr, int type)
{
    gn_context *gl = (gn_context *) uptr;
    gn_call *call = gn__allocCall(gl);
    if (!call) return;
    call->type = type;
    call->triangleCount = 4;
    int offset = gn__allocVerts(gl, 4);
    if (offset == -1) goto error;
    call->triangleOffset = offset;
    NVGvertex *quad = &gl->verts[offset];
    gn__vset(&quad[0], gl->view[0], gl->view[1], 0.5f, 1.0f);
    gn__vset(&quad[1], gl->view[0], 0.0f, 0.5f, 1.0f);
    gn__vset(&quad[2], 0.0f, gl->view[1], 0.5f, 1.0f);
    gn__vset(&quad[3], 0.0f, 0.0f, 0.5f, 1.0f);
    call->uniformOffset = gn__allocFragUniforms(gl, 1);
    if (call->uniformOffset == -1) goto error;
    gn_frag *frag = &gl->uniforms[call->uniformOffset];
    memset(frag, 0, sizeof *frag);
    frag->strokeThr = -1.0f;
    frag->type = SHADER_SIMPLE;
    return;
error:
    if (gl->ncalls > 0) gl->ncalls--;
}

static void gn__renderSaveClip(void *uptr) { gn__renderCopyClip(uptr, CALL_SAVECLIP); }
static void gn__renderRestoreClip(void *uptr) { gn__renderCopyClip(uptr, CALL_RESTORECLIP); }

static SDL_GPUShader *gn__shader(SDL_GPUDevice *device, bool vs)
{
    SDL_GPUShaderFormat formats = SDL_GetGPUShaderFormats(device);
    SDL_GPUShaderCreateInfo info = {0};
    info.stage = vs ? SDL_GPU_SHADERSTAGE_VERTEX : SDL_GPU_SHADERSTAGE_FRAGMENT;
    info.num_uniform_buffers = 1;
    info.num_samplers = vs ? 0 : 1;
    if (formats & SDL_GPU_SHADERFORMAT_SPIRV) {
        info.format = SDL_GPU_SHADERFORMAT_SPIRV;
        info.code = vs ? nanovg_vs_spv : nanovg_ps_spv;
        info.code_size = vs ? sizeof nanovg_vs_spv : sizeof nanovg_ps_spv;
        info.entrypoint = vs ? "VSMain" : "PSMain";
    } else if (formats & SDL_GPU_SHADERFORMAT_DXIL) {
        info.format = SDL_GPU_SHADERFORMAT_DXIL;
        info.code = vs ? nanovg_vs_dxil : nanovg_ps_dxil;
        info.code_size = vs ? sizeof nanovg_vs_dxil : sizeof nanovg_ps_dxil;
        info.entrypoint = vs ? "VSMain" : "PSMain";
    } else if (formats & SDL_GPU_SHADERFORMAT_MSL) {
        info.format = SDL_GPU_SHADERFORMAT_MSL;
        info.code = vs ? nanovg_vs_msl : nanovg_ps_msl;
        info.code_size = (vs ? sizeof nanovg_vs_msl : sizeof nanovg_ps_msl) - 1;
        info.entrypoint = "main0";
    } else {
        return NULL;
    }
    return SDL_CreateGPUShader(device, &info);
}

static int gn__renderCreate(void *uptr)
{
    gn_context *gl = (gn_context *) uptr;
    gl->vs = gn__shader(gl->device, true);
    gl->fs = gn__shader(gl->device, false);
    if (!gl->vs || !gl->fs) return 0;
    /* Draws without an image still bind one. */
    unsigned char zero = 0;
    gl->dummyTex = gn__renderCreateTexture(gl, NVG_TEXTURE_ALPHA, 1, 1, 0, &zero);
    return gl->dummyTex != 0;
}

static void gn__renderDelete(void *uptr)
{
    gn_context *gl = (gn_context *) uptr;
    if (!gl) return;
    /* The device may be gone already (at shutdown), with everything made on it. */
    if (gl->device && phpsdl_gpu_device() == gl->device) {
        for (int i = 0; i < gl->ntextures; i++) if (gl->textures[i].tex) SDL_ReleaseGPUTexture(gl->device, gl->textures[i].tex);
        for (int i = 0; i < gl->npipelines; i++) SDL_ReleaseGPUGraphicsPipeline(gl->device, gl->pipelines[i].pipeline);
        for (int i = 0; i < 16; i++) if (gl->samplers[i]) SDL_ReleaseGPUSampler(gl->device, gl->samplers[i]);
        if (gl->vs) SDL_ReleaseGPUShader(gl->device, gl->vs);
        if (gl->fs) SDL_ReleaseGPUShader(gl->device, gl->fs);
        if (gl->vbuf) SDL_ReleaseGPUBuffer(gl->device, gl->vbuf);
        if (gl->ibuf) SDL_ReleaseGPUBuffer(gl->device, gl->ibuf);
        if (gl->upload) SDL_ReleaseGPUTransferBuffer(gl->device, gl->upload);
    }
    free(gl->textures);
    free(gl->pipelines);
    free(gl->paths);
    free(gl->verts);
    free(gl->uniforms);
    free(gl->calls);
    free(gl->indices);
    free(gl->draws);
    free(gl);
}

NVGcontext *nvgCreateSDLGPU(int flags)
{
    SDL_GPUDevice *device = phpsdl_gpu_device();
    if (!device) return NULL;
    gn_context *gl = calloc(1, sizeof(gn_context));
    if (!gl) return NULL;
    gl->device = device;
    gl->flags = flags;
    gl->ratio = 1;
    NVGparams params;
    memset(&params, 0, sizeof params);
    params.renderCreate = gn__renderCreate;
    params.renderCreateTexture = gn__renderCreateTexture;
    params.renderDeleteTexture = gn__renderDeleteTexture;
    params.renderUpdateTexture = gn__renderUpdateTexture;
    params.renderGetTextureSize = gn__renderGetTextureSize;
    params.renderViewport = gn__renderViewport;
    params.renderCancel = gn__renderCancel;
    params.renderFlush = gn__renderFlush;
    params.renderFill = gn__renderFill;
    params.renderFillEvenOdd = gn__renderFillEvenOdd;
    params.renderStroke = gn__renderStroke;
    params.renderTriangles = gn__renderTriangles;
    params.renderClip = gn__renderClip;
    params.renderResetClip = gn__renderResetClip;
    params.renderSaveClip = gn__renderSaveClip;
    params.renderRestoreClip = gn__renderRestoreClip;
    params.renderDelete = gn__renderDelete;
    params.userPtr = gl;
    params.edgeAntiAlias = flags & NVG_ANTIALIAS ? 1 : 0;
    /* On failure nvgCreateInternal frees the context through renderDelete. */
    return nvgCreateInternal(&params);
}
