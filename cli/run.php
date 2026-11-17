<?php

declare(strict_types=1);

/**
 * The toolkit of a myphp checkout that contains $file (src/ beside cli/ and examples/),
 * so the repository's examples run with the code next to them, not the binary's copy.
 */
function myphpCheckoutToolkit(string $file): ?string
{
    if (str_starts_with($file, 'phar://') || !($dir = realpath(dirname($file)))) return null;
    for ($i = 0; $i < 4 && $dir !== dirname($dir); $i++, $dir = dirname($dir)) {
        if (is_file("$dir/src/autoload.php") && is_file("$dir/src/App.php") && is_file("$dir/cli/main.php")) return "$dir/src/autoload.php";
    }
    return null;
}

function myphpRun(string $file, array $args): int
{
    require myphpCheckoutToolkit($file) ?? MYPHP_ROOT . '/src/autoload.php';
    // Composer packages beside the app load automatically.
    $vendor = dirname(str_starts_with($file, 'phar://') ? $file : (string) realpath($file)) . '/vendor/autoload.php';
    if (is_file($vendor)) require_once $vendor;
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
    $runtime = myphpRuntimeFlag($args);
    $watch = in_array($args[0] ?? '', ['--watch', '-w'], true);
    if ($watch) array_shift($args);
    $file = array_shift($args) ?? 'app.php';
    if (!is_file($file)) return myphpError("$file not found. Create an app with \"myphp init <name>\".");
    // The core runtime hands apps that need HTTPS, SQLite, XML... to myphp-full.
    if (myphpFlavor() !== 'full' && !getenv('MYPHP_DELEGATED') && $runtime !== 'core') {
        $reason = $runtime === 'full' ? 'asked with --full' : myphpNeedsFull(dirname((string) realpath($file)));
        if ($reason !== null) return myphpDelegate(['run', ...($watch ? ['--watch'] : []), $file, ...$args], $reason);
    }
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
 * Run the app in a child process with hot reload: the app watches the project and
 * reloads changed code in its own window, keeping its state (PhpUi\HotReload). When
 * code cannot be reloaded in place the app exits with HotReload::RESTART and starts
 * again here. Closing the window ends watching; a crash waits for the next change.
 */
function myphpWatch(string $file, array $args): int
{
    $dir = dirname(realpath($file));
    $self = PHP_SAPI === 'micro' ? [Phar::running(false)] : [PHP_BINARY, realpath($_SERVER['SCRIPT_FILENAME'])];
    $command = [...$self, 'run', $file, ...$args];
    $poll = (float) (getenv('MYPHP_WATCH_INTERVAL') ?: 0.3);
    $env = getenv();
    $env['MYPHP_HOT_RELOAD'] = realpath($file);
    fwrite(STDERR, "myphp: watching $dir for changes (Ctrl+C to stop)\n");
    while (true) {
        $child = proc_open($command, [STDIN, STDOUT, STDERR], $pipes, null, $env);
        if (!is_resource($child)) return myphpError('cannot start the app');
        while (($status = proc_get_status($child))['running']) usleep((int) ($poll * 1e6));
        $code = $status['exitcode'];
        $files = myphpSnapshot($dir);
        proc_close($child);
        // The app asked for a fresh start: code it cannot reload in place changed.
        if ($code === 75) {
            fwrite(STDERR, "myphp: restarting\n");
            continue;
        }
        // A closed window stops watching, a crash waits for a fix.
        if ($code === 0) return 0;
        fwrite(STDERR, "myphp: the app exited with code $code; waiting for changes\n");
        do {
            usleep((int) ($poll * 1e6));
            $now = myphpSnapshot($dir);
        } while ($now === $files);
        fwrite(STDERR, "myphp: reloading\n");
    }
}
