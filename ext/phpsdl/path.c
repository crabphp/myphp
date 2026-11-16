/*
 * Vector paths for PhpUi\Canvas, with HTML canvas semantics: building (lines,
 * Béziers, arcs, arcTo, ellipses, rectangles, rounded rectangles), transforming,
 * flattening, stroking (joins, caps, miter limit, dashes) and hit testing. Pure C:
 * render.c rasterizes the result.
 */

#include <stdlib.h>
#include <string.h>
#include <math.h>
#include "path.h"

#ifndef M_PI
#define M_PI 3.14159265358979323846
#endif
#define TAU ((float) (2 * M_PI))

/* Building ------------------------------------------------------------------- */

static int path_reserve(phpsdl_path *p, int cmds, int pts)
{
    if (p->ncmds + cmds > p->ccmds) {
        int c = p->ccmds ? p->ccmds * 2 : 32;
        while (c < p->ncmds + cmds) c *= 2;
        uint8_t *n = realloc(p->cmds, (size_t) c);
        if (!n) return 0;
        p->cmds = n; p->ccmds = c;
    }
    if (p->npts + pts > p->cpts) {
        int c = p->cpts ? p->cpts * 2 : 128;
        while (c < p->npts + pts) c *= 2;
        float *n = realloc(p->pts, sizeof(float) * (size_t) c);
        if (!n) return 0;
        p->pts = n; p->cpts = c;
    }
    return 1;
}

static void path_push(phpsdl_path *p, uint8_t cmd, const float *v, int n)
{
    if (!path_reserve(p, 1, n)) return;
    p->cmds[p->ncmds++] = cmd;
    if (n) memcpy(p->pts + p->npts, v, sizeof(float) * (size_t) n);
    p->npts += n;
    /* Any command after a lone shape makes it a general path. */
    if (p->shape != PATH_SHAPE_NONE) p->shape = PATH_SHAPE_MIXED;
}

void phpsdl_path_clear(phpsdl_path *p)
{
    p->ncmds = p->npts = 0;
    p->shape = PATH_SHAPE_NONE;
    p->open = false;
}

static void xform(const float m[6], float x, float y, float *ox, float *oy)
{
    *ox = m[0] * x + m[2] * y + m[4];
    *oy = m[1] * x + m[3] * y + m[5];
}

void phpsdl_path_set_transform(phpsdl_path *p, const float m[6])
{
    static const float id[6] = {1, 0, 0, 1, 0, 0};
    if (!m || !memcmp(m, id, sizeof id)) { p->transformed = false; return; }
    memcpy(p->m, m, sizeof p->m);
    p->transformed = true;
}

/* A point added by the caller, into the path's space. */
#define TX(p, x, y) do { if ((p)->transformed) { float tx_, ty_; xform((p)->m, x, y, &tx_, &ty_); x = tx_; y = ty_; } } while (0)

/* Shapes stay exact boxes only under a translation and a uniform, positive scale. */
static bool shape_transform(const phpsdl_path *p, float *args)
{
    if (!p->transformed) return true;
    const float *m = p->m;
    if (m[1] != 0 || m[2] != 0 || m[0] <= 0 || fabsf(m[0] - m[3]) > 1e-6f) return false;
    args[0] = args[0] * m[0] + m[4];
    args[1] = args[1] * m[0] + m[5];
    for (int i = 2; i < 8; i++) args[i] *= m[0];
    return true;
}

void phpsdl_path_free(phpsdl_path *p)
{
    free(p->cmds);
    free(p->pts);
    memset(p, 0, sizeof *p);
}

void phpsdl_path_copy(const phpsdl_path *from, phpsdl_path *to)
{
    phpsdl_path_clear(to);
    if (!path_reserve(to, from->ncmds, from->npts)) return;
    memcpy(to->cmds, from->cmds, (size_t) from->ncmds);
    memcpy(to->pts, from->pts, sizeof(float) * (size_t) from->npts);
    to->ncmds = from->ncmds; to->npts = from->npts;
    to->shape = from->shape;
    memcpy(to->shape_args, from->shape_args, sizeof to->shape_args);
    to->open = from->open;
    memcpy(to->start, from->start, sizeof to->start);
    memcpy(to->last, from->last, sizeof to->last);
    memcpy(to->m, from->m, sizeof to->m);
    to->transformed = from->transformed;
}

