/*
 * CPU rasterizing for PhpUi\Canvas: path coverage (non-zero with HarfBuzz's exact
 * area rasterizer, even-odd with 16 sub-scanlines of exact horizontal coverage),
 * clipping, Gaussian blur for shadows, and paints (gradients with any number of
 * stops, patterns) baked into premultiplied RGBA. render.c uploads the results.
 */

#include <stdlib.h>
#include <string.h>
#include <math.h>
#include <hb.h>
#include <hb-raster.h>
#include "raster.h"

#ifndef M_PI
#define M_PI 3.14159265358979323846
#endif

static hb_raster_draw_t *raster = NULL;

void phpsdl_raster_shutdown(void)
{
    if (raster) { hb_raster_draw_destroy(raster); raster = NULL; }
}

void phpsdl_mask_free(phpsdl_mask *m)
{
    free(m->a);
    memset(m, 0, sizeof *m);
}

void phpsdl_mask_copy(const phpsdl_mask *from, phpsdl_mask *to)
{
    *to = *from;
    size_t size = (size_t) from->w * (size_t) from->h;
    to->a = malloc(size ? size : 1);
    if (to->a && size) memcpy(to->a, from->a, size);
}

/* Non-zero: HarfBuzz's rasterizer, glyph space has y up. */
static bool fill_nonzero(const phpsdl_path *p, phpsdl_mask *out)
{
    if (!raster) raster = hb_raster_draw_create_or_fail();
    if (!raster) return false;
    hb_raster_draw_reset(raster);
    hb_raster_draw_set_scale_factor(raster, 1.0f, 1.0f);
    hb_raster_draw_set_transform(raster, 1.f, 0.f, 0.f, 1.f, 0.f, 0.f);
    hb_draw_funcs_t *funcs = hb_raster_draw_get_funcs(raster);
    hb_draw_state_t st = HB_DRAW_STATE_DEFAULT;
    const float *v = p->pts;
    bool open = false;
    for (int i = 0; i < p->ncmds; i++) {
        switch (p->cmds[i]) {
            case PATH_MOVE:
                if (open) hb_draw_close_path(funcs, raster, &st);
                hb_draw_move_to(funcs, raster, &st, v[0], -v[1]);
                open = true; v += 2; break;
            case PATH_LINE: hb_draw_line_to(funcs, raster, &st, v[0], -v[1]); v += 2; break;
            case PATH_QUAD: hb_draw_quadratic_to(funcs, raster, &st, v[0], -v[1], v[2], -v[3]); v += 4; break;
            case PATH_CUBIC: hb_draw_cubic_to(funcs, raster, &st, v[0], -v[1], v[2], -v[3], v[4], -v[5]); v += 6; break;
            case PATH_CLOSE: if (open) hb_draw_close_path(funcs, raster, &st); open = false; break;
        }
    }
    if (open) hb_draw_close_path(funcs, raster, &st);
    hb_raster_image_t *img = hb_raster_draw_render(raster);
    if (!img) return false;
    hb_raster_extents_t ext;
    hb_raster_image_get_extents(img, &ext);
    bool ok = false;
    if (ext.width && ext.height) {
        out->x = ext.x_origin;
        out->y = -(ext.y_origin + (int) ext.height);
        out->w = (int) ext.width;
        out->h = (int) ext.height;
        out->a = malloc((size_t) out->w * (size_t) out->h);
        if (out->a) {
            const uint8_t *buf = hb_raster_image_get_buffer(img);
            /* Rows come bottom up. */
            for (int r = 0; r < out->h; r++) {
                memcpy(out->a + (size_t) r * out->w, buf + (size_t) (out->h - 1 - r) * ext.stride, (size_t) out->w);
            }
            ok = true;
        }
    }
    hb_raster_draw_recycle_image(raster, img);
    return ok;
}

/* Even-odd: 16 sub-scanlines per row, each covering its spans exactly across. */
#define SUBSCAN 16
typedef struct { float x0, y0, x1, y1; } edge;

