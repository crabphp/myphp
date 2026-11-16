#ifndef PHPSDL_RENDER_H
#define PHPSDL_RENDER_H

#include <SDL3/SDL.h>

void phpsdl_render_register(zend_class_entry *renderer_ce);
void phpsdl_render_shutdown(void);

/* Drawing is SDL_GPU's: Vulkan, Metal or Direct3D 12. The driver's name ("vulkan",
 * "metal", "direct3d12"), or "none" before a window has claimed the device. */
const char *phpsdl_backend(void);
/* A window draws with the GPU device once claimed; released before it is destroyed. */
bool phpsdl_gpu_claim(SDL_Window *window);
void phpsdl_gpu_release(SDL_Window *window);
void phpsdl_gpu_vsync(SDL_Window *window, bool on);

/* A frame, cleared to $clear: into $window at $width×$height pixels, or, with no
 * window, off screen at that size. phpsdl_frame_end shows a window's frame, or saves
 * an off-screen frame as a PNG at $png. */
bool phpsdl_frame_begin(SDL_Window *window, int width, int height, const float clear[4]);
bool phpsdl_frame_end(SDL_Window *window, const char *png);

/* For NanoVG on SDL_GPU (nanovg_sdlgpu.c). The frame's command buffer is NULL when
 * no frame is open or it has nothing to draw into. */
SDL_GPUDevice *phpsdl_gpu_device(void);
SDL_GPUCommandBuffer *phpsdl_gpu_frame_cmd(void);
SDL_GPUTextureFormat phpsdl_gpu_frame_format(void);
SDL_GPUTextureFormat phpsdl_gpu_stencil_format(void);
SDL_GPURenderPass *phpsdl_gpu_begin_pass(bool stencil);

#endif
