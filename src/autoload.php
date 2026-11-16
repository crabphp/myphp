<?php

declare(strict_types=1);

if (!extension_loaded('glfw')) {
    fwrite(STDERR, "PHP UI needs the glfw extension (PHP-GLFW): https://phpgl.net\n");
    exit(1);
}
if (class_exists(\PhpUi\App::class, false)) return;

require __DIR__ . '/DBus.php';
require __DIR__ . '/Desktop.php';
require __DIR__ . '/Theme.php';
require __DIR__ . '/Element.php';
require __DIR__ . '/State.php';
require __DIR__ . '/QuadRenderer.php';
require __DIR__ . '/Painter.php';
require __DIR__ . '/Layout.php';
require __DIR__ . '/App.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/components.php';
require __DIR__ . '/charts.php';
