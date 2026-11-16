<?php

declare(strict_types=1);

require __DIR__ . '/../src/autoload.php';

use PhpUi\{App, Desktop, Theme};
use function PhpUi\{badge, button, col, dataDir, icon, row, spacer, state, switchToggle, t, text, textInput};

// A log viewer for Laravel (Monolog) and plain logs: live follow, level filters,
// search, and full stack traces you can select and copy.
// Usage: myphp run logs.php [file]. Run from a Laravel project's folder, it opens storage/logs.

const LEVELS = ['error' => 'Error', 'warning' => 'Warning', 'info' => 'Info', 'debug' => 'Debug'];
const SHOWN = 150;

$state = state(new class {
    public string $file = '';
    /** @var list<array{time: string, level: string, env: string, message: string, detail: string}> */
    public array $entries = [];
    public string $level = 'all';
    public string $search = '';
    public ?int $open = null;
    public bool $follow = true;
    public string $notice = '';
    public int $size = 0;
    public int $newest = 0;
});

/** Monolog's "[2026-11-16 09:12:33] production.ERROR: message", with stack traces on the following lines. */
const ENTRY = '/^\[(\d{4}-\d\d-\d\d[ T]\d\d:\d\d:\d\d)(?:\.\d+)?(?:[+-]\d\d:?\d\d|Z)?\] (\w+)\.(\w+): (.*)$/';

function logLevel(string $name): string
{
    return match (strtolower($name)) {
        'emergency', 'alert', 'critical', 'error', 'fatal' => 'error',
        'warning', 'warn' => 'warning',
        'debug', 'trace' => 'debug',
        default => 'info',
    };
}

/** Entries from log text. Lines before the first Monolog header, or in a non-Monolog file, become entries of their own. */
function parseLog(string $text): array
{
    $entries = [];
    foreach (preg_split('/\r?\n/', $text) as $line) {
        if (preg_match(ENTRY, $line, $m)) {
            $entries[] = ['time' => str_replace('T', ' ', $m[1]), 'level' => logLevel($m[3]), 'env' => $m[2], 'message' => $m[4], 'detail' => ''];
        } elseif ($entries && $entries[array_key_last($entries)]['env'] !== '') {
            if ($line !== '') $entries[array_key_last($entries)]['detail'] .= ($entries[array_key_last($entries)]['detail'] === '' ? '' : "\n") . $line;
        } elseif (trim($line) !== '') {
            $level = preg_match('/\b(error|fatal|exception|critical)\b/i', $line) ? 'error'
                : (preg_match('/\bwarn(ing)?\b/i', $line) ? 'warning' : (preg_match('/\bdebug\b/i', $line) ? 'debug' : 'info'));
            $entries[] = ['time' => '', 'level' => $level, 'env' => '', 'message' => $line, 'detail' => ''];
        }
    }
    return $entries;
}

/** The first line to show, and everything else (exception, JSON context, stack trace) for the expanded view. */
function splitMessage(array $entry): array
{
    $message = $entry['message'];
    $detail = $entry['detail'];
    if (preg_match('/^(.*?) (\{".*)$/s', $message, $m)) [$message, $detail] = [$m[1], trim($m[2] . "\n" . $detail)];
    return [$message, $detail];
}

/** A sample Laravel log, for when there is no log to open. */
function sampleLog(): string
{
    $at = fn (int $ago) => date('Y-m-d H:i:s', time() - $ago);
    return "[{$at(3600)}] production.INFO: Deploy finished {\"release\":\"v2.14.0\",\"duration\":\"48s\"}\n"
        . "[{$at(3300)}] production.INFO: User logged in {\"user_id\":184,\"ip\":\"203.0.113.7\"}\n"
        . "[{$at(2900)}] production.WARNING: Slow query (1240 ms): select * from orders where status = ? {\"bindings\":[\"pending\"]}\n"
        . "[{$at(2400)}] production.ERROR: SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'ada@example.com' for key 'users_email_unique' {\"exception\":\"[object] (Illuminate\\\\Database\\\\UniqueConstraintViolationException(code: 23000): SQLSTATE[23000] at /var/www/app/vendor/laravel/framework/src/Illuminate/Database/Connection.php:825)\n"
        . "[stacktrace]\n#0 /var/www/app/vendor/laravel/framework/src/Illuminate/Database/Connection.php(779): Illuminate\\\\Database\\\\Connection->runQueryCallback()\n"
        . "#1 /var/www/app/vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php(3608): Illuminate\\\\Database\\\\Connection->insert()\n"
        . "#2 /var/www/app/app/Http/Controllers/RegisterController.php(42): Illuminate\\\\Database\\\\Eloquent\\\\Model->save()\n"
        . "#3 /var/www/app/vendor/laravel/framework/src/Illuminate/Routing/Controller.php(54): App\\\\Http\\\\Controllers\\\\RegisterController->store()\n"
        . "#4 {main}\n\"}\n"
        . "[{$at(1800)}] production.DEBUG: Cache miss {\"key\":\"dashboard.stats.184\"}\n"
        . "[{$at(1200)}] production.INFO: Payment received {\"order\":5521,\"amount\":\"49.00\",\"currency\":\"EUR\"}\n"
        . "[{$at(600)}] production.ERROR: Call to a member function getKey() on null {\"userId\":184,\"exception\":\"[object] (Error(code: 0): Call to a member function getKey() on null at /var/www/app/app/Policies/OrderPolicy.php:18)\n"
        . "[stacktrace]\n#0 /var/www/app/vendor/laravel/framework/src/Illuminate/Auth/Access/Gate.php(811): App\\\\Policies\\\\OrderPolicy->view()\n"
        . "#1 /var/www/app/app/Http/Controllers/OrderController.php(31): Illuminate\\\\Auth\\\\Access\\\\Gate->authorize()\n#2 {main}\n\"}\n"
        . "[{$at(120)}] production.WARNING: Rate limit nearly reached for API key ...4f2a {\"remaining\":12}\n";
}