static bool path_has_point(const phpsdl_path *p) { return p->ncmds > 0; }

/* Canvas starts a subpath at the point a line or curve is drawn from when there is none. */
static void path_ensure(phpsdl_path *p, float x, float y)
{
    if (!path_has_point(p)) phpsdl_path_move(p, x, y);
    else if (!p->open) {
        /* After a close, drawing continues from the closed subpath's start. */
        float v[2] = {p->start[0], p->start[1]};
        path_push(p, PATH_MOVE, v, 2);
        p->open = true;
    }
}

void phpsdl_path_move(phpsdl_path *p, float x, float y)
{
    TX(p, x, y);
    float v[2] = {x, y};
    path_push(p, PATH_MOVE, v, 2);
    p->start[0] = p->last[0] = x;
    p->start[1] = p->last[1] = y;
    p->open = true;
}

void phpsdl_path_line(phpsdl_path *p, float x, float y)
{
    if (!path_has_point(p)) { phpsdl_path_move(p, x, y); return; }
    path_ensure(p, x, y);
    TX(p, x, y);
    float v[2] = {x, y};
    path_push(p, PATH_LINE, v, 2);
    p->last[0] = x; p->last[1] = y;
}

void phpsdl_path_quad(phpsdl_path *p, float cx, float cy, float x, float y)
{
    path_ensure(p, cx, cy);
    TX(p, cx, cy);
    TX(p, x, y);
    float v[4] = {cx, cy, x, y};
    path_push(p, PATH_QUAD, v, 4);
    p->last[0] = x; p->last[1] = y;
}

void phpsdl_path_cubic(phpsdl_path *p, float c1x, float c1y, float c2x, float c2y, float x, float y)
{
    path_ensure(p, c1x, c1y);
    TX(p, c1x, c1y);
    TX(p, c2x, c2y);
    TX(p, x, y);
    float v[6] = {c1x, c1y, c2x, c2y, x, y};
    path_push(p, PATH_CUBIC, v, 6);
    p->last[0] = x; p->last[1] = y;
}

void phpsdl_path_close(phpsdl_path *p)
{
    if (!p->open) return;
    path_push(p, PATH_CLOSE, NULL, 0);
    p->open = false;
    p->last[0] = p->start[0]; p->last[1] = p->start[1];
}

void phpsdl_path_ellipse(phpsdl_path *p, float cx, float cy, float rx, float ry, float rotation, float a0, float a1, bool ccw)
{
    if (rx < 0 || ry < 0) return;
    float sweep = a1 - a0;
    if (!ccw) {
        if (sweep >= TAU) sweep = TAU;
        else { sweep = fmodf(sweep, TAU); if (sweep < 0) sweep += TAU; }
    } else {
        if (sweep <= -TAU) sweep = -TAU;
        else { sweep = fmodf(sweep, TAU); if (sweep > 0) sweep -= TAU; }
    }
    float cr = cosf(rotation), sr = sinf(rotation);
#define ELLIPSE_PT(ux, uy, ox, oy) do { float ex_ = (ux) * rx, ey_ = (uy) * ry; ox = cx + ex_ * cr - ey_ * sr; oy = cy + ex_ * sr + ey_ * cr; } while (0)
    float sx, sy;
    ELLIPSE_PT(cosf(a0), sinf(a0), sx, sy);
    bool full_circle = !path_has_point(p) && fabsf(sweep) >= TAU && rx == ry;
    /* Canvas: a line from the current point when there is one, else a new subpath. */
    if (path_has_point(p)) phpsdl_path_line(p, sx, sy);
    else phpsdl_path_move(p, sx, sy);
    int n = (int) ceilf(fabsf(sweep) / (float) (M_PI / 2) - 1e-4f);
    if (n < 1) n = 1;
    float step = sweep / n;
    float k = 4.0f / 3.0f * tanf(step / 4);
    float a = a0;
    for (int i = 0; i < n; i++) {
        float b = a + step;
        float ca = cosf(a), sa = sinf(a), cb = cosf(b), sb = sinf(b);
        float c1x, c1y, c2x, c2y, ex, ey;
        ELLIPSE_PT(ca - k * sa, sa + k * ca, c1x, c1y);
        ELLIPSE_PT(cb + k * sb, sb - k * cb, c2x, c2y);
        ELLIPSE_PT(cb, sb, ex, ey);
        phpsdl_path_cubic(p, c1x, c1y, c2x, c2y, ex, ey);
        a = b;
    }
#undef ELLIPSE_PT
    if (full_circle) {
        float args[8] = {cx - rx, cy - rx, 2 * rx, 2 * rx, rx, rx, rx, rx};
        if (shape_transform(p, args)) { p->shape = PATH_SHAPE_CIRCLE; memcpy(p->shape_args, args, sizeof args); }
        else p->shape = PATH_SHAPE_MIXED;
    }
}

