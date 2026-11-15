<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';
require __DIR__ . '/../src/autoload.php';
use PhpUi\Desktop;
$path = getenv('PATH');
putenv('PATH=' . __DIR__ . '/fakes/bin:' . $path);
$file = getenv('PHPUI_TEST_TMP') . '/a file.txt';
file_put_contents($file, 'test');
foreach (['https://example.com/?a=1&b=2', $file, 'README.md'] as $target) {
    $done = null;
    check(Desktop::open($target, function ($ok) use (&$done) { $done = $ok; }), 'open accepts URL or existing path');
    until(function () use (&$done) { return $done !== null; });
    same(true, $done, 'open callback reports success');
    same([file_exists($target) ? realpath($target) : $target], array_slice(fakeCalls('xdg-open'), -1)[0]['argv'], 'opener receives one absolute target argument');
}
foreach (['javascript:alert(1)', '--help', '/nonexistent/myphp-file'] as $target) {
    $done = null;
    same(false, Desktop::open($target, function ($ok) use (&$done) { $done = $ok; }), 'unsafe or absent target refused');
    Desktop::poll(); same(false, $done, 'refusal still calls callback');
}
$bin = getenv('PHPUI_TEST_TMP') . '/only-gio'; mkdir($bin);
symlink(__DIR__ . '/fakes/bin/gio', "$bin/gio");
putenv('PATH=' . $bin);
$done = null;
check(Desktop::open('mailto:test@example.com', function ($ok) use (&$done) { $done = $ok; }), 'gio fallback starts');
until(function () use (&$done) { return $done !== null; });
same(true, $done, 'gio fallback succeeds');
same(['open', 'mailto:test@example.com'], array_slice(fakeCalls('gio'), -1)[0]['argv'], 'gio argv is correct');
putenv('PATH=' . $path);
