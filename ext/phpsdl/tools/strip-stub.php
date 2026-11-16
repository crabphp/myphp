<?php
// Drop what myphp does not use and would need C++ for (model and voxel file parsers,
// the draw call assembler), and the gl*() functions: phpsdl has no OpenGL.
$file = $argv[1];
$stub = file_get_contents($file);
$parts = preg_split('/^(?=namespace )/m', $stub);
$keep = array_filter($parts, fn ($p) => !preg_match('/^namespace (GL\\\\(Geometry|Rendering)\b|\{)/', $p));
file_put_contents($file, implode('', $keep));