void phpsdl_path_arc_to(phpsdl_path *p, float x1, float y1, float x2, float y2, float r)
{
    if (r < 0) return;
    if (!path_has_point(p)) phpsdl_path_move(p, x1, y1);
    float x0 = p->last[0], y0 = p->last[1];
    if (p->transformed) {
        /* The current point is in the path's space; arcTo works in the caller's. */
        const float *m = p->m;
        float det = m[0] * m[3] - m[1] * m[2];
        if (fabsf(det) < 1e-12f) return;
        float dx = x0 - m[4], dy = y0 - m[5];
        x0 = (m[3] * dx - m[2] * dy) / det;
        y0 = (-m[1] * dx + m[0] * dy) / det;
    }
    float ax = x0 - x1, ay = y0 - y1, bx = x2 - x1, by = y2 - y1;
    float la = hypotf(ax, ay), lb = hypotf(bx, by);
    float cross = ax * by - ay * bx;
    if (r == 0 || la < 1e-6f || lb < 1e-6f || fabsf(cross) < 1e-6f * la * lb) { phpsdl_path_line(p, x1, y1); return; }
    ax /= la; ay /= la; bx /= lb; by /= lb;
    float cosang = ax * bx + ay * by;
    float angle = acosf(fmaxf(-1, fminf(1, cosang)));
    float dist = r / tanf(angle / 2);
    float t0x = x1 + ax * dist, t0y = y1 + ay * dist;
    float t2x = x1 + bx * dist, t2y = y1 + by * dist;
    float mx = ax + bx, my = ay + by, ml = hypotf(mx, my);
    float d = r / sinf(angle / 2);
    float cx = x1 + mx / ml * d, cy = y1 + my / ml * d;
    float a0 = atan2f(t0y - cy, t0x - cx), a1 = atan2f(t2y - cy, t2x - cx);
    /* Turning one way or the other decides the arc's direction. */
    bool ccw = cross > 0;
    phpsdl_path_line(p, t0x, t0y);
    phpsdl_path_ellipse(p, cx, cy, r, r, 0, a0, a1, ccw);
}

void phpsdl_path_rect(phpsdl_path *p, float x, float y, float w, float h)
{
    bool alone = !path_has_point(p);
    phpsdl_path_move(p, x, y);
    phpsdl_path_line(p, x + w, y);
    phpsdl_path_line(p, x + w, y + h);
    phpsdl_path_line(p, x, y + h);
    phpsdl_path_close(p);
    phpsdl_path_move(p, x, y);
    if (alone) {
        float nx = w < 0 ? x + w : x, ny = h < 0 ? y + h : y;
        float args[8] = {nx, ny, fabsf(w), fabsf(h), 0, 0, 0, 0};
        if (shape_transform(p, args)) { p->shape = PATH_SHAPE_RECT; memcpy(p->shape_args, args, sizeof args); }
        else p->shape = PATH_SHAPE_MIXED;
    }
}

