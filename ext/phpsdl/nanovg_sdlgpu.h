#ifndef NANOVG_SDLGPU_H
#define NANOVG_SDLGPU_H

#include "nanovg.h"

/* The flags nanovg_gl.h defines, for code that does not include it. */
#ifndef NANOVG_GL_H
enum NVGcreateFlags {
    NVG_ANTIALIAS = 1 << 0,
    NVG_STENCIL_STROKES = 1 << 1,
    NVG_DEBUG = 1 << 2,
};
#endif

/* NanoVG drawing into phpsdl's SDL_GPU frame; NULL before a window has the GPU device. */
NVGcontext *nvgCreateSDLGPU(int flags);

#endif
