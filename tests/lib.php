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
function fakeSet(string $key, string $value): void
{
    $done = false;
    $bus = PhpUi\DBus::session();
    $bus->call('org.myphp.Test', '/org/myphp/Test', 'org.myphp.Test', 'Set', 'ss', [$key, $value], function ($result, $error) use (&$done) { $done = true; same(null, $error, 'configure fake'); });
    check(until(function () use (&$done) { return $done; }, poll: $bus->poll(...)), 'fake configured');
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