void phpsdl_path_rounded(phpsdl_path *p, float x, float y, float w, float h, const float radii[4])
{
    bool alone = !path_has_point(p);
    if (w < 0) { x += w; w = -w; }
    if (h < 0) { y += h; h = -h; }
    float r[4];
    for (int i = 0; i < 4; i++) r[i] = fmaxf(0, radii[i]);
    /* Radii that do not fit are scaled down together, as canvas does. */
    float f = 1;
    float sums[4] = {r[0] + r[1], r[1] + r[2], r[2] + r[3], r[3] + r[0]};
    float sides[4] = {w, h, w, h};
    for (int i = 0; i < 4; i++) if (sums[i] > sides[i] && sums[i] > 0) f = fminf(f, sides[i] / sums[i]);
    for (int i = 0; i < 4; i++) r[i] *= f;
    const float k = 0.5522847498f;
    phpsdl_path_move(p, x + r[0], y);
    phpsdl_path_line(p, x + w - r[1], y);
    if (r[1] > 0) phpsdl_path_cubic(p, x + w - r[1] * (1 - k), y, x + w, y + r[1] * (1 - k), x + w, y + r[1]);
    phpsdl_path_line(p, x + w, y + h - r[2]);
    if (r[2] > 0) phpsdl_path_cubic(p, x + w, y + h - r[2] * (1 - k), x + w - r[2] * (1 - k), y + h, x + w - r[2], y + h);
    phpsdl_path_line(p, x + r[3], y + h);
    if (r[3] > 0) phpsdl_path_cubic(p, x + r[3] * (1 - k), y + h, x, y + h - r[3] * (1 - k), x, y + h - r[3]);
    phpsdl_path_line(p, x, y + r[0]);
    if (r[0] > 0) phpsdl_path_cubic(p, x, y + r[0] * (1 - k), x + r[0] * (1 - k), y, x + r[0], y);
    phpsdl_path_close(p);
    phpsdl_path_move(p, x, y);
    if (alone) {
        float args[8] = {x, y, w, h, r[0], r[1], r[2], r[3]};
        if (shape_transform(p, args)) { p->shape = PATH_SHAPE_ROUNDED; memcpy(p->shape_args, args, sizeof args); }
        else p->shape = PATH_SHAPE_MIXED;
    }
}

void phpsdl_path_transform(const phpsdl_path *in, const float m[6], phpsdl_path *out)
{
    phpsdl_path_clear(out);
    if (!path_reserve(out, in->ncmds, in->npts)) return;
    memcpy(out->cmds, in->cmds, (size_t) in->ncmds);
    for (int i = 0; i + 1 < in->npts; i += 2) xform(m, in->pts[i], in->pts[i + 1], &out->pts[i], &out->pts[i + 1]);
    out->ncmds = in->ncmds; out->npts = in->npts;
    out->open = in->open;
    xform(m, in->start[0], in->start[1], &out->start[0], &out->start[1]);
    xform(m, in->last[0], in->last[1], &out->last[0], &out->last[1]);
    out->shape = PATH_SHAPE_MIXED;
    out->transformed = false;
}

void phpsdl_path_add(phpsdl_path *p, const phpsdl_path *other, const float m[6])
{
    phpsdl_path t = {0};
    float mm[6];
    if (p->transformed) {
        /* The path's transform after $m. */
        const float *a = p->m;
        mm[0] = a[0] * m[0] + a[2] * m[1]; mm[1] = a[1] * m[0] + a[3] * m[1];
        mm[2] = a[0] * m[2] + a[2] * m[3]; mm[3] = a[1] * m[2] + a[3] * m[3];
        mm[4] = a[0] * m[4] + a[2] * m[5] + a[4]; mm[5] = a[1] * m[4] + a[3] * m[5] + a[5];
        m = mm;
    }
    phpsdl_path_transform(other, m, &t);
    if (path_reserve(p, t.ncmds, t.npts)) {
        memcpy(p->cmds + p->ncmds, t.cmds, (size_t) t.ncmds);
        memcpy(p->pts + p->npts, t.pts, sizeof(float) * (size_t) t.npts);
        p->ncmds += t.ncmds; p->npts += t.npts;
        if (t.ncmds) {
            p->shape = PATH_SHAPE_MIXED;
            p->open = t.open;
            memcpy(p->start, t.start, sizeof p->start);
            memcpy(p->last, t.last, sizeof p->last);
        }
    }
    phpsdl_path_free(&t);
}

