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
register_shutdown_function(function () {
    global $failures, $checks;
    echo "$checks checks, $failures failures\n";
    if ($failures) exit(1);
});
