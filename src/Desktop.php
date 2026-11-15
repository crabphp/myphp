<?php

declare(strict_types=1);

namespace PhpUi;

/** Linux desktop services, advanced by the app's main loop. */
final class Desktop
{
    public static string $appName = 'PHP UI';
    private static ?string $error = null;
    private static ?DBus $bus = null;
    private static array $jobs = [], $programs = [], $deferred = [];
    private static bool $registered = false;

    public static function lastError(): ?string { return self::$error; }

    private static function init(): void
    {
        if (self::$registered) return;
        self::$registered = true;
        register_shutdown_function(self::shutdown(...));
    }

    private static function mode(): string
    {
        return getenv('PHPUI_DESKTOP') ?: 'auto';
    }

    private static function connection(): ?DBus
    {
        self::init();
        if (self::mode() === 'fallback') return null;
        return self::$bus = DBus::session();
    }

    /** Catch application callback failures too: desktop polling never unwinds the loop. */
    private static function deliver(?callable $done, mixed ...$args): void
    {
        if (!$done) return;
        try { $done(...$args); } catch (\Throwable $e) { self::$error = $e->getMessage(); }
    }

    private static function defer(callable $done): void
    {
        self::init();
        self::$deferred[] = $done;
    }

    public static function has(string $program): bool
    {
        $path = getenv('PATH') ?: '';
        $key = "$path\0$program";
        if (isset(self::$programs[$key])) return self::$programs[$key];
        $paths = str_contains($program, '/') ? [$program] : array_map(fn ($dir) => ($dir ?: '.') . '/' . $program, explode(PATH_SEPARATOR, $path));
        foreach ($paths as $file) if (is_file($file) && is_executable($file)) return self::$programs[$key] = true;
        return self::$programs[$key] = false;
    }

    /** @internal Run an argv array, never a shell string. */
    public static function spawn(array $argv, ?callable $done = null, ?array $env = null): bool
    {
        self::init();
        if (!$argv || !self::has($argv[0])) { self::$error = 'Program not found: ' . ($argv[0] ?? ''); return false; }
        try {
            $process = @proc_open($argv, [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes, env_vars: $env === null ? null : $env + getenv());
            if (!is_resource($process)) { self::$error = 'Could not start ' . $argv[0]; return false; }
            stream_set_blocking($pipes[1], false);
            self::$jobs[] = ['process' => $process, 'pipe' => $pipes[1], 'out' => '', 'done' => $done];
            return true;
        } catch (\Throwable $e) { self::$error = $e->getMessage(); return false; }
    }

    public static function poll(): bool
    {
        $ran = false;
        try { if (self::$bus) $ran = self::$bus->poll(); }
        catch (\Throwable $e) { self::$error = $e->getMessage(); }
        foreach (array_keys(self::$jobs) as $id) {
            $job = &self::$jobs[$id];
            if (is_resource($job['pipe'])) $job['out'] .= stream_get_contents($job['pipe'], 65536);
            $status = proc_get_status($job['process']);
            if ($status['running']) { unset($job); continue; }
            if (is_resource($job['pipe'])) {
                $job['out'] .= stream_get_contents($job['pipe']);
                fclose($job['pipe']);
            }
            $exit = $status['exitcode'];
            proc_close($job['process']); // Only after proc_get_status reported exit.
            [$done, $out] = [$job['done'], $job['out']];
            unset($job, self::$jobs[$id]);
            if ($done) { $ran = true; self::deliver($done, $exit, $out); }
        }
        $queue = self::$deferred;
        self::$deferred = [];
        foreach ($queue as $done) { $ran = true; self::deliver($done); }
        return $ran;
    }

    public static function waitHint(): float
    {
        return self::$jobs || self::$deferred || self::$bus?->pending() ? 1 / 60 : 0.5;
    }

    public static function shutdown(): void
    {
        foreach (self::$jobs as &$job) {
            if (proc_get_status($job['process'])['running']) {
                proc_terminate($job['process']);
                // A child that ignores TERM must not delay PHP's resource teardown.
                proc_terminate($job['process'], 9);
            }
            if (is_resource($job['pipe'])) fclose($job['pipe']);
            $job['done'] = null;
        }
        unset($job);
        self::$deferred = [];
        self::$bus?->close();
        self::$bus = null;
    }
}
