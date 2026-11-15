<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';
require __DIR__ . '/../src/autoload.php';
use PhpUi\Desktop;

same(0.5, Desktop::waitHint(), 'idle desktop sleeps half a second');
$result = null;
$start = microtime(true); $turns = 0;
check(Desktop::spawn(['sh', '-c', 'sleep 0.3; echo hi; exit 3'], function ($code, $out) use (&$result) { $result = [$code, $out]; }), 'child starts');
same(1 / 60, Desktop::waitHint(), 'pending child wakes at 60Hz');
while ($result === null && microtime(true) - $start < 2) { Desktop::poll(); $turns++; usleep(200); }
same([3, "hi\n"], $result, 'child exit status and stdout are preserved');
check($turns > 300 && microtime(true) - $start >= .3, 'child never blocks the polling loop');
same(false, Desktop::spawn(['no-such-myphp-program']), 'missing program returns false');
check(str_contains(Desktop::lastError(), 'not found'), 'missing program records an error');
Desktop::spawn(['sleep', '30']);
$jobs = (new ReflectionProperty(Desktop::class, 'jobs'))->getValue();
$pid = proc_get_status(end($jobs)['process'])['pid'];
Desktop::shutdown();
$end = microtime(true) + 1;
do { Desktop::poll(); if (!posix_kill($pid, 0)) break; usleep(1000); } while (microtime(true) < $end);
check(!posix_kill($pid, 0), 'shutdown kills and reaps the owned child');
Desktop::shutdown();
same(0.5, Desktop::waitHint(), 'shutdown is idempotent');
