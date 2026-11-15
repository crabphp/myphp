<?php

declare(strict_types=1);

require __DIR__ . '/lib.php';

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
            throw new RuntimeException('Command timed out: ' . implode(' ', $command));
        }
        usleep(10000);
    } while (true);
    proc_close($process);
    $result = [$status['exitcode'], file_get_contents($out), file_get_contents($err)];
    unlink($out); unlink($err);
    return $result;
}

function cli(array $args, ?string $cwd = null, array $env = []): array
{
    $command = getenv('MYPHP_BIN') ? [getenv('MYPHP_BIN')] : [PHP_BINARY, __DIR__ . '/../bin/myphp'];
    return cliProcess([...$command, ...$args], $cwd, $env);
}

foreach ([[], ['help'], ['-h'], ['--help']] as $args) {
    [$code, $out, $err] = cli($args);
    same(0, $code, 'help succeeds');
    check(str_contains($out, 'myphp version'), 'help lists implemented commands');
    same('', $err, 'help has no errors');
}
foreach (['version', '-v', '--version'] as $command) {
    [$code, $out, $err] = cli([$command]);
    same(0, $code, 'version succeeds');
    check((bool) preg_match('/^myphp \S+ \(PHP 8\.\d+\.\d+\)\n$/', $out), 'version includes runtime');
    same('', $err, 'version has no errors');
}
[$code, $out, $err] = cli(['nope']);
same(2, $code, 'unknown command is usage error');
same('', $out, 'unknown command has no stdout');
check(str_contains($err, 'myphp: unknown command "nope"'), 'unknown command diagnostic');
