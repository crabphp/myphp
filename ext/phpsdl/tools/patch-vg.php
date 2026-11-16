<?php
// phpsdl: VGContext draws with NanoVG's SDL_GPU backend (nanovg_sdlgpu.c) when phpsdl
// draws with SDL_GPU, and with the GL3 backend otherwise. Run on gl/phpglfw_vg.c.
$file = $argv[1];
$s = file_get_contents($file);
$patches = [
    "#include \"nanovg_gl.h\"\n" => "#include \"nanovg_gl.h\"\n#include \"nanovg_sdlgpu.h\"\n#include \"render.h\"\n",
    "    intern->nvgctx = nvgCreateGL3(flags);\n" => "    /* phpsdl: NanoVG draws with SDL_GPU when phpsdl does. */\n"
        . "    intern->nvgctx = phpsdl_gpu ? nvgCreateSDLGPU(flags) : nvgCreateGL3(flags);\n"
        . "    if (!intern->nvgctx) zend_throw_error(NULL, \"Could not create the VGContext: %s\", phpsdl_gpu ? \"no window draws with SDL_GPU yet\" : \"no OpenGL context\");\n",
    "    image->nvgimage_handle = nvglCreateImageFromHandleGL3(" => "    if (phpsdl_gpu) {\n        zend_throw_error(NULL, \"A VGImage from an OpenGL texture needs OpenGL; phpsdl draws with SDL_GPU\");\n        return;\n    }\n"
        . "    image->nvgimage_handle = nvglCreateImageFromHandleGL3(",
];
foreach ($patches as $from => $to) {
    if (str_contains($s, $to)) continue;
    if (substr_count($s, $from) !== 1) { fwrite(STDERR, "patch-vg: layout changed near " . trim(strtok($from, "\n")) . "\n"); exit(1); }
    $s = str_replace($from, $to, $s);
}
file_put_contents($file, $s);