/* Flattening ------------------------------------------------------------------- */

static int curve_steps(float dd, float factor, float tolerance)
{
    /* Wang's formula: enough segments for the curve to stay within $tolerance. */
    float n = sqrtf(factor * dd / fmaxf(tolerance, 1e-4f));
    if (!(n >= 1)) return 1;
    return n > 500 ? 500 : (int) ceilf(n);
}

void phpsdl_path_flatten(const phpsdl_path *in, float tolerance, phpsdl_path *out)
{
    phpsdl_path_clear(out);
    const float *v = in->pts;
    float cx = 0, cy = 0, sx = 0, sy = 0;
    for (int i = 0; i < in->ncmds; i++) {
        switch (in->cmds[i]) {
            case PATH_MOVE:
                cx = sx = v[0]; cy = sy = v[1];
                path_push(out, PATH_MOVE, v, 2);
                v += 2;
                break;
            case PATH_LINE:
                path_push(out, PATH_LINE, v, 2);
                cx = v[0]; cy = v[1];
                v += 2;
                break;
            case PATH_QUAD: {
                float ddx = cx - 2 * v[0] + v[2], ddy = cy - 2 * v[1] + v[3];
                int n = curve_steps(hypotf(ddx, ddy), 0.25f, tolerance);
                for (int s = 1; s <= n; s++) {
                    float t = (float) s / n, u = 1 - t;
                    float q[2] = {u * u * cx + 2 * u * t * v[0] + t * t * v[2], u * u * cy + 2 * u * t * v[1] + t * t * v[3]};
                    path_push(out, PATH_LINE, q, 2);
                }
                cx = v[2]; cy = v[3];
                v += 4;
                break;
            }
            case PATH_CUBIC: {
                float d1 = hypotf(cx - 2 * v[0] + v[2], cy - 2 * v[1] + v[3]);
                float d2 = hypotf(v[0] - 2 * v[2] + v[4], v[1] - 2 * v[3] + v[5]);
                int n = curve_steps(fmaxf(d1, d2), 0.75f, tolerance);
                for (int s = 1; s <= n; s++) {
                    float t = (float) s / n, u = 1 - t;
                    float a = u * u * u, b = 3 * u * u * t, c = 3 * u * t * t, d = t * t * t;
                    float q[2] = {a * cx + b * v[0] + c * v[2] + d * v[4], a * cy + b * v[1] + c * v[3] + d * v[5]};
                    path_push(out, PATH_LINE, q, 2);
                }
                cx = v[4]; cy = v[5];
                v += 6;
                break;
            }
            case PATH_CLOSE:
                path_push(out, PATH_CLOSE, NULL, 0);
                cx = sx; cy = sy;
                break;
        }
    }
    out->shape = PATH_SHAPE_MIXED;
}

/* Polylines: the subpaths of a flattened path, as points and whether each is closed. */
typedef struct {
    float *pts;
    int n, cap;
    bool closed;
} polyline;

static void poly_add(polyline *l, float x, float y)
{
    if (l->n && fabsf(l->pts[2 * l->n - 2] - x) < 1e-5f && fabsf(l->pts[2 * l->n - 1] - y) < 1e-5f) return;
    if (l->n == l->cap) {
        l->cap = l->cap ? l->cap * 2 : 32;
        l->pts = realloc(l->pts, sizeof(float) * 2 * (size_t) l->cap);
    }
    l->pts[2 * l->n] = x; l->pts[2 * l->n + 1] = y;
    l->n++;
}

typedef void (*polyline_fn)(const polyline *l, int segments, void *data);

/* Calls $fn for every subpath of the flattened $f; $segments counts its drawn commands
 * (a moveTo and lineTo to the same point make a zero-length, but drawn, subpath). */
