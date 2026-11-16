<?php
// phpsdl has no OpenGL and no NanoVG: after generate.sh copies PHP-GLFW's modules into
// ext/phpsdl, drop the gl*() functions and GL_* constants, glad, NanoVG and its
// GL\VectorGraphics bindings (VGContext, SVGImage). Buffers keep OpenGL's scalar types
// from phpsdl_gltypes.h. Run with the ext/phpsdl folder.
$ext = rtrim($argv[1], '/');
foreach (['gl/phpglfw_functions.c', 'gl/phpglfw_functions.h', 'gl/phpglfw_constants.c',
    'gl/phpglfw_vg.c', 'gl/phpglfw_vg.h', 'gl/phpglfw_svgparser.c', 'gl/phpglfw_svgparser.h'] as $file) {
    if (is_file("$ext/$file")) unlink("$ext/$file");
}
$remove = function (string $dir) use (&$remove) {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') continue;
        is_dir("$dir/$f") && !is_link("$dir/$f") ? $remove("$dir/$f") : unlink("$dir/$f");
    }
    rmdir($dir);
};
$remove("$ext/vendor/glad");
$remove("$ext/vendor/nanovg");
// The constants header only carries the GL types to the other modules now.
$h = "$ext/gl/phpglfw_constants.h";
$s = file_get_contents($h);
if (!str_contains($s, 'phpsdl_gltypes.h')) {
    if (substr_count($s, "#include <glad/glad.h>\n") !== 1) { fwrite(STDERR, "strip-gl: layout changed in phpglfw_constants.h\n"); exit(1); }
    $s = str_replace("#include <glad/glad.h>\n", "#include \"phpsdl_gltypes.h\"\n", $s);
    $s = str_replace("void phpglfw_register_constants(INIT_FUNC_ARGS);\n", '', $s);
    $s = preg_replace('/^#define PHPGLFW_C_\w+ .*\n/m', '', $s);
    file_put_contents($h, $s);
}
