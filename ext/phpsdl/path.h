#ifndef PHPSDL_PATH_H
#define PHPSDL_PATH_H

#include <stdint.h>
#include <stdbool.h>
#include <stddef.h>

/* A vector path (Path2D): moves, lines, quadratic and cubic Béziers and closes. */
enum { PATH_MOVE = 0, PATH_LINE, PATH_QUAD, PATH_CUBIC, PATH_CLOSE };
/* A path that is just one shape the renderer can draw exactly, without rasterizing. */
enum { PATH_SHAPE_NONE = 0, PATH_SHAPE_RECT, PATH_SHAPE_ROUNDED, PATH_SHAPE_CIRCLE, PATH_SHAPE_MIXED };
enum { PATH_JOIN_MITER = 0, PATH_JOIN_ROUND, PATH_JOIN_BEVEL };
enum { PATH_CAP_BUTT = 0, PATH_CAP_ROUND, PATH_CAP_SQUARE };
enum { PATH_NONZERO = 0, PATH_EVENODD };

typedef struct {
    uint8_t *cmds;
    float *pts;
    int ncmds, npts, ccmds, cpts;
    int shape;           /* PATH_SHAPE_*: while the path is only that one shape */
    float shape_args[8]; /* x, y, w, h, then radii tl, tr, br, bl */
    bool open;           /* a subpath was started and not closed */
    float start[2], last[2]; /* in the path's space (after m) */
    float m[6];          /* applied to points as they are added, as a canvas's current transform */
    bool transformed;
} phpsdl_path;

typedef struct {
    float width, miter_limit;
    int join, cap;
    const float *dash; /* lengths, an even count (an odd list is repeated, as canvas does) */
    int ndash;
    float dash_offset;
} phpsdl_stroke_style;

void phpsdl_path_clear(phpsdl_path *p);
void phpsdl_path_free(phpsdl_path *p);
void phpsdl_path_copy(const phpsdl_path *from, phpsdl_path *to);

/* Points added from now on go through $m (null: as given). */
void phpsdl_path_set_transform(phpsdl_path *p, const float m[6]);
void phpsdl_path_move(phpsdl_path *p, float x, float y);
void phpsdl_path_line(phpsdl_path *p, float x, float y);
void phpsdl_path_quad(phpsdl_path *p, float cx, float cy, float x, float y);
void phpsdl_path_cubic(phpsdl_path *p, float c1x, float c1y, float c2x, float c2y, float x, float y);
void phpsdl_path_close(phpsdl_path *p);
/* As canvas: an arc joined to the current point by a line; angles in radians. */
void phpsdl_path_ellipse(phpsdl_path *p, float cx, float cy, float rx, float ry, float rotation, float a0, float a1, bool ccw);
void phpsdl_path_arc_to(phpsdl_path *p, float x1, float y1, float x2, float y2, float r);
void phpsdl_path_rect(phpsdl_path *p, float x, float y, float w, float h);
/* Radii tl, tr, br, bl (circular). */
void phpsdl_path_rounded(phpsdl_path *p, float x, float y, float w, float h, const float r[4]);
/* $other under $m, then the path's own transform, appended (Path2D.addPath). */
void phpsdl_path_add(phpsdl_path *p, const phpsdl_path *other, const float m[6]);

/* The affine $m ([a, b, c, d, e, f]: x' = ax + cy + e, y' = bx + dy + f). */
void phpsdl_path_transform(const phpsdl_path *in, const float m[6], phpsdl_path *out);
/* Curves flattened into lines, each within $tolerance of the curve. */
void phpsdl_path_flatten(const phpsdl_path *in, float tolerance, phpsdl_path *out);
/* The outline of $in stroked with $style, as polygons that all wind the same way, so
 * a non-zero fill of $out paints the stroke. Curves are flattened within $tolerance. */
void phpsdl_path_stroke(const phpsdl_path *in, const phpsdl_stroke_style *style, float tolerance, phpsdl_path *out);
/* Whether ($x, $y) is inside the path (flattened within $tolerance) by $rule. */
bool phpsdl_path_contains(const phpsdl_path *p, float x, float y, int rule, float tolerance);
/* Bounds of the points (control points included); false when empty. */
bool phpsdl_path_bounds(const phpsdl_path *p, float b[4]);
uint64_t phpsdl_path_hash(const phpsdl_path *p, uint64_t seed);
uint64_t phpsdl_hash_bytes(const void *data, size_t len, uint64_t seed);

#endif
