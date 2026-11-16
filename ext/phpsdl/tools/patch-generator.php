<?php
// Make PHP-GLFW's generator emit OpenGL and NanoVG bindings without GLFW.
$dir = $argv[1];
$build = file_get_contents("$dir/build");
$build = str_replace(['$glfwparser = new GLFWHeaderParser;', "\n\$glfwparser->process("], ['// phpsdl: no GLFW', "\n// \$glfwparser->process("], $build);
$build = preg_replace('/^\$gen->adjust\(PHPGlfwAdjustments\\\\GLFW/m', '// $0', $build);
file_put_contents("$dir/build", $build);
// The functions template holds hand-written GLFW callback handlers.
$tpl = "$dir/templates/phpglfw_functions.c.php";
$lines = file($tpl);
$start = $end = null;
foreach ($lines as $i => $line) {
    if ($start === null && str_starts_with($line, 'static void phpglfw_callback_windowposcallback_handler')) $start = $i;
    if ($start !== null && $end === null && str_contains($line, 'PHPGlfw Functions')) $end = $i - 2;
}
if ($start === null || $end === null) { fwrite(STDERR, "template layout changed\n"); exit(1); }
array_splice($lines, $start, $end - $start);
file_put_contents($tpl, implode('', $lines));
