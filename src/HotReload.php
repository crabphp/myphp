<?php

declare(strict_types=1);

namespace PhpUi;

/**
 * Hot reload for `myphp run --watch`: when a project file changes, the app's script
 * runs again inside the same process and window. The new render function, handlers
 * and timers take over; state() values, focus, scroll positions, text being edited
 * and zoom stay. Images and icons reload too.
 *
 * Code PHP cannot load twice (a script or file that declares named functions or
 * classes) restarts the app instead, as does a change to the toolkit itself. A file
 * that does not parse, or a script that throws, shows the error in the window and
 * keeps the last working version running.
 */
final class HotReload
{
    /** Exit code that asks `myphp run --watch` to restart the app. */
    public const RESTART = 75;

    /** The error of the last reload, shown over the app until a reload works. */
    public static ?string $error = null;
    private static ?string $script = null;
    private static array $files = [];
    private static float $next = 0;
    private static bool $reloading = false;

    /** Whether this process was started by `myphp run --watch`. */
    public static function enabled(): bool
    {
        if (self::$script !== null) return true;
        $script = getenv('MYPHP_HOT_RELOAD');
        if (!$script || !is_file($script)) return false;
        self::$script = realpath($script) ?: $script;
        self::$files = self::snapshot(dirname(self::$script));
        return true;
    }

    /** Whether a reload is running the script again: App then adopts the window. */
    public static function reloading(): bool
    {
        return self::$reloading;
    }

    /** Called on every loop turn; checks the files a few times a second. */
    public static function poll(App $app): bool
    {
        if (self::$reloading || microtime(true) < self::$next) return false;
        $interval = getenv('MYPHP_WATCH_INTERVAL');
        self::$next = microtime(true) + (is_numeric($interval) ? (float) $interval : 0.3);
        $now = self::snapshot(dirname(self::$script));
        if ($now === self::$files) return false;
        $changed = array_keys(array_diff_assoc($now, self::$files) + array_diff_key(self::$files, $now));
        self::$files = $now;
        self::reload($app, $changed);
        return true;
    }

    /** @param string[] $changed */
    private static function reload(App $app, array $changed): void
    {
        $php = array_values(array_filter($changed, fn ($f) => preg_match('/\.(php|phtml|inc)$/i', $f)));
        $assets = array_diff($changed, $php);
        if ($assets) $app->forgetAssets();
        if (!$php) { fwrite(STDERR, "myphp: reloaded assets\n"); return; }

        // Code that cannot run twice in one process: restart instead.
        foreach ($php as $file) {
            if (!is_file($file)) continue;
            $code = (string) file_get_contents($file);
            try {
                token_get_all($code, TOKEN_PARSE);
            } catch (\ParseError $e) {
                self::fail(sprintf('%s:%d: %s', self::relative($file), $e->getLine(), $e->getMessage()));
                return;
            }
            if (self::declares($code) || ($file !== self::$script && self::loadedOnce($file))) {
                fwrite(STDERR, "myphp: " . self::relative($file) . " declares functions or classes; restarting\n");
                exit(self::RESTART);
            }
        }

        if (function_exists('opcache_invalidate')) foreach ($php as $file) @opcache_invalidate($file, true);
        self::$reloading = true;
        State::beginReload();
        $old = App::current();
        try {
            (static function (): void {
                global $argv, $argc;
                require func_get_arg(0);
            })(self::$script);
            $new = App::current();
            if ($new === $old) throw new \RuntimeException('The script did not create an App');
            $old->retire();
            self::$error = null;
            fwrite(STDERR, "myphp: reloaded\n");
        } catch (\Throwable $e) {
            App::restore($old);
            State::cancelReload();
            self::fail(sprintf('%s: %s (%s:%d)', get_class($e), $e->getMessage(), self::relative($e->getFile()), $e->getLine()));
        } finally {
            self::$reloading = false;
            State::endReload();
        }
    }

    private static function fail(string $message): void
    {
        self::$error = $message;
        App::current()?->invalidate();
        fwrite(STDERR, "myphp: reload failed: $message\n");
    }

    /** A file the toolkit or a require_once loaded: running the script again will not load it again. */
    private static function loadedOnce(string $file): bool
    {
        $real = realpath($file) ?: $file;
        if (!in_array($real, get_included_files(), true)) return false;
        // Files the script itself pulls in with require/include run again with it; only
        // a scan of the script can tell, so trust it for files beside the script.
        return str_starts_with($real, dirname(__DIR__) . DIRECTORY_SEPARATOR);
    }

    /**
     * Whether $code declares a named function, class, interface, trait or enum,
     * which PHP cannot declare again. Methods and anonymous classes are fine.
     */
    public static function declares(string $code): bool
    {
        $tokens = array_values(array_filter(token_get_all($code), fn ($t) => !is_array($t) || !in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)));
        $stack = [];     // what each open brace belongs to: 'class' or 'other'
        $classNext = false;
        foreach ($tokens as $i => $t) {
            $id = is_array($t) ? $t[0] : $t;
            $prev = $tokens[$i - 1] ?? null;
            $prevId = is_array($prev) ? $prev[0] : $prev;
            $next = $tokens[$i + 1] ?? null;
            if (in_array($id, [T_CLASS, T_INTERFACE, T_TRAIT], true) || (defined('T_ENUM') && $id === T_ENUM)) {
                if ($prevId === T_DOUBLE_COLON) continue; // Foo::class
                $classNext = true;
                if ($prevId !== T_NEW && is_array($next) && $next[0] === T_STRING) return true;
            } elseif ($id === T_FUNCTION) {
                if ($prevId === T_USE) continue; // use function Foo\bar;
                $inClass = in_array('class', $stack, true);
                if (!$inClass && is_array($next) && $next[0] === T_STRING) return true;
                // function &name(): the & is its own token kind since PHP 8.1.
                $amp = $next === '&' || (is_array($next) && $next[1] === '&');
                if (!$inClass && $amp && is_array($tokens[$i + 2] ?? null) && $tokens[$i + 2][0] === T_STRING) return true;
            } elseif ($id === '{' || $id === T_CURLY_OPEN || $id === T_DOLLAR_OPEN_CURLY_BRACES) {
                $stack[] = $classNext && $id === '{' ? 'class' : 'other';
                $classNext = false;
            } elseif ($id === '}') {
                array_pop($stack);
            }
        }
        return false;
    }

    /** Code and asset files of the project with their contents' hashes. */
    public static function snapshot(string $dir): array
    {
        $files = [];
        $walk = function (string $dir) use (&$walk, &$files): void {
            foreach (@scandir($dir) ?: [] as $name) {
                if ($name[0] === '.' || in_array($name, ['vendor', 'build', 'node_modules'], true)) continue;
                $path = "$dir/$name";
                if (is_link($path)) continue;
                if (is_dir($path)) $walk($path);
                elseif (preg_match('/\.(php|phtml|inc|png|jpe?g|gif|bmp|svg|ttf|otf)$/i', $name) && is_file($path)) {
                    $size = filesize($path);
                    $files[$path] = $size < 2_000_000 ? hash_file('xxh3', $path) : filemtime($path) . ":$size";
                }
            }
        };
        clearstatcache();
        $walk($dir);
        return $files;
    }

    private static function relative(string $file): string
    {
        $base = dirname((string) self::$script) . DIRECTORY_SEPARATOR;
        return str_starts_with($file, $base) ? substr($file, strlen($base)) : $file;
    }
}
