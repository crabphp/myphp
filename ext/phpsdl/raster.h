#ifndef PHPSDL_RASTER_H
#define PHPSDL_RASTER_H

#include <stdbool.h>
#include "path.h"

/* Coverage over device pixels: an 8-bit alpha image placed at (x, y), rows top down. */
typedef struct {
    int x, y, w, h;
    unsigned char *a;
} phpsdl_mask;

enum { PAINT_SOLID = 0, PAINT_LINEAR, PAINT_RADIAL, PAINT_CONIC, PAINT_PATTERN };

/* How a canvas paints what it covers. Geometry is in the canvas's own units. */
typedef struct {
    int type;
    float color[4];       /* solid, straight alpha */
    float g[6];           /* linear: x0 y0 x1 y1; radial: x0 y0 r0 x1 y1 r1; conic: angle x y */
    float lut[256][4];    /* gradient colors along t, premultiplied */
    const unsigned char *image; /* pattern: RGBA, straight alpha */
    int iw, ih;
    bool repeat_x, repeat_y, smooth;
    float pattern_inv[6]; /* canvas units to image pixels */
} phpsdl_paint;

void phpsdl_raster_shutdown(void);

/* The path (in device pixels) filled by $rule; false when it covers nothing. */
bool phpsdl_raster_fill(const phpsdl_path *device_path, int rule, phpsdl_mask *out);
void phpsdl_mask_free(phpsdl_mask *m);
/* Keep only [x0, x1) × [y0, y1); false when nothing is left. */
bool phpsdl_mask_crop(phpsdl_mask *m, int x0, int y0, int x1, int y1);
/* Multiply by $clip, which also bounds the result; false when nothing is left. */
bool phpsdl_mask_intersect(phpsdl_mask *m, const phpsdl_mask *clip);
/* A Gaussian blur of standard deviation $sigma pixels; the mask grows to fit. */
void phpsdl_mask_blur(phpsdl_mask *m, float sigma);
void phpsdl_mask_copy(const phpsdl_mask *from, phpsdl_mask *to);

/* Gradient stops (offsets in [0, 1], colors straight alpha) into the paint's table. */
void phpsdl_paint_stops(phpsdl_paint *p, const float *offsets, const float (*colors)[4], int n);
/* Premultiplied RGBA for the mask's pixels: the paint at each pixel's center, which
 * $inv maps from device pixels to canvas units, times coverage and $alpha. */
unsigned char *phpsdl_paint_bake(const phpsdl_mask *m, const phpsdl_paint *p, const float inv[6], float alpha);

#endif
