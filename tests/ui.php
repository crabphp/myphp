<?php

declare(strict_types=1);

require __DIR__ . '/lib.php';

$one = getenv('PHPUI_TEST_TMP') . '/one.png';
$env = getenv();
unset($env['PHPUI_SCREENSHOTS']);
$env['PHPUI_SCREENSHOT'] = $one;
$start = microtime(true);
$child = proc_open(['timeout', '5', PHP_BINARY, __DIR__ . '/../examples/todo.php'],
    [1 => ['pipe', 'w'], 2 => STDERR], $pipes, env_vars: $env);
stream_get_contents($pipes[1]); fclose($pipes[1]);
same(0, proc_close($child), 'singular screenshot exits on its own');
check(microtime(true) - $start < 5, 'one frame finishes within five seconds');
check(is_file($one), 'one-frame PNG exists');
$identify = proc_open(['magick', 'identify', '-format', '%wx%h', $one],
    [1 => ['pipe', 'w'], 2 => STDERR], $pipes);
same('640x560', stream_get_contents($pipes[1]), 'one-frame PNG has window dimensions');
fclose($pipes[1]);
same(0, proc_close($identify), 'identify succeeds');
same('#f5f5f5', pixel($one, 0, 0), 'todo background is the light muted theme color');

// The todo example exits after its scripted flow; assert its JSON from a child.
$shots = getenv('PHPUI_TEST_TMP') . '/todo';
$child = proc_open([PHP_BINARY, __DIR__ . '/../examples/todo.php'], [1 => ['pipe', 'w'], 2 => STDERR], $pipes,
    env_vars: ['PHPUI_SCREENSHOTS' => $shots] + getenv());
$output = stream_get_contents($pipes[1]);
fclose($pipes[1]);
same(0, proc_close($child), 'todo screenshot flow exits successfully');
$result = json_decode(trim($output), true, flags: JSON_THROW_ON_ERROR);
same(3, count($result['todos']), 'todo flow leaves three todos');
same('open', $result['filter'], 'todo flow selects open filter');
same(1, $result['shown'], 'todo flow shows one open todo');

putenv('PHPUI_NO_RUN=1');
require __DIR__ . '/../examples/dashboard.php';
$app->frame();
$app->clickId('quick-create');
same(true, $state->dialog, 'dashboard quick-create opens dialog');
$app->clickId('draft-header');
$app->type('a');
$app->type('b');
$app->type('c');
same('abc', $state->draft['header'], 'typing between frames preserves every character');
$app->key(GLFW_KEY_BACKSPACE);
$app->key(GLFW_KEY_BACKSPACE);
same('a', $state->draft['header'], 'repeated backspace sees the latest text');
$app->key(GLFW_KEY_ESCAPE);
same(false, $state->dialog, 'Escape closes the dashboard dialog with its input focused');
$keys = [];
$app->onKey(function (int $key, int $mods) use (&$keys) { $keys[] = [$key, $mods]; });
$app->key(GLFW_KEY_V, GLFW_MOD_CONTROL);
same([[GLFW_KEY_V, GLFW_MOD_CONTROL]], $keys, 'unhandled keys preserve modifiers');
$app->clickId('search');
$app->key(GLFW_KEY_F1, GLFW_MOD_SHIFT);
same([GLFW_KEY_F1, GLFW_MOD_SHIFT], end($keys), 'input forwards keys it does not use');

$counter = (object) ['value' => 0];
$counterApp = new PhpUi\App('Counter', 200, 100, fn () => PhpUi\col()->full()->child(
    PhpUi\button((string) $counter->value, fn () => $counter->value++, 'default')->id('count')));
$counterApp->clickId('count');
$counterApp->clickId('count');
same(2, $counter->value, 'two clicks without a frame both run');
same($counterApp, PhpUi\App::current(), 'current is the most recently created app');
$turns = 0;
$counterApp->poller(function () use (&$turns, $counter) {
    if (++$turns !== 3) return false;
    $counter->value = 7;
    return true;
});
$counterApp->step();
$counterApp->step();
$counterApp->step();
same('7', $counterApp->find('count')->children[0]->text, 'third poller turn rebuilds the tree');
$counterApp->press(10, 10);
$counterApp->poller(fn () => true);
$counterApp->step();
$counterApp->release(10, 10);
same(7, $counter->value, 'outside callback cancels an in-flight press');
$shutdown = false;
$counterApp->onShutdown(function () use (&$shutdown) { $shutdown = true; });
$counterApp->poller(fn () => throw new RuntimeException('loop failure'));
try { $counterApp->run(); } catch (RuntimeException $e) { same('loop failure', $e->getMessage(), 'loop exception propagates'); }
check($shutdown, 'shutdown handler runs when the loop throws');
same(null, PhpUi\App::current(), 'destroyed app is no longer current');

PhpUi\Theme::use('light');
PhpUi\Theme::use('system');
PhpUi\Theme::set('dark');
same('system', PhpUi\Theme::$source, 'setting a mode preserves the system source');
check(PhpUi\Theme::dark(), 'explicit dark setter works');
PhpUi\Theme::toggle();
same('light', PhpUi\Theme::$source, 'toggle leaves the system source explicit');
foreach (['set', 'use'] as $method) {
    try { PhpUi\Theme::$method('invalid'); check(false, "$method rejects invalid value"); }
    catch (InvalidArgumentException) { check(true, "$method rejects invalid value"); }
}
PhpUi\Theme::use('light');

$sentence = str_repeat('A long sentence wraps inside its panel. ', 8);
$wrapApp = new PhpUi\App('Wrap', 500, 300, fn () => PhpUi\col()->full()->bg('#ffffff')->p(20)
    ->child(PhpUi\paragraph($sentence, 400)->bg('#eeeeee')));
$painter = PhpUi\Painter::$shared;
$lines = $painter->wrap($sentence, 400);
check(count($lines) > 1, 'long sentence wraps to multiple lines');
check(max(array_map(fn ($line) => $painter->textWidth($line, 'regular', 14), $lines)) <= 400, 'every wrapped line fits');
same(['first', '', 'third'], $painter->wrap("first\n\nthird", 400), 'wrapping preserves explicit newlines');
$wordLines = $painter->wrap(str_repeat('é', 100), 40);
check(count($wordLines) > 1 && implode('', $wordLines) === str_repeat('é', 100), 'oversized Unicode words split without losing characters');
$shot = getenv('PHPUI_TEST_TMP') . '/wrap.png';
$wrapApp->screenshot($shot, 500, 300, 1);
$probe = proc_open(['magick', $shot, '-crop', '70x300+430+0', '-format', '%[fx:mean]', 'info:'], [1 => ['pipe', 'w'], 2 => STDERR], $pipes);
$mean = stream_get_contents($pipes[1]); fclose($pipes[1]);
same(0, proc_close($probe), 'paragraph pixel probe succeeds');
same('1', $mean, 'no text pixels outside the paragraph box');