static int cmp_float(const void *a, const void *b)
{
    float x = *(const float *) a, y = *(const float *) b;
    return (x > y) - (x < y);
}

static bool fill_evenodd(const phpsdl_path *p, phpsdl_mask *out)
{
    phpsdl_path flat = {0};
    phpsdl_path_flatten(p, 0.1f, &flat);
    float b[4];
    if (!phpsdl_path_bounds(&flat, b)) { phpsdl_path_free(&flat); return false; }
    /* Edges, every subpath closed. */
    int cap = flat.ncmds + 16, n = 0;
    edge *edges = malloc(sizeof(edge) * (size_t) cap);
    float sx = 0, sy = 0, cx = 0, cy = 0;
    bool open = false;
    const float *v = flat.pts;
    for (int i = 0; i <= flat.ncmds; i++) {
        uint8_t c = i < flat.ncmds ? flat.cmds[i] : PATH_CLOSE;
        if ((c == PATH_MOVE || c == PATH_CLOSE) && open && (cx != sx || cy != sy)) {
            edges[n++] = (edge) {cx, cy, sx, sy};
        }
        if (c == PATH_MOVE) { sx = cx = v[0]; sy = cy = v[1]; open = true; v += 2; }
        else if (c == PATH_LINE) {
            if (cy != v[1]) edges[n++] = (edge) {cx, cy, v[0], v[1]};
            cx = v[0]; cy = v[1]; v += 2;
        } else if (c == PATH_CLOSE) { cx = sx; cy = sy; open = false; }
        if (n + 2 >= cap) { cap *= 2; edges = realloc(edges, sizeof(edge) * (size_t) cap); }
    }
    phpsdl_path_free(&flat);
    int x0 = (int) floorf(b[0]), y0 = (int) floorf(b[1]), x1 = (int) ceilf(b[2]), y1 = (int) ceilf(b[3]);
    int w = x1 - x0, h = y1 - y0;
    if (n == 0 || w <= 0 || h <= 0) { free(edges); return false; }
    out->x = x0; out->y = y0; out->w = w; out->h = h;
    out->a = calloc((size_t) w * (size_t) h, 1);
    float *acc = calloc((size_t) w + 1, sizeof(float));
    float *xs = malloc(sizeof(float) * (size_t) n);
    const float weight = 1.0f / SUBSCAN;
    for (int row = 0; row < h; row++) {
        memset(acc, 0, sizeof(float) * (size_t) (w + 1));
        for (int k = 0; k < SUBSCAN; k++) {
            float y = y0 + row + (k + 0.5f) / SUBSCAN;
            int m = 0;
            for (int e = 0; e < n; e++) {
                const edge *E = &edges[e];
                float lo = fminf(E->y0, E->y1), hi = fmaxf(E->y0, E->y1);
                if (y < lo || y >= hi) continue;
                xs[m++] = E->x0 + (y - E->y0) * (E->x1 - E->x0) / (E->y1 - E->y0) - x0;
            }
            if (m < 2) continue;
            qsort(xs, (size_t) m, sizeof(float), cmp_float);
            for (int s = 0; s + 1 < m; s += 2) {
                float a = fmaxf(0, xs[s]), z = fminf((float) w, xs[s + 1]);
                if (z <= a) continue;
                int ia = (int) a, iz = (int) z;
                if (ia == iz) { acc[ia] += (z - a) * weight; continue; }
                acc[ia] += (ia + 1 - a) * weight;
                for (int i = ia + 1; i < iz; i++) acc[i] += weight;
                if (iz < w) acc[iz] += (z - iz) * weight;
            }
        }
        unsigned char *dst = out->a + (size_t) row * w;
        for (int i = 0; i < w; i++) {
            float c = acc[i] > 1 ? 1 : acc[i];
            dst[i] = (unsigned char) (c * 255 + 0.5f);
        }
    }
    free(xs);
    free(acc);
    free(edges);
    return true;
}

bool phpsdl_raster_fill(const phpsdl_path *device_path, int rule, phpsdl_mask *out)
{
    memset(out, 0, sizeof *out);
    if (device_path->ncmds == 0) return false;
    return rule == PATH_EVENODD ? fill_evenodd(device_path, out) : fill_nonzero(device_path, out);
}

