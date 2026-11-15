<?php

declare(strict_types=1);

$failures = 0;
$checks = 0;
function check(bool $ok, string $what): void
{
    global $failures, $checks;
    $checks++;
    if (!$ok) $failures++;
    echo ($ok ? 'ok' : 'FAIL') . " - $what\n";
}
function same(mixed $expected, mixed $actual, string $what): void
{
    check($expected === $actual, $what);
    if ($expected !== $actual) echo '  expected ' . var_export($expected, true) . ', got ' . var_export($actual, true) . "\n";
}
function until(callable $ready, float $seconds = 3, ?callable $poll = null): bool
{
    $end = microtime(true) + $seconds;
    do {
        ($poll ?? PhpUi\Desktop::poll(...))();
        if ($ready()) return true;
        usleep(1000);
    } while (microtime(true) < $end);
    return false;
}
/** Run with isolated output files, bounded time, and no shell interpolation. */
function cliProcess(array $command, ?string $cwd = null, array $env = [], float $timeout = 10): array
{
    $out = tempnam(getenv('PHPUI_TEST_TMP'), 'stdout-');
    $err = tempnam(getenv('PHPUI_TEST_TMP'), 'stderr-');
    $process = proc_open($command, [0 => ['file', '/dev/null', 'r'],
        1 => ['file', $out, 'w'], 2 => ['file', $err, 'w']], $pipes,
        $cwd ?? getenv('PHPUI_TEST_TMP'), $env + getenv());
    $deadline = microtime(true) + $timeout;
    do {
        $status = proc_get_status($process);
        if (!$status['running']) break;
        if (microtime(true) > $deadline) {
            proc_terminate($process, 9);
            proc_close($process);
            unlink($out); unlink($err);
            throw new RuntimeException('Command timed out: ' . implode(' ', $command));
        }
        usleep(10000);
    } while (true);
    proc_close($process);
    $result = [$status['exitcode'], file_get_contents($out), file_get_contents($err)];
    unlink($out); unlink($err);
    return $result;
}

function fakeSet(string $key, string $value): void
{
    $done = false;
    $bus = PhpUi\DBus::session();
    $bus->call('org.myphp.Test', '/org/myphp/Test', 'org.myphp.Test', 'Set', 'ss', [$key, $value], function ($result, $error) use (&$done) { $done = true; same(null, $error, 'configure fake'); });
    check(until(function () use (&$done) { return $done; }, poll: $bus->poll(...)), 'fake configured');
}
function pixel(string $file, int $x, int $y): string
{
    $process = proc_open(['magick', $file, '-format', "%[hex:p{{$x},{$y}}]", 'info:'], [1 => ['pipe', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
    $value = stream_get_contents($pipes[1]); fclose($pipes[1]);
    if (proc_close($process) !== 0) throw new RuntimeException('Pixel probe failed');
    return '#' . strtolower(substr($value, 0, 6));
}
function fakeCalls(string $method): array
{
    return array_values(array_filter(array_map(fn ($line) => json_decode($line, true), file(getenv('FAKE_LOG'), FILE_IGNORE_NEW_LINES) ?: []), fn ($call) => ($call['method'] ?? '') === $method));
}
register_shutdown_function(function () {
    global $failures, $checks;
    echo "$checks checks, $failures failures\n";
    if ($failures) exit(1);
});