static void each_polyline(const phpsdl_path *f, polyline_fn fn, void *data)
{
    polyline l = {0};
    int segments = 0;
    float sx = 0, sy = 0;
    const float *v = f->pts;
    for (int i = 0; i < f->ncmds; i++) {
        uint8_t c = f->cmds[i];
        if (c == PATH_MOVE) {
            if (l.n) fn(&l, segments, data);
            l.n = 0; l.closed = false; segments = 0;
            sx = v[0]; sy = v[1];
            poly_add(&l, v[0], v[1]);
            v += 2;
        } else if (c == PATH_LINE) {
            if (!l.n) poly_add(&l, sx, sy);
            poly_add(&l, v[0], v[1]);
            segments++;
            v += 2;
        } else if (c == PATH_CLOSE) {
            /* The closing segment is drawn by the closed flag. */
            if (l.n > 1 && fabsf(l.pts[2 * l.n - 2] - sx) < 1e-5f && fabsf(l.pts[2 * l.n - 1] - sy) < 1e-5f) l.n--;
            l.closed = true;
            segments++;
            fn(&l, segments, data);
            l.n = 0; l.closed = false; segments = 0;
        }
    }
    if (l.n) fn(&l, segments, data);
    free(l.pts);
}

/* Stroking --------------------------------------------------------------------- */

typedef struct {
    phpsdl_path *out;
    const phpsdl_stroke_style *style;
    float tolerance;
    polyline dash;
} stroker;

/* A polygon, wound clockwise on screen whatever order the points come in. */
static void emit_polygon(phpsdl_path *out, const float *pts, int n)
{
    if (n < 3) return;
    float area = 0;
    for (int i = 0; i < n; i++) {
        int j = (i + 1) % n;
        area += pts[2 * i] * pts[2 * j + 1] - pts[2 * j] * pts[2 * i + 1];
    }
    if (fabsf(area) < 1e-9f) return;
    bool reverse = area < 0;
    for (int k = 0; k < n; k++) {
        int i = reverse ? n - 1 - k : k;
        if (k == 0) phpsdl_path_move(out, pts[2 * i], pts[2 * i + 1]);
        else phpsdl_path_line(out, pts[2 * i], pts[2 * i + 1]);
    }
    phpsdl_path_close(out);
}

static void emit_circle(stroker *s, float cx, float cy, float r)
{
    float tol = fminf(s->tolerance, r * 0.5f);
    int n = (int) ceilf((float) M_PI / acosf(1 - tol / r));
    if (n < 8) n = 8;
    if (n > 256) n = 256;
    float pts[512];
    for (int i = 0; i < n; i++) {
        float a = TAU * i / n;
        pts[2 * i] = cx + cosf(a) * r;
        pts[2 * i + 1] = cy + sinf(a) * r;
    }
    emit_polygon(s->out, pts, n);
}

static void emit_join(stroker *s, float vx, float vy, float d0x, float d0y, float d1x, float d1y)
{
    float hw = s->style->width / 2;
    float cross = d0x * d1y - d0y * d1x, dot = d0x * d1x + d0y * d1y;
    if (fabsf(cross) < 1e-6f && dot > 0) return; /* straight on */
    if (s->style->join == PATH_JOIN_ROUND) { emit_circle(s, vx, vy, hw); return; }
    float sgn = cross > 0 ? -1.0f : 1.0f; /* the outer side */
    float n0x = -d0y * sgn, n0y = d0x * sgn, n1x = -d1y * sgn, n1y = d1x * sgn;
    float p0x = vx + n0x * hw, p0y = vy + n0y * hw, p1x = vx + n1x * hw, p1y = vy + n1y * hw;
    if (s->style->join == PATH_JOIN_MITER) {
        float mx = n0x + n1x, my = n0y + n1y, ml = hypotf(mx, my);
        if (ml > 1e-6f) {
            /* Miter length over line width: 1 / sin(half the angle between the segments). */
            float len = 2 / ml;
            if (len <= s->style->miter_limit) {
                float tipx = vx + mx / ml * hw * len, tipy = vy + my / ml * hw * len;
                float quad[8] = {vx, vy, p0x, p0y, tipx, tipy, p1x, p1y};
                emit_polygon(s->out, quad, 4);
                return;
            }
        }
    }
    float tri[6] = {vx, vy, p0x, p0y, p1x, p1y};
    emit_polygon(s->out, tri, 3);
}

