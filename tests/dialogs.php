<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';
require __DIR__ . '/../src/autoload.php';
use PhpUi\{DBus, Desktop};

if (getenv('PHPUI_TEST_MODE') === 'real-portal') {
    $dir = getenv('PHPUI_TEST_TMP');
    $capture = function ($file) {
        $p = proc_open(['import', '-window', 'root', $file], [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
        same(0, proc_close($p), 'capture Xvfb root');
    };
    $capture("$dir/before.png");
    $done = false;
    Desktop::openFile(function () use (&$done) { $done = true; });
    $property = new ReflectionProperty(Desktop::class, 'dialog');
    check(until(fn () => ($property->getValue()['replied'] ?? false)), 'real portal returns request handle');
    until(fn () => false, 1);
    check(!$done, 'real GTK dialog stays open without Response');
    $capture("$dir/during.png");
    check(hash_file('sha256', "$dir/before.png") !== hash_file('sha256', "$dir/during.png"), 'GTK dialog changes root pixels');
    $path = $property->getValue()['path'];
    DBus::session()->call('org.freedesktop.portal.Desktop', $path, 'org.freedesktop.portal.Request', 'Close');
    until(fn () => false, 1);
    $capture("$dir/after.png");
    $p = proc_open(['magick', 'compare', '-metric', 'AE', "$dir/before.png", "$dir/after.png", 'null:'], [1 => ['file', '/dev/null', 'w'], 2 => ['pipe', 'w']], $pipes);
    $difference = stream_get_contents($pipes[2]); fclose($pipes[2]);
    same(0, proc_close($p), "closing request restores root pixels ($difference)");
    Desktop::shutdown();
    putenv('PHPUI_DESKTOP=fallback');
    $done = false;
    Desktop::openFile(function () use (&$done) { $done = true; });
    until(fn () => false, 1);
    check(!$done, 'real zenity remains open without blocking');
    $jobs = (new ReflectionProperty(Desktop::class, 'jobs'))->getValue();
    $pid = proc_get_status(end($jobs)['process'])['pid'];
    Desktop::shutdown();
    until(fn () => !posix_kill($pid, 0));
    check(!posix_kill($pid, 0), 'shutdown ends real zenity');
    exit;
}

fakeSet('chooser', 'ok:/tmp/a space,/tmp/é.png');
$result = false;
Desktop::openFile(function ($paths) use (&$result) { $result = $paths; }, 'Pick images', ['Images' => ['png', 'jpg'], 'All files' => ['*']], true, '/tmp');
check(until(function () use (&$result) { return $result !== false; }), 'portal open callback arrives');
same(['/tmp/a space', '/tmp/é.png'], $result, 'portal file URIs decode spaces and Unicode');
$call = array_slice(fakeCalls('OpenFile'), -1)[0];
same('Pick images', $call['title'], 'portal gets title');
same(1, $call['options']['multiple'], 'portal gets multiple');
same([['Images', [[0, '*.png'], [0, '*.jpg']]], ['All files', [[0, '*']]]], $call['options']['filters'], 'portal gets structured filters');
check(str_starts_with($call['options']['handle_token'], 'phpui'), 'portal gets request token');

foreach (['saveFile', 'pickFolder'] as $method) {
    $result = false;
    Desktop::$method(function ($path) use (&$result) { $result = $path; });
    until(function () use (&$result) { return $result !== false; });
    same('/tmp/a space', $result, "$method returns one path");
}
fakeSet('chooser', 'cancel');
$result = false;
Desktop::openFile(function ($paths) use (&$result) { $result = $paths; });
until(function () use (&$result) { return $result !== false; });
same(null, $result, 'cancel returns null');
fakeSet('chooser', 'hold');
$result = false; $turns = 0;
Desktop::openFile(function ($paths) use (&$result) { $result = $paths; });
until(function () use (&$turns) { $turns++; return false; }, 1);
same(false, $result, 'held dialog has no response timeout');
check($turns > 50, 'held dialog does not block');
same(1 / 60, Desktop::waitHint(), 'open dialog keeps responsive wake rate');
$second = false;
Desktop::openFile(function ($paths) use (&$second) { $second = $paths; });
Desktop::poll();
same(null, $second, 'second concurrent chooser is refused');
check(str_contains(Desktop::lastError(), 'already open'), 'concurrent chooser records reason');
Desktop::shutdown();

$path = getenv('PATH');
putenv('PATH=' . __DIR__ . '/fakes/bin:' . $path);
fakeSet('chooser', 'error');
$result = false;
Desktop::openFile(function ($paths) use (&$result) { $result = $paths; });
until(function () use (&$result) { return $result !== false; });
same(['/tmp/fake'], $result, 'portal error falls back to zenity');
putenv('PHPUI_DESKTOP=fallback');
putenv("FAKE_ZENITY_OUT=/tmp/a space\n/tmp/é\n");
$result = false;
Desktop::openFile(function ($paths) use (&$result) { $result = $paths; }, 'Images', ['Images' => ['png', 'jpg']], true, '/tmp');
until(function () use (&$result) { return $result !== false; });
same(['/tmp/a space', '/tmp/é'], $result, 'zenity multiple decodes newline-separated paths');
$args = array_slice(fakeCalls('zenity'), -1)[0]['argv'];
same(['--file-selection', '--title=Images', '--multiple', "--separator=\n", '--filename=/tmp/', '--file-filter=Images | *.png *.jpg'], $args, 'zenity open argv preserves each argument');
foreach (['saveFile', 'pickFolder'] as $method) {
    $result = false;
    $cb = function ($value) use (&$result) { $result = $value; };
    if ($method === 'saveFile') Desktop::saveFile($cb, 'Save', 'a file.txt', '/tmp');
    else Desktop::pickFolder($cb, folder: '/tmp');
    until(function () use (&$result) { return $result !== false; });
    $args = array_slice(fakeCalls('zenity'), -1)[0]['argv'];
    check(in_array($method === 'saveFile' ? '--save' : '--directory', $args), "$method uses zenity mode flag");
    check(in_array($method === 'saveFile' ? '--filename=/tmp/a file.txt' : '--filename=/tmp/', $args), "$method gets filename suggestion");
}
foreach ([1, 2] as $code) {
    putenv("FAKE_ZENITY_EXIT=$code");
    $result = false;
    Desktop::openFile(function ($paths) use (&$result) { $result = $paths; });
    until(function () use (&$result) { return $result !== false; });
    same(null, $result, "zenity exit $code returns null");
    if ($code === 2) check(str_contains(Desktop::lastError(), 'code 2'), 'zenity failure records reason');
}
putenv('FAKE_ZENITY_EXIT=0'); putenv('FAKE_ZENITY_SLEEP=1');
$result = false; $turns = 0;
Desktop::openFile(function ($paths) use (&$result) { $result = $paths; });
until(function () use (&$result, &$turns) { $turns++; return $result !== false; });
check($turns > 100, 'slow zenity does not block polling');
putenv('PATH=/nonexistent');
$result = false;
Desktop::openFile(function ($paths) use (&$result) { $result = $paths; });
Desktop::poll();
same(null, $result, 'missing zenity completes with null');
same('No file dialog available: install xdg-desktop-portal or zenity', Desktop::lastError(), 'missing chooser has actionable error');
putenv('PATH=' . $path);
Desktop::shutdown();
