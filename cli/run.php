<?php

declare(strict_types=1);

function myphpRun(string $file, array $args): int
{
    require MYPHP_ROOT . '/src/autoload.php';
    $GLOBALS['argv'] = $_SERVER['argv'] = [$file, ...$args];
    $GLOBALS['argc'] = $_SERVER['argc'] = count($GLOBALS['argv']);
    (static function (): void {
        global $argv, $argc;
        require func_get_arg(0);
    })(str_starts_with($file, 'phar://') ? $file : realpath($file));
    return 0;
}

function cmdRun(array $args): int
{
    $file = array_shift($args) ?? 'app.php';
    if (!is_file($file)) return myphpError("$file not found. Create an app with \"myphp init <name>\".");
    return myphpRun($file, $args);
}

function cmdDemo(array $args): int
{
    $name = array_shift($args) ?? 'dashboard';
    if (!in_array($name, ['dashboard', 'desktop', 'todo'], true)) {
        return myphpError('demo name must be dashboard, desktop or todo', 2);
    }
    return myphpRun(MYPHP_ROOT . "/examples/$name.php", $args);
}