$load = function (string $file) use ($state) {
    clearstatcache(true, $file);
    if (!is_file($file) || !is_readable($file)) { $state->notice = "Cannot read $file"; return; }
    // Large logs: the last 4 MB is what anyone reads.
    $size = filesize($file);
    $from = max(0, $size - 4_000_000);
    $text = (string) file_get_contents($file, false, null, $from);
    if ($from > 0) $text = substr($text, (int) strpos($text, "\n") + 1);
    [$state->file, $state->entries, $state->size, $state->open] = [$file, parseLog($text), $size, null];
    $state->newest = 0;
    $state->notice = $from > 0 ? 'Showing the last 4 MB of a ' . round($size / 1e6) . ' MB log' : '';
};

/** Follow the file: read what was appended, or reload after rotation. */
$poll = function () use ($state, $load) {
    if (!$state->follow || $state->file === '') return;
    clearstatcache(true, $state->file);
    $size = @filesize($state->file);
    if ($size === false || $size === $state->size) return;
    if ($size < $state->size) { $load($state->file); return; }
    $added = (string) file_get_contents($state->file, false, null, $state->size);
    $state->size = $size;
    // An entry may continue on the new lines (a stack trace written in two steps): parse its text again.
    $last = array_pop($state->entries);
    $before = count($state->entries);
    $tail = $last === null ? '' : ($last['time'] === '' ? $last['message'] : "[{$last['time']}] {$last['env']}." . strtoupper($last['level']) . ": {$last['message']}" . ($last['detail'] === '' ? '' : "\n{$last['detail']}"));
    $more = parseLog($tail . "\n" . $added);
    if ($last !== null && $more) $more[0]['level'] = $last['level'];
    array_push($state->entries, ...$more);
    $state->newest += max(0, count($state->entries) - $before - 1);
};

$pick = fn () => Desktop::openFile(fn ($files) => $files && $load($files[0]), 'Open a log', ['Logs' => ['log', 'txt'], 'All files' => ['*']]);

// What to open: an argument, a Laravel project's newest log, or the sample.
$file = $argv[1] ?? null;
if ($file === null && is_dir('storage/logs')) {
    $logs = glob('storage/logs/*.log') ?: [];
    usort($logs, fn ($a, $b) => filemtime($b) <=> filemtime($a));
    $file = $logs[0] ?? null;
}
if ($file === null) {
    $file = (getenv('MYPHP_LOGS_DIR') ?: dataDir('myphp-logs')) . '/laravel.log';
    if (!is_file($file)) file_put_contents($file, sampleLog());
}
$load($file);

$colors = ['error' => t('destructive'), 'warning' => t('chart-4'), 'info' => t('chart-2'), 'debug' => t('muted-foreground')];

