<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';

$binary = getenv('PHPUI_TEST_BINARY');
$argv = $binary ? [$binary] : [PHP_BINARY, __DIR__ . '/../examples/desktop.php'];
$dir = getenv('PHPUI_TEST_TMP') . '/desktop-example';
$process = proc_open($argv, [1 => ['pipe', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes,
    env_vars: ['PHPUI_SCREENSHOTS' => $dir, 'PHPUI_EXAMPLE' => 'desktop'] + getenv());
$output = stream_get_contents($pipes[1]); fclose($pipes[1]);
same(0, proc_close($process), 'desktop example screenshot flow succeeds');
$result = json_decode(trim($output), true, flags: JSON_THROW_ON_ERROR);
check(in_array('Confirm: true', $result['log'], true), 'example confirms through the OK button');
check(in_array('Drop: ["/tmp/first file.txt","/tmp/é.png"]', $result['log'], true), 'example logs scripted dropped paths');
same('Hello from PHP ✓', $result['clipboard'], 'example copy and paste round trip');
same(['proc_open' => true, 'stream_socket_client' => true, 'posix_geteuid' => true], $result['runtime'], 'runtime has required desktop capabilities');
foreach (['start', 'message', 'confirm', 'results', 'dark'] as $shot) check(is_file("$dir/desktop-$shot.png"), "example writes $shot screenshot");
same('#262626', pixel("$dir/desktop-dark.png", 0, 0), 'example dark screenshot has expected background pixel');
