<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';

// The log viewer: Laravel entries, filters, search, expanding, live follow and rotation.
$logsDir = getenv('PHPUI_TEST_TMP') . '/logs';
mkdir($logsDir);
putenv("MYPHP_LOGS_DIR=$logsDir");
putenv('PHPUI_NO_RUN=1');
require __DIR__ . '/../examples/logs.php';
use PhpUi\{Key, Mod};
$app->frame();

same(8, count($state->entries), 'the sample Laravel log has eight entries');
same(['error' => 2, 'warning' => 2, 'info' => 3, 'debug' => 1], array_merge(['error' => 0, 'warning' => 0, 'info' => 0, 'debug' => 0], array_count_values(array_column($state->entries, 'level'))), 'levels are grouped');
check(str_contains($state->entries[3]['detail'], '#2 /var/www/app/app/Http/Controllers/RegisterController.php'), 'stack trace lines stay with their entry');

$app->clickId('level-error');
check($app->find('entry-3') !== null && $app->find('entry-0') === null, 'the Error chip shows only errors');
$app->clickId('entry-3');
check($app->find('detail-3') !== null, 'clicking an entry expands its trace');
same(3, $state->open, 'one entry is open');
$app->clickId('level-all');
$app->clickId('search');
$app->type('getKey');
check($app->find('entry-6') !== null && $app->find('entry-3') === null, 'search looks in messages and traces');
$app->key(Key::A, PHP_OS_FAMILY === 'Darwin' ? Mod::SUPER : Mod::CONTROL);
$app->key(Key::BACKSPACE);

// Follow: a new entry, then its stack trace written separately.
$file = $state->file;
file_put_contents($file, '[' . date('Y-m-d H:i:s') . "] production.CRITICAL: Queue worker died {\"exception\":\"[object] (RuntimeException(code: 0): boom)\n", FILE_APPEND);
$poll();
same(9, count($state->entries), 'following picks up a new entry');
same('error', $state->entries[8]['level'], 'CRITICAL counts as an error');
file_put_contents($file, "[stacktrace]\n#0 /var/www/app/artisan(13): run()\n\"}\n", FILE_APPEND);
$poll();
same(9, count($state->entries), 'a trace written later joins its entry');
check(str_contains($state->entries[8]['detail'], '#0 /var/www/app/artisan(13)'), 'the late trace is in the entry');
$app->frame();
check($app->find('entry-8') !== null, 'the new entry is shown');

// Rotation: the file starts over.
file_put_contents($file, '[' . date('Y-m-d H:i:s') . "] local.INFO: Fresh log\n");
$poll();
same(['Fresh log'], array_column($state->entries, 'message'), 'a rotated log is read again');

// Plain logs: one entry per line, levels from keywords.
$plain = "$logsDir/app.log";
file_put_contents($plain, "server started\nWARN disk almost full\nfatal error: out of memory\n");
$load($plain);
same(['info', 'warning', 'error'], array_column($state->entries, 'level'), 'plain log lines get levels from keywords');
$app->clickId('follow');
same(false, $state->follow, 'the Follow switch turns following off');