bool phpsdl_mask_crop(phpsdl_mask *m, int x0, int y0, int x1, int y1)
{
    int nx0 = m->x > x0 ? m->x : x0, ny0 = m->y > y0 ? m->y : y0;
    int nx1 = m->x + m->w < x1 ? m->x + m->w : x1, ny1 = m->y + m->h < y1 ? m->y + m->h : y1;
    if (nx1 <= nx0 || ny1 <= ny0) { phpsdl_mask_free(m); return false; }
    if (nx0 == m->x && ny0 == m->y && nx1 == m->x + m->w && ny1 == m->y + m->h) return true;
    int w = nx1 - nx0, h = ny1 - ny0;
    unsigned char *a = malloc((size_t) w * (size_t) h);
    for (int r = 0; r < h; r++) memcpy(a + (size_t) r * w, m->a + (size_t) (ny0 - m->y + r) * m->w + (nx0 - m->x), (size_t) w);
    free(m->a);
    m->a = a; m->x = nx0; m->y = ny0; m->w = w; m->h = h;
    return true;
}

bool phpsdl_mask_intersect(phpsdl_mask *m, const phpsdl_mask *clip)
{
    if (!phpsdl_mask_crop(m, clip->x, clip->y, clip->x + clip->w, clip->y + clip->h)) return false;
    for (int r = 0; r < m->h; r++) {
        unsigned char *row = m->a + (size_t) r * m->w;
        const unsigned char *c = clip->a + (size_t) (m->y - clip->y + r) * clip->w + (m->x - clip->x);
        for (int i = 0; i < m->w; i++) row[i] = (unsigned char) ((row[i] * c[i] + 127) / 255);
    }
    return true;
}

/* One box blur pass of radius $r over $n values $step apart. */
static void box_pass(float *data, float *tmp, int n, int step, int r)
{
    if (r <= 0) return;
    float scale = 1.0f / (2 * r + 1), sum = 0;
    for (int i = 0; i < n; i++) tmp[i] = data[(size_t) i * step];
    for (int i = -r; i <= r; i++) if (i >= 0 && i < n) sum += tmp[i];
    for (int i = 0; i < n; i++) {
        data[(size_t) i * step] = sum * scale;
        int add = i + r + 1, sub = i - r;
        if (add < n) sum += tmp[add];
        if (sub >= 0) sum -= tmp[sub];
    }
}

void phpsdl_mask_blur(phpsdl_mask *m, float sigma)
{
    if (sigma < 0.3f || !m->a) return;
    /* Three box blurs approximate the Gaussian (W3C's sizes for them). */
    int boxes[3];
    float wIdeal = sqrtf(12 * sigma * sigma / 3 + 1);
    int wl = (int) floorf(wIdeal);
    if (wl % 2 == 0) wl--;
    int wu = wl + 2;
    float mIdeal = (12 * sigma * sigma - 3 * wl * wl - 4 * 3 * wl - 3 * 3) / (-4.0f * wl - 4);
    int mm = (int) roundf(mIdeal);
    for (int i = 0; i < 3; i++) boxes[i] = ((i < mm ? wl : wu) - 1) / 2;
    int pad = boxes[0] + boxes[1] + boxes[2] + 1;
    int w = m->w + 2 * pad, h = m->h + 2 * pad;
    float *f = calloc((size_t) w * (size_t) h, sizeof(float));
    float *tmp = malloc(sizeof(float) * (size_t) (w > h ? w : h));
    for (int r = 0; r < m->h; r++) for (int i = 0; i < m->w; i++) f[(size_t) (r + pad) * w + i + pad] = m->a[(size_t) r * m->w + i];
    for (int b = 0; b < 3; b++) {
        for (int r = 0; r < h; r++) box_pass(f + (size_t) r * w, tmp, w, 1, boxes[b]);
        for (int c = 0; c < w; c++) box_pass(f + c, tmp, h, w, boxes[b]);
    }
    free(m->a);
    m->a = malloc((size_t) w * (size_t) h);
    for (size_t i = 0; i < (size_t) w * h; i++) m->a[i] = (unsigned char) (f[i] > 255 ? 255 : f[i] + 0.5f);
    m->x -= pad; m->y -= pad; m->w = w; m->h = h;
    free(f);
    free(tmp);
}

