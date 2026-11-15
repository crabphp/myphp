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
    private const PORTAL = 'org.freedesktop.portal.Desktop';
    private const PORTAL_PATH = '/org/freedesktop/portal/desktop';
    private static ?array $dialog = null;
    private static int $token = 0;
    private static ?DBus $chooserBus = null;

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
        if (self::$bus && !self::$bus->connected() && self::$dialog && self::$dialog['path'] !== null) {
            self::$error = 'disconnected';
            self::finishFile(null);
            $ran = true;
        }
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
        return self::$dialog || self::$jobs || self::$deferred || self::$bus?->pending() ? 1 / 60 : 0.5;
    }

    /** Open a safe URL or existing path in the user's default application. */
    public static function open(string $target, ?callable $done = null): bool
    {
        self::$error = null;
        $scheme = strtolower((string) parse_url($target, PHP_URL_SCHEME));
        if ($target === '' || str_starts_with($target, '-') || str_contains($target, "\0")
            || (!in_array($scheme, ['http', 'https', 'mailto', 'file'], true) && !file_exists($target))) {
            self::$error = 'Refused URL or missing file';
            if ($done) self::defer(fn () => self::deliver($done, false));
            return false;
        }
        if (file_exists($target)) $target = realpath($target);
        $argv = self::has('xdg-open') ? ['xdg-open', $target] : ['gio', 'open', $target];
        if (self::spawn($argv, function ($code) use ($done) {
            if ($code !== 0) self::$error = "Could not open target (exit $code)";
            self::deliver($done, $code === 0);
        })) return true;
        if ($done) self::defer(fn () => self::deliver($done, false));
        return false;
    }

    public static function openFile(callable $done, string $title = 'Open', array $filters = [], bool $multiple = false, ?string $folder = null): void
    {
        self::choose($done, 'OpenFile', $title, $filters, $multiple, false, $folder, null);
    }

    public static function saveFile(callable $done, string $title = 'Save', ?string $name = null, ?string $folder = null, array $filters = []): void
    {
        self::choose(fn ($paths) => $done($paths[0] ?? null), 'SaveFile', $title, $filters, false, false, $folder, $name);
    }

    public static function pickFolder(callable $done, string $title = 'Select Folder', ?string $folder = null): void
    {
        self::choose(fn ($paths) => $done($paths[0] ?? null), 'OpenFile', $title, [], false, true, $folder, null);
    }

    private static function choose(callable $done, string $method, string $title, array $filters, bool $multiple, bool $directory, ?string $folder, ?string $name): void
    {
        if (self::$dialog) {
            self::$error = 'A file dialog is already open';
            self::defer(fn () => self::deliver($done, null));
            return;
        }
        self::$error = null;
        $token = 'phpui' . ++self::$token;
        $options = ['handle_token' => ['s', $token], 'multiple' => ['b', $multiple]];
        if ($directory) $options['directory'] = ['b', true];
        if ($folder !== null) $options['current_folder'] = ['ay', array_values(unpack('C*', $folder . "\0"))];
        if ($name !== null) $options['current_name'] = ['s', $name];
        if ($filters) {
            $list = [];
            foreach ($filters as $label => $extensions) $list[] = [$label, array_map(fn ($ext) => [0, $ext === '*' ? '*' : '*.' . ltrim($ext, '.')], $extensions)];
            $options['filters'] = ['a(sa(us))', $list];
        }
        self::$dialog = compact('done', 'method', 'title', 'options', 'multiple', 'directory', 'folder', 'name', 'filters', 'token') + ['path' => null, 'early' => [], 'replied' => false];
        $bus = self::connection();
        if (!$bus) { self::defer(self::fileFallback(...)); return; }
        if (self::$chooserBus !== $bus) {
            self::$chooserBus = $bus;
            $bus->on('org.freedesktop.portal.Request', 'Response', function ($args, $path) {
                if (!self::$dialog) return;
                if ($path === self::$dialog['path']) self::fileResponse($args);
                elseif (!self::$dialog['replied']) self::$dialog['early'][$path] = $args;
            });
        }
        self::$dialog['path'] = self::PORTAL_PATH . '/request/' . str_replace('.', '_', ltrim($bus->name, ':')) . '/' . $token;
        $send = function () use ($bus, $token) {
            if ((self::$dialog['token'] ?? null) !== $token) return;
            $d = self::$dialog;
            $bus->call(self::PORTAL, self::PORTAL_PATH, 'org.freedesktop.portal.FileChooser', $d['method'], 'ssa{sv}', ['', $d['title'], $d['options']], function ($result, $error) use ($token) {
                if ((self::$dialog['token'] ?? null) !== $token) return;
                if ($error) { self::$error = $error; self::fileFallback(); return; }
                self::$dialog['path'] = $result[0];
                self::$dialog['replied'] = true;
                if (isset(self::$dialog['early'][$result[0]])) self::fileResponse(self::$dialog['early'][$result[0]]);
                if (self::$dialog) self::$dialog['early'] = [];
            });
        };
        if (!$directory) { $send(); return; }
        $bus->call(self::PORTAL, self::PORTAL_PATH, 'org.freedesktop.DBus.Properties', 'Get', 'ss', ['org.freedesktop.portal.FileChooser', 'version'], function ($result, $error) use ($send, $token) {
            if ((self::$dialog['token'] ?? null) !== $token) return;
            if ($error || ($result[0][1] ?? 0) < 3) { self::$error = $error ?? 'Folder selection needs FileChooser version 3'; self::fileFallback(); }
            else $send();
        });
    }

    private static function fileResponse(array $args): void
    {
        if (($args[0] ?? 2) !== 0) { self::finishFile(null); return; }
        $paths = [];
        foreach ($args[1]['uris'][1] ?? [] as $uri) {
            if (parse_url($uri, PHP_URL_SCHEME) !== 'file') { self::$error = 'File dialog returned a non-file URI'; self::finishFile(null); return; }
            $paths[] = rawurldecode(parse_url($uri, PHP_URL_PATH) ?? '');
        }
        self::finishFile($paths ?: null);
    }

    private static function finishFile(?array $paths): void
    {
        $done = self::$dialog['done'] ?? null;
        self::$dialog = null;
        self::deliver($done, $paths);
    }

    private static function fileFallback(): void
    {
        if (!self::$dialog) return;
        if (self::mode() === 'portal') {
            self::$error ??= 'No desktop portal available';
            self::finishFile(null);
            return;
        }
        $d = self::$dialog;
        self::$dialog['path'] = null;
        $argv = ['zenity', '--file-selection', '--title=' . $d['title']];
        if ($d['multiple']) array_push($argv, '--multiple', "--separator=\n");
        if ($d['directory']) $argv[] = '--directory';
        if ($d['method'] === 'SaveFile') $argv[] = '--save';
        $filename = ($d['folder'] !== null ? rtrim($d['folder'], '/') . '/' : '') . ($d['name'] ?? '');
        if ($filename !== '') $argv[] = '--filename=' . $filename;
        foreach ($d['filters'] as $label => $extensions) {
            $argv[] = '--file-filter=' . $label . ' | ' . implode(' ', array_map(fn ($ext) => $ext === '*' ? '*' : '*.' . ltrim($ext, '.'), $extensions));
        }
        if (!self::spawn($argv, function ($code, $stdout) {
            if ($code !== 0 && $code !== 1) self::$error = "zenity exited with code $code";
            $out = rtrim($stdout, "\r\n");
            self::finishFile($code === 0 && $out !== '' ? explode("\n", $out) : null);
        })) {
            self::$error = 'No file dialog available: install xdg-desktop-portal or zenity';
            self::finishFile(null);
        }
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
        self::$dialog = null;
        self::$chooserBus = null;
        self::$bus?->close();
        self::$bus = null;
    }
}