static void emit_cap_square(stroker *s, float x, float y, float dx, float dy)
{
    float hw = s->style->width / 2;
    float nx = -dy * hw, ny = dx * hw, ex = dx * hw, ey = dy * hw;
    float q[8] = {x + nx, y + ny, x + nx + ex, y + ny + ey, x - nx + ex, y - ny + ey, x - nx, y - ny};
    emit_polygon(s->out, q, 4);
}

/* One polyline stroked: a quad per segment, joins between, caps at open ends. */
static void stroke_polyline(stroker *s, const polyline *l, int segments)
{
    float hw = s->style->width / 2;
    if (hw <= 0) return;
    int n = l->n;
    const float *p = l->pts;
    if (n == 1) {
        /* A zero-length subpath shows only as round or square caps. */
        if (segments == 0) return;
        if (s->style->cap == PATH_CAP_ROUND) emit_circle(s, p[0], p[1], hw);
        else if (s->style->cap == PATH_CAP_SQUARE) {
            float q[8] = {p[0] - hw, p[1] - hw, p[0] + hw, p[1] - hw, p[0] + hw, p[1] + hw, p[0] - hw, p[1] + hw};
            emit_polygon(s->out, q, 4);
        }
        return;
    }
    int nseg = l->closed ? n : n - 1;
    for (int i = 0; i < nseg; i++) {
        int j = (i + 1) % n;
        float ax = p[2 * i], ay = p[2 * i + 1], bx = p[2 * j], by = p[2 * j + 1];
        float dx = bx - ax, dy = by - ay, len = hypotf(dx, dy);
        if (len < 1e-6f) continue;
        dx /= len; dy /= len;
        float nx = -dy * hw, ny = dx * hw;
        float q[8] = {ax + nx, ay + ny, bx + nx, by + ny, bx - nx, by - ny, ax - nx, ay - ny};
        emit_polygon(s->out, q, 4);
        /* The join at this segment's end, with the next one. */
        if (l->closed || i + 1 < nseg) {
            int k = (j + 1) % n;
            float ex = p[2 * k] - bx, ey = p[2 * k + 1] - by, el = hypotf(ex, ey);
            if (el > 1e-6f) emit_join(s, bx, by, dx, dy, ex / el, ey / el);
        }
    }
    if (!l->closed) {
        float sx = p[2] - p[0], sy = p[3] - p[1], sl = hypotf(sx, sy);
        float ex = p[2 * n - 2] - p[2 * n - 4], ey = p[2 * n - 1] - p[2 * n - 3], el = hypotf(ex, ey);
        if (s->style->cap == PATH_CAP_ROUND) {
            emit_circle(s, p[0], p[1], hw);
            emit_circle(s, p[2 * n - 2], p[2 * n - 1], hw);
        } else if (s->style->cap == PATH_CAP_SQUARE && sl > 1e-6f && el > 1e-6f) {
            emit_cap_square(s, p[0], p[1], -sx / sl, -sy / sl);
            emit_cap_square(s, p[2 * n - 2], p[2 * n - 1], ex / el, ey / el);
        }
    }
}

/* Dashes: the polyline cut into its "on" pieces, each stroked with caps. */
static void stroke_dashed(stroker *s, const polyline *l, int segments)
{
    const phpsdl_stroke_style *st = s->style;
    int nd = st->ndash % 2 ? st->ndash * 2 : st->ndash;
    float total = 0;
    for (int i = 0; i < nd; i++) total += st->dash[i % st->ndash];
    if (total <= 0) { stroke_polyline(s, l, segments); return; }
    /* Where the pattern starts. */
    float off = fmodf(st->dash_offset, total);
    if (off < 0) off += total;
    int di = 0;
    while (off >= st->dash[di % st->ndash]) { off -= st->dash[di % st->ndash]; di = (di + 1) % nd; }
    float left = st->dash[di % st->ndash] - off;
    bool on = di % 2 == 0;
    polyline *d = &s->dash;
    d->n = 0; d->closed = false;
    int n = l->n, nseg = l->closed ? n : n - 1;
    const float *p = l->pts;
    if (on) poly_add(d, p[0], p[1]);
    for (int i = 0; i < nseg; i++) {
        int j = (i + 1) % n;
        float ax = p[2 * i], ay = p[2 * i + 1], bx = p[2 * j], by = p[2 * j + 1];
        float len = hypotf(bx - ax, by - ay), pos = 0;
        while (len - pos > left) {
            pos += left;
            float t = pos / len, x = ax + (bx - ax) * t, y = ay + (by - ay) * t;
            if (on) { poly_add(d, x, y); stroke_polyline(s, d, 1); d->n = 0; }
            else poly_add(d, x, y);
            on = !on;
            di = (di + 1) % nd;
            left = st->dash[di % st->ndash];
        }
        left -= len - pos;
        if (on) poly_add(d, bx, by);
    }
    if (on && d->n > 1) stroke_polyline(s, d, 1);
    d->n = 0;
}

