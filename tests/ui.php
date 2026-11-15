<?php

declare(strict_types=1);

require __DIR__ . '/lib.php';

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

$counter = (object) ['value' => 0];
$counterApp = new PhpUi\App('Counter', 200, 100, fn () => PhpUi\col()->full()->child(
    PhpUi\button((string) $counter->value, fn () => $counter->value++, 'default')->id('count')));
$counterApp->clickId('count');
$counterApp->clickId('count');
same(2, $counter->value, 'two clicks without a frame both run');