void phpsdl_paint_stops(phpsdl_paint *p, const float *offsets, const float (*colors)[4], int n)
{
    for (int i = 0; i < 256; i++) {
        float t = i / 255.0f, c[4] = {0, 0, 0, 0};
        if (n == 1 || (n > 0 && t <= offsets[0])) memcpy(c, colors[0], sizeof c);
        else if (n > 0 && t >= offsets[n - 1]) memcpy(c, colors[n - 1], sizeof c);
        else {
            for (int s = 0; s + 1 < n; s++) {
                if (t >= offsets[s] && t <= offsets[s + 1]) {
                    float span = offsets[s + 1] - offsets[s];
                    float u = span > 0 ? (t - offsets[s]) / span : 1;
                    /* Between stops in premultiplied color, so transparent ends fade cleanly. */
                    for (int k = 0; k < 3; k++) c[k] = colors[s][k] * colors[s][3] * (1 - u) + colors[s + 1][k] * colors[s + 1][3] * u;
                    c[3] = colors[s][3] * (1 - u) + colors[s + 1][3] * u;
                    p->lut[i][0] = c[0]; p->lut[i][1] = c[1]; p->lut[i][2] = c[2]; p->lut[i][3] = c[3];
                    goto next;
                }
            }
        }
        p->lut[i][0] = c[0] * c[3]; p->lut[i][1] = c[1] * c[3]; p->lut[i][2] = c[2] * c[3]; p->lut[i][3] = c[3];
next:;
    }
}

static float wrap_coord(float v, int size, bool repeat, bool *outside)
{
    if (repeat) { v = fmodf(v, (float) size); if (v < 0) v += size; return v; }
    if (v < 0 || v >= size) *outside = true;
    return v;
}

static void pattern_texel(const phpsdl_paint *p, int x, int y, float out[4])
{
    if (p->repeat_x) { x %= p->iw; if (x < 0) x += p->iw; }
    if (p->repeat_y) { y %= p->ih; if (y < 0) y += p->ih; }
    if (x < 0 || y < 0 || x >= p->iw || y >= p->ih) { out[0] = out[1] = out[2] = out[3] = 0; return; }
    const unsigned char *t = p->image + ((size_t) y * p->iw + x) * 4;
    float a = t[3] / 255.0f;
    out[0] = t[0] / 255.0f * a; out[1] = t[1] / 255.0f * a; out[2] = t[2] / 255.0f * a; out[3] = a;
}

