<?php
// phpsdl: VGContext draws with NanoVG's SDL_GPU backend (nanovg_sdlgpu.c) instead of
// its OpenGL 3 one, and a VGImage cannot come from an OpenGL texture handle. Run on
// gl/phpglfw_vg.c.
$file = $argv[1];
$s = file_get_contents($file);
$patches = [
    "#define NANOVG_GL3_IMPLEMENTATION\n#include \"nanovg_gl.h\"\n" => "#include \"nanovg_sdlgpu.h\"\n",
    "\t    nvgDeleteGL3(intern->nvgctx);\n" => "\t    nvgDeleteInternal(intern->nvgctx);\n",
    "    intern->nvgctx = nvgCreateGL3(flags);\n" => "    /* phpsdl: NanoVG draws with SDL_GPU, into the frame phpsdl draws. */\n"
        . "    intern->nvgctx = nvgCreateSDLGPU(flags);\n"
        . "    if (!intern->nvgctx) zend_throw_error(NULL, \"Could not create the VGContext: open a PhpSdl\\\\Window first\");\n",
];
foreach ($patches as $from => $to) {
    if (str_contains($s, $to)) continue;
    if (substr_count($s, $from) !== 1) { fwrite(STDERR, "patch-vg: layout changed near " . trim(strtok($from, "\n")) . "\n"); exit(1); }
    $s = str_replace($from, $to, $s);
}
// imageFromHandle took an OpenGL texture name.
$s = preg_replace_callback('/(PHP_METHOD\(GL_VectorGraphics_VGContext, imageFromHandle\)\n\{\n)(.*?)\n\}\n/s', function ($m) {
    if (str_contains($m[2], 'phpsdl')) return $m[0];
    return $m[1] . "    /* phpsdl: there is no OpenGL texture to take. */\n"
        . "    zend_throw_error(NULL, \"VGContext::imageFromHandle() takes an OpenGL texture; phpsdl draws with SDL_GPU. Use imageFromTexture()\");\n"
        . "    RETURN_THROWS();\n}\n";
}, $s, 1, $count);
if ($count !== 1 || str_contains($s, 'GL3')) { fwrite(STDERR, "patch-vg: OpenGL left in phpglfw_vg.c\n"); exit(1); }
file_put_contents($file, $s);
