<?php

declare(strict_types=1);

define('MYPHP_ROOT', dirname(__DIR__));
ini_set('display_errors', 'stderr');
error_reporting(E_ALL);
require __DIR__ . '/run.php';
require __DIR__ . '/init.php';
require __DIR__ . '/build.php';
require __DIR__ . '/upgrade.php';

function myphpVersion(): string
{
    $file = MYPHP_ROOT . '/version';
    return is_file($file) ? trim(file_get_contents($file)) : 'dev';
}

function myphpError(string $message, int $code = 1): int
{
    fwrite(STDERR, "myphp: $message\n");
    return $code;
}

function myphpHelp(): int
{
    echo "myphp - native desktop apps in PHP\n\nUsage:\n";
    foreach ([
        'init' => '  myphp init <name>     create a new app',
        'run' => '  myphp run [file]      run an app (default: app.php); --watch reloads on save',
        'build' => '  myphp build [file]    build one executable (default: app.php)',
        'demo' => '  myphp demo [name]     run a bundled example: dashboard, desktop, gallery, notes, player, todo',
        'upgrade' => '  myphp upgrade         update myphp; --nightly for the newest unreleased build',
    ] as $name => $line) {
        if (function_exists('cmd' . ucfirst($name))) echo "$line\n";
    }
    echo "  myphp version        print the version\n  myphp help           print this help\n";
    return 0;
}

function myphpMain(array $args): int
{
    // What a Windows upgrade left behind: the old binary could only be renamed while running.
    if (PHP_OS_FAMILY === 'Windows' && PHP_SAPI === 'micro' && is_file(Phar::running(false) . '.old')) @unlink(Phar::running(false) . '.old');
    $command = array_shift($args) ?? 'help';
    if (in_array($command, ['help', '-h', '--help'], true)) return myphpHelp();
    if (in_array($command, ['version', '-v', '--version'], true)) {
        echo 'myphp ' . myphpVersion() . ' (PHP ' . PHP_VERSION . ")\n";
        return 0;
    }
    if (in_array($command, ['init', 'run', 'build', 'demo', 'upgrade'], true)) {
        $handler = 'cmd' . ucfirst($command);
        if (function_exists($handler)) return $handler($args);
    }
    return myphpError("unknown command \"$command\"\nRun \"myphp help\".", 2);
}

try {
    exit(myphpMain(array_slice($argv, 1)));
} catch (Throwable $e) {
    exit(myphpError(get_class($e) . ': ' . $e->getMessage() . "\n" . $e->getTraceAsString()));
}