$render = function () use ($state, $load, $pick, $colors) {
    $counts = array_count_values(array_column($state->entries, 'level')) + array_fill_keys(array_keys(LEVELS), 0);
    $query = mb_strtolower(trim($state->search));
    $matches = [];
    for ($i = count($state->entries) - 1; $i >= 0; $i--) {
        $e = $state->entries[$i];
        if ($state->level !== 'all' && $e['level'] !== $state->level) continue;
        if ($query !== '' && !str_contains(mb_strtolower($e['message'] . ' ' . $e['detail']), $query)) continue;
        $matches[] = $i;
    }

    $chip = function (string $key, string $name) use ($state, $counts, $colors) {
        $on = $state->level === $key;
        return row()->itemsCenter()->gap(6)->h(30)->px(10)->rounded(15)->id("level-$key")->transition()->textSize(13)->medium()
            ->border(1, $on ? t('foreground') : t('border'))->when($on, fn ($e) => $e->bg(t('accent')), fn ($e) => $e->hover(fn ($s) => $s->bg(t('muted', 0.6))))
            ->onClick(fn () => [$state->level, $state->open] = [$key, null])
            ->schema([
                ...($key === 'all' ? [] : [row()->size(8)->rounded(4)->bg($colors[$key])]),
                text($name),
                text((string) ($key === 'all' ? count($state->entries) : $counts[$key]))->textColor(t('muted-foreground')),
            ]);
    };

    $entry = function (int $i) use ($state, $colors) {
        $e = $state->entries[$i];
        [$message, $detail] = splitMessage($e);
        $open = $state->open === $i;
        $fresh = $i >= count($state->entries) - $state->newest;
        return col()->rounded(8)->border(1, $open ? t('ring') : t('border'))->bg(t('card'))->clip()->id("entry-$i")->transition()->schema([
            row()->itemsCenter()->gap(10)->h(40)->px(12)->hover(fn ($s) => $s->bg(t('muted', 0.4)))
                ->onClick(fn () => $state->open = $open ? null : $i)
                ->schema([
                    row()->w(4)->h(20)->rounded(2)->bg($colors[$e['level']]),
                    text($e['time'] === '' ? '—' : substr($e['time'], 11))->mono()->textSize(12)->textColor(t('muted-foreground'))->w(64),
                    text($message)->truncate()->flex1()->when($e['level'] === 'error', fn ($x) => $x->medium()),
                    ...($fresh ? [badge('new')->color('secondary')] : []),
                    ...($detail !== '' ? [icon($open ? 'chevron-down' : 'chevron-right', 16, t('muted-foreground'))] : []),
                ]),
        ])->when($open, fn ($x) => $x->child(col()->gap(10)->px(16)->py(12)->borderT(1, t('border'))->bg(t('muted', 0.35))->animateIn(120)->schema([
            row()->itemsCenter()->gap(8)->schema([
                text(trim("{$e['time']} {$e['env']}"))->textSize(12)->textColor(t('muted-foreground')),
                spacer(),
                button('Copy')->outlined()->size('sm')->icon('copy')->id("copy-$i")
                    ->onClick(fn () => Desktop::setClipboard(trim($message . "\n" . $detail))),
            ]),
            text($message . ($detail === '' ? '' : "\n\n" . $detail))->wrap()->mono()->textSize(12)->selectable()->id("detail-$i"),
        ])));
    };

    $list = col()->gap(6)->scroll('entries')->grow()->px(20)->pb(20)->schema(array_map($entry, array_slice($matches, 0, SHOWN)));
    if (count($matches) > SHOWN) {
        $list->child(text('Showing the newest ' . SHOWN . ' of ' . count($matches) . ' entries; search or filter to find older ones.')
            ->textSize(12)->textColor(t('muted-foreground'))->textCenter()->py(8));
    }
    if (!$matches) {
        $list->child(col()->itemsCenter()->gap(6)->py(48)->schema([
            icon('search', 24, t('muted-foreground')),
            text($state->entries ? 'Nothing matches' : 'This log is empty')->textColor(t('muted-foreground')),
        ]));
    }

    return col()->full()->bg(t('background'))->onDrop(fn (array $paths) => $load($paths[0]))->schema([
        row()->itemsCenter()->gap(12)->h(56)->px(20)->borderB(1, t('border'))->schema([
            icon('file-text', 18),
            col()->flex1()->schema([
                text(basename($state->file))->semibold()->truncate()->id('file-name'),
                text(dirname($state->file) . ' · ' . number_format($state->size / 1024, 1) . ' KB')->textSize(12)->textColor(t('muted-foreground'))->truncate(),
            ]),
            switchToggle()->label('Follow')->bind($state->follow)->id('follow'),
            button('Open')->outlined()->size('sm')->icon('folder-open')->onClick($pick)->id('open'),
            button()->color('ghost')->size('icon-sm')->icon(Theme::dark() ? 'sun' : 'moon')->label('Switch theme')
                ->onClick(fn () => Theme::toggle())->id('theme'),
        ]),
        row()->itemsCenter()->gap(12)->px(20)->py(14)->schema([
            row()->gap(6)->itemsCenter()->schema(array_map($chip, array_keys(['all' => 'All'] + LEVELS), ['all' => 'All'] + LEVELS)),
            spacer(),
            textInput('search')->placeholder('Search messages and traces')->prefixIcon('search')->w(280)
                ->bind($state->search)->afterStateUpdated(fn () => $state->open = null),
        ]),
        ...($state->notice !== '' ? [text($state->notice)->textSize(12)->textColor(t('muted-foreground'))->px(20)->pb(8)->id('notice')] : []),
        $list,
    ]);
};

$app = new App('MyPHP Logs', 1000, 680, $render);
$app->every(1, $poll);
$app->onKey(function (int $key) use ($state) {
    if ($key === GLFW_KEY_ESCAPE) $state->open = null;
});

if ($dir = getenv('PHPUI_SCREENSHOTS')) {
    @mkdir($dir, 0777, true);
    $app->frame();
    $error = array_key_last(array_filter($state->entries, fn ($e) => $e['level'] === 'error'));
    $state->open = $error;
    $app->screenshot("$dir/logs.png", 1000, 680, 2);
    exit;
}
if (!getenv('PHPUI_NO_RUN')) $app->run();
