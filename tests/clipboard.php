<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';
require __DIR__ . '/../src/autoload.php';
use PhpUi\{App, Desktop};
$state = (object) ['text' => ''];
$app = new App('Clipboard', 400, 200, fn () => PhpUi\col()->full()->child(PhpUi\textInput('input', $state->text, fn ($v) => $state->text = $v)));
Desktop::setClipboard('a ✓');
same('a ✓', Desktop::clipboard(), 'clipboard round trip preserves Unicode');
// Keep the selection owner in our process group rather than killing unrelated xsel instances.
$owner = proc_open(['xsel', '-b', '-i', '--nodetach'], [0 => ['pipe', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
fwrite($pipes[0], 'x'); fclose($pipes[0]);
check(until(fn () => Desktop::clipboard() === 'x', poll: fn () => $app->window->waitEvents(0)), 'another program can supply clipboard text');
Desktop::setClipboard('y');
$result = null;
Desktop::spawn(['xsel', '-b', '-o'], function ($code, $text) use (&$result) { $result = [$code, $text]; });
check(until(function () use (&$result) { return $result !== null; }, poll: function () use ($app) { $app->window->waitEvents(0); Desktop::poll(); }), 'clipboard owner services another program asynchronously');
same([0, 'y'], $result, 'another program reads our clipboard');
$app->clickId('input');
Desktop::setClipboard("one\r\ntwo\nthree");
$app->key(GLFW_KEY_V, GLFW_MOD_CONTROL);
same('one two three', $state->text, 'Ctrl+V converts line breaks to spaces');
$app->key(GLFW_KEY_C, GLFW_MOD_CONTROL);
same($state->text, Desktop::clipboard(), 'Ctrl+C copies the entire input');
$app->key(GLFW_KEY_X, GLFW_MOD_CONTROL);
same('one two three', Desktop::clipboard(), 'Ctrl+X copies before clearing');
same('', $state->text, 'Ctrl+X clears the input');
$app->type('a'); $app->type('b');
$app->key(GLFW_KEY_C, GLFW_MOD_CONTROL);
same('ab', Desktop::clipboard(), 'copy reads the latest unframed input');
if (proc_get_status($owner)['running']) proc_terminate($owner, 9);
proc_close($owner);
