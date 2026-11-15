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
    private static ?bool $systemDark = null;
    private static ?DBus $themeBus = null;
    private static ?int $themeListener = null;
    private static ?\Closure $themeChange = null;
    private static int $themeGeneration = 0;

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
        if (self::$dialog || self::$jobs || self::$deferred || self::$bus?->pending()) return 1 / 60;
        return self::$themeListener !== null ? 0.25 : 0.5;
    }

    public static function systemDark(): ?bool { return self::$systemDark; }

    public static function followSystemTheme(?callable $onChange = null): void
    {
        Theme::use('system');
        self::$themeChange = $onChange === null ? null : $onChange(...);
        if (self::$themeListener !== null) self::$themeBus?->off(self::$themeListener);
        self::$themeListener = null;
        $generation = ++self::$themeGeneration;
        $bus = self::connection();
        if (!$bus) { self::themeFallback($generation); return; }
        self::$themeBus = $bus;
        self::$themeListener = $bus->on('org.freedesktop.portal.Settings', 'SettingChanged', function ($args) {
            if (($args[0] ?? '') === 'org.freedesktop.appearance' && ($args[1] ?? '') === 'color-scheme') self::applyScheme($args[2]);
        }, self::PORTAL_PATH);
        self::readScheme('ReadOne', $generation);
    }

    private static function readScheme(string $method, int $generation): void
    {
        self::$themeBus->call(self::PORTAL, self::PORTAL_PATH, 'org.freedesktop.portal.Settings', $method, 'ss', ['org.freedesktop.appearance', 'color-scheme'], function ($result, $error) use ($method, $generation) {
            if ($generation !== self::$themeGeneration) return;
            if ($error) {
                if ($method === 'ReadOne' && str_contains($error, 'UnknownMethod')) { self::readScheme('Read', $generation); return; }
                self::$error = $error;
                self::themeFallback($generation);
            } else self::applyScheme($result[0]);
        });
    }

    private static function applyScheme(mixed $value): void
    {
        while (is_array($value) && count($value) === 2) $value = $value[1];
        self::$systemDark = match ($value) { 1 => true, 2 => false, default => null };
        if (self::$systemDark === null || Theme::$source !== 'system') return;
        $mode = self::$systemDark ? 'dark' : 'light';
        if (Theme::$mode === $mode) return;
        Theme::set($mode);
        self::deliver(self::$themeChange, self::$systemDark);
    }

    private static function themeFallback(int $generation): void
    {
        if (self::$themeListener !== null) self::$themeBus?->off(self::$themeListener);
        self::$themeListener = null;
        if (self::mode() === 'portal') { self::$error ??= 'No desktop portal available'; return; }
        self::spawn(['gsettings', 'get', 'org.gnome.desktop.interface', 'color-scheme'], function ($code, $stdout) use ($generation) {
            if ($generation !== self::$themeGeneration) return;
            if ($code !== 0) { self::$error = "gsettings exited with code $code"; return; }
            self::applyScheme(match (trim($stdout, " \r\n\t'\"")) { 'prefer-dark' => 1, 'prefer-light' => 2, default => 0 });
        });
    }

    public static function clipboard(): string
    {
        $app = App::current();
        if (!$app) return '';
        try { return glfwGetClipboardString($app->window) ?? ''; }
        catch (\Throwable) { return ''; }
    }

    public static function setClipboard(string $text): void
    {
        $app = App::current();
        if (!$app) { self::$error = 'Clipboard needs an App'; return; }
        glfwSetClipboardString($app->window, $text);
    }

    public static function message(string $title, string $message, array $buttons = ['OK'], ?callable $done = null,
        string $type = 'info', ?int $default = null, ?int $cancel = null, array $destructive = []): void
    {
        $app = App::current();
        if (!$app || !$buttons) {
            self::$error = $app ? 'Message needs at least one button' : 'Message needs an App';
            self::defer(fn () => self::deliver($done, null));
            return;
        }
        $buttons = array_values($buttons);
        $default ??= 0;
        if ($cancel === null) {
            $cancel = 0;
            foreach ($buttons as $i => $label) if (in_array(strtolower($label), ['cancel', 'no'], true)) { $cancel = $i; break; }
        }
        if (!isset($buttons[$default], $buttons[$cancel])) throw new \InvalidArgumentException('Invalid message button index');
        $app->modal(function () use ($app, $title, $message, $buttons, $done, $type, $default, $cancel, $destructive) {
            $box = messageBox($title, $message, $buttons, function ($i) use ($app, $done) {
                $app->closeModal();
                self::deliver($done, $i);
            }, $type, $default, $destructive);
            $box->style['modalCancel'] = $cancel;
            return $box;
        });
    }

    public static function confirm(string $title, string $message, callable $done, string $ok = 'OK', string $cancel = 'Cancel', bool $destructive = false): void
    {
        self::message($title, $message, [$cancel, $ok], fn ($i) => $done($i === 1), 'question', 1, 0, $destructive ? [1] : []);
    }

    public static function error(string $title, string $message, ?callable $done = null): void
    {
        self::message($title, $message, done: $done, type: 'error');
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
        self::$themeListener = null;
        self::$themeBus = null;
        self::$themeChange = null;
        self::$themeGeneration++;
        self::$bus?->close();
        self::$bus = null;
    }
}
