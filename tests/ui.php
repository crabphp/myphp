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
