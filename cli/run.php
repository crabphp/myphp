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
    $watch = in_array($args[0] ?? '', ['--watch', '-w'], true);
    if ($watch) array_shift($args);
    $file = array_shift($args) ?? 'app.php';
    if (!is_file($file)) return myphpError("$file not found. Create an app with \"myphp init <name>\".");
    return $watch ? myphpWatch($file, $args) : myphpRun($file, $args);
}

/**
 * Code and asset files of a project with their change times. Data an app writes
 * (JSON, text, databases, logs) is not watched, so saving it does not reload.
 */
function myphpSnapshot(string $dir): array
{
    $files = [];
    $walk = function (string $dir) use (&$walk, &$files): void {
        foreach (@scandir($dir) ?: [] as $name) {
            if ($name[0] === '.' || in_array($name, ['vendor', 'build', 'node_modules'], true)) continue;
            $path = "$dir/$name";
            if (is_link($path)) continue;
            if (is_dir($path)) $walk($path);
            elseif (preg_match('/\.(php|phtml|inc|png|jpe?g|gif|bmp|svg|ttf|otf)$/i', $name) && is_file($path)) {
                // File times have one-second steps: hash normal-sized files to see quick saves too.
                $size = filesize($path);
                $files[$path] = $size < 2_000_000 ? hash_file('xxh3', $path) : filemtime($path) . ":$size";
            }
        }
    };
    clearstatcache();
    $walk($dir);
    return $files;
}

/**
 * Run the app in a child process and restart it when a project file changes.
 * Closing the window ends watching; a crash waits for the next change.
 */
function myphpWatch(string $file, array $args): int
{
    $dir = dirname(realpath($file));
    $self = PHP_SAPI === 'micro' ? [Phar::running(false)] : [PHP_BINARY, realpath($_SERVER['SCRIPT_FILENAME'])];
    $command = [...$self, 'run', $file, ...$args];
    $poll = (float) (getenv('MYPHP_WATCH_INTERVAL') ?: 0.3);
    fwrite(STDERR, "myphp: watching $dir for changes (Ctrl+C to stop)\n");
    $files = myphpSnapshot($dir);
    while (true) {
        $child = proc_open($command, [STDIN, STDOUT, STDERR], $pipes);
        if (!is_resource($child)) return myphpError('cannot start the app');
        [$changed, $code] = [false, 0];
        while (true) {
            usleep((int) ($poll * 1e6));
            $status = proc_get_status($child);
            $now = myphpSnapshot($dir);
            if ($now !== $files) {
                $files = $now;
                $changed = true;
                if ($status['running']) {
                    proc_terminate($child);
                    for ($i = 0; $i < 50 && proc_get_status($child)['running']; $i++) usleep(20000);
                    if (proc_get_status($child)['running']) proc_terminate($child, 9);
                }
                break;
            }
            if (!$status['running']) { $code = $status['exitcode']; break; }
        }
        proc_close($child);
        if ($changed) {
            fwrite(STDERR, "myphp: reloading\n");
            continue;
        }
        // The app ended by itself: a closed window stops watching, a crash waits for a fix.
        if ($code === 0) return 0;
        fwrite(STDERR, "myphp: the app exited with code $code; waiting for changes\n");
        do {
            usleep((int) ($poll * 1e6));
            $now = myphpSnapshot($dir);
        } while ($now === $files);
        $files = $now;
        fwrite(STDERR, "myphp: reloading\n");
    }
}

function cmdDemo(array $args): int
{
    $name = array_shift($args) ?? 'dashboard';
    if (!in_array($name, ['dashboard', 'desktop', 'gallery', 'notes', 'player', 'todo'], true)) {
        return myphpError('demo name must be dashboard, desktop, gallery, notes, player or todo', 2);
    }
    return myphpRun(MYPHP_ROOT . "/examples/$name.php", $args);
}