static void stroke_one(const polyline *l, int segments, void *data)
{
    stroker *s = data;
    if (s->style->ndash > 0 && l->n > 1) stroke_dashed(s, l, segments);
    else stroke_polyline(s, l, segments);
}

void phpsdl_path_stroke(const phpsdl_path *in, const phpsdl_stroke_style *style, float tolerance, phpsdl_path *out)
{
    phpsdl_path_clear(out);
    phpsdl_path flat = {0};
    phpsdl_path_flatten(in, tolerance, &flat);
    stroker s = {out, style, tolerance, {0}};
    each_polyline(&flat, stroke_one, &s);
    free(s.dash.pts);
    phpsdl_path_free(&flat);
    out->shape = PATH_SHAPE_MIXED;
}

/* Hit testing, bounds, hashing ----------------------------------------------- */

typedef struct { float x, y; int winding; } hit;

static void hit_one(const polyline *l, int segments, void *data)
{
    (void) segments;
    hit *h = data;
    int n = l->n;
    for (int i = 0; i < n; i++) {
        int j = (i + 1) % n; /* fills close every subpath */
        float ax = l->pts[2 * i], ay = l->pts[2 * i + 1], bx = l->pts[2 * j], by = l->pts[2 * j + 1];
        if ((ay <= h->y) != (by <= h->y)) {
            float x = ax + (h->y - ay) / (by - ay) * (bx - ax);
            if (x > h->x) h->winding += by > ay ? 1 : -1;
        }
    }
}

bool phpsdl_path_contains(const phpsdl_path *p, float x, float y, int rule, float tolerance)
{
    phpsdl_path flat = {0};
    phpsdl_path_flatten(p, tolerance, &flat);
    hit h = {x, y, 0};
    each_polyline(&flat, hit_one, &h);
    phpsdl_path_free(&flat);
    return rule == PATH_EVENODD ? (h.winding & 1) != 0 : h.winding != 0;
}

bool phpsdl_path_bounds(const phpsdl_path *p, float b[4])
{
    if (p->npts < 2) return false;
    float x0 = p->pts[0], y0 = p->pts[1], x1 = x0, y1 = y0;
    for (int i = 2; i + 1 < p->npts; i += 2) {
        x0 = fminf(x0, p->pts[i]); x1 = fmaxf(x1, p->pts[i]);
        y0 = fminf(y0, p->pts[i + 1]); y1 = fmaxf(y1, p->pts[i + 1]);
    }
    b[0] = x0; b[1] = y0; b[2] = x1; b[3] = y1;
    return true;
}

uint64_t phpsdl_hash_bytes(const void *data, size_t len, uint64_t seed)
{
    const unsigned char *s = data;
    uint64_t h = seed ? seed : 0xcbf29ce484222325ull;
    for (size_t i = 0; i < len; i++) { h ^= s[i]; h *= 0x100000001b3ull; }
    return h;
}

uint64_t phpsdl_path_hash(const phpsdl_path *p, uint64_t seed)
{
    uint64_t h = phpsdl_hash_bytes(p->cmds, (size_t) p->ncmds, seed);
    return phpsdl_hash_bytes(p->pts, sizeof(float) * (size_t) p->npts, h);
}
