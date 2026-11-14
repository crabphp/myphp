<?php
// Packs src, assets and the dashboard into one dashboard.phar.
$out = __DIR__ . '/dashboard.phar';
@unlink($out);
$phar = new Phar($out);
$phar->startBuffering();
$root = dirname(__DIR__);
foreach (['src', 'assets'] as $dir) $phar->buildFromDirectory($root, '#^' . preg_quote($root, '#') . "/$dir/#");
$phar->addFile("$root/examples/dashboard.php", 'examples/dashboard.php');
$phar->setStub("#!/usr/bin/env php\n<?php Phar::mapPhar('dashboard.phar'); require 'phar://dashboard.phar/examples/dashboard.php'; __HALT_COMPILER();");
$phar->stopBuffering();
chmod($out, 0755);