static void paint_at(const phpsdl_paint *p, float x, float y, float out[4])
{
    float t;
    switch (p->type) {
        case PAINT_LINEAR: {
            float dx = p->g[2] - p->g[0], dy = p->g[3] - p->g[1], dd = dx * dx + dy * dy;
            if (dd <= 0) { out[0] = out[1] = out[2] = out[3] = 0; return; }
            t = ((x - p->g[0]) * dx + (y - p->g[1]) * dy) / dd;
            break;
        }
        case PAINT_RADIAL: {
            /* Canvas's two-circle gradient: the largest ω with p on circle ω and r(ω) >= 0. */
            float cdx = p->g[3] - p->g[0], cdy = p->g[4] - p->g[1], dr = p->g[5] - p->g[2];
            float pdx = x - p->g[0], pdy = y - p->g[1], r0 = p->g[2];
            float a = cdx * cdx + cdy * cdy - dr * dr, b = pdx * cdx + pdy * cdy + r0 * dr, c = pdx * pdx + pdy * pdy - r0 * r0;
            float w;
            if (fabsf(a) < 1e-6f) {
                if (fabsf(b) < 1e-9f) { out[0] = out[1] = out[2] = out[3] = 0; return; }
                w = c / (2 * b);
                if (r0 + w * dr < 0) { out[0] = out[1] = out[2] = out[3] = 0; return; }
            } else {
                float disc = b * b - a * c;
                if (disc < 0) { out[0] = out[1] = out[2] = out[3] = 0; return; }
                float s = sqrtf(disc);
                w = (b + s) / a;
                if (r0 + w * dr < 0) {
                    w = (b - s) / a;
                    if (r0 + w * dr < 0) { out[0] = out[1] = out[2] = out[3] = 0; return; }
                }
            }
            t = w;
            break;
        }
        case PAINT_CONIC: {
            float a = atan2f(y - p->g[2], x - p->g[1]) - p->g[0];
            t = a / (float) (2 * M_PI);
            t -= floorf(t);
            break;
        }
        case PAINT_PATTERN: {
            float u = p->pattern_inv[0] * x + p->pattern_inv[2] * y + p->pattern_inv[4];
            float v = p->pattern_inv[1] * x + p->pattern_inv[3] * y + p->pattern_inv[5];
            bool outside = false;
            u = wrap_coord(u, p->iw, p->repeat_x, &outside);
            v = wrap_coord(v, p->ih, p->repeat_y, &outside);
            if (outside) { out[0] = out[1] = out[2] = out[3] = 0; return; }
            if (!p->smooth) { pattern_texel(p, (int) u, (int) v, out); return; }
            float fu = u - 0.5f, fv = v - 0.5f;
            int iu = (int) floorf(fu), iv = (int) floorf(fv);
            float du = fu - iu, dv = fv - iv, c00[4], c10[4], c01[4], c11[4];
            /* Edges clamp, as images are sampled at their borders. */
            int u0 = p->repeat_x ? iu : (iu < 0 ? 0 : iu), u1 = p->repeat_x ? iu + 1 : (iu + 1 >= p->iw ? p->iw - 1 : iu + 1);
            int v0 = p->repeat_y ? iv : (iv < 0 ? 0 : iv), v1 = p->repeat_y ? iv + 1 : (iv + 1 >= p->ih ? p->ih - 1 : iv + 1);
            pattern_texel(p, u0, v0, c00); pattern_texel(p, u1, v0, c10);
            pattern_texel(p, u0, v1, c01); pattern_texel(p, u1, v1, c11);
            for (int k = 0; k < 4; k++) {
                out[k] = (c00[k] * (1 - du) + c10[k] * du) * (1 - dv) + (c01[k] * (1 - du) + c11[k] * du) * dv;
            }
            return;
        }
        default: {
            float a = p->color[3];
            out[0] = p->color[0] * a; out[1] = p->color[1] * a; out[2] = p->color[2] * a; out[3] = a;
            return;
        }
    }
    int i = (int) (fmaxf(0, fminf(1, t)) * 255 + 0.5f);
    memcpy(out, p->lut[i], sizeof(float) * 4);
}

unsigned char *phpsdl_paint_bake(const phpsdl_mask *m, const phpsdl_paint *p, const float inv[6], float alpha)
{
    unsigned char *rgba = calloc((size_t) m->w * (size_t) m->h, 4);
    if (!rgba) return NULL;
    for (int r = 0; r < m->h; r++) {
        for (int i = 0; i < m->w; i++) {
            unsigned char cov = m->a[(size_t) r * m->w + i];
            if (!cov) continue;
            float dx = m->x + i + 0.5f, dy = m->y + r + 0.5f;
            float lx = inv[0] * dx + inv[2] * dy + inv[4], ly = inv[1] * dx + inv[3] * dy + inv[5];
            float c[4];
            paint_at(p, lx, ly, c);
            float k = cov / 255.0f * alpha;
            unsigned char *o = rgba + ((size_t) r * m->w + i) * 4;
            for (int j = 0; j < 4; j++) {
                float v = c[j] * k * 255 + 0.5f;
                o[j] = (unsigned char) (v > 255 ? 255 : v);
            }
        }
    }
    return rgba;
}
