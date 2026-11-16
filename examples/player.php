<?php

declare(strict_types=1);

require __DIR__ . '/../src/autoload.php';

use GL\Audio\{Engine, Sound};
use GL\Buffer\FloatBuffer;
use PhpUi\{App, Canvas, Desktop, Element, Theme};
use function PhpUi\{button, canvas, col, dataDir, icon, row, slider, spacer, state, t, text, textInput};

// A music player: MP3, FLAC and WAV through the audio engine inside PHP-GLFW.
// Drop files or folders on the window, or add a folder. Three demo tracks are made on first run.

$home = getenv('MYPHP_PLAYER_DIR') ?: dataDir('myphp-player');
$libraryFile = "$home/library.json";

$state = state(new class {
    /** @var list<array{path: string, title: string, artist: string, length: float}> */
    public array $tracks = [];
    public ?int $current = null;
    public bool $playing = false;
    public float $volume = 80;
    public bool $shuffle = false;
    public string $repeat = 'all';
    public string $search = '';
    public string $notice = '';
    /** Waveform of the current track: one 0..1 peak per bar. */
    public array $peaks = [];
});

/** Audio objects stay out of state: they are not data to render. */
$audio = new class {
    public ?Engine $engine = null;
    public ?Sound $sound = null;
    public ?int $timer = null;
};
try {
    $audio->engine = new Engine([]);
    $audio->engine->start();
} catch (Throwable $e) {
    $state->notice = 'No audio device: ' . $e->getMessage();
}

/** Short synthesized songs, so the player has something to play without any files. */
$makeDemos = function (string $dir): void {
    @mkdir($dir, 0777, true);
    $songs = [
        ['Night Drive', 'PHP Ensemble', 112, 220.0, [0, 5, 3, 7]],
        ['Paper Planes', 'The Closures', 96, 261.63, [0, 7, 9, 5]],
        ['Garbage Collector', 'Zend Engine', 128, 196.0, [0, 3, 8, 10]],
    ];
    $rate = 22050;
    foreach ($songs as $n => [$title, $artist, $bpm, $root, $chords]) {
        $file = sprintf('%s/%02d %s - %s.wav', $dir, $n + 1, $artist, $title);
        if (is_file($file)) continue;
        $beat = 60 / $bpm;
        $samples = [];
        $total = (int) ($rate * $beat * 32);
        for ($i = 0; $i < $total; $i++) {
            $t = $i / $rate;
            $b = $t / $beat;
            $chord = $chords[(int) ($b / 4) % 4];
            // Arpeggio in eighth notes, a bass note per beat, a kick on every beat.
            $step = (int) ($b * 2);
            $note = $chord + [0, 4, 7, 12, 7, 4, 0, -5][$step % 8];
            $f = $root * 2 ** ($note / 12);
            $e8 = fmod($b * 2, 1);
            $lead = (sin(2 * M_PI * $f * $t) + 0.3 * sin(4 * M_PI * $f * $t)) * exp(-$e8 * 3) * 0.28;
            $fb = $root / 2 * 2 ** ($chord / 12);
            $e4 = fmod($b, 1);
            $bass = sin(2 * M_PI * $fb * $t) * exp(-$e4 * 2) * 0.3;
            $kt = $e4 * $beat;
            $kick = sin(2 * M_PI * (50 + 80 * exp(-$kt * 30)) * $kt) * exp(-$kt * 12) * 0.45;
            $fade = min(1, $t / 0.5, ($total / $rate - $t) / 1.5);
            $samples[] = (int) (tanh($lead + $bass + $kick) * $fade * 30000);
        }
        $pcm = '';
        foreach (array_chunk($samples, 8192) as $chunk) $pcm .= pack('v*', ...array_map(fn ($v) => $v & 0xffff, $chunk));
        file_put_contents($file, 'RIFF' . pack('V', 36 + strlen($pcm)) . 'WAVEfmt '
            . pack('VvvVVvv', 16, 1, 1, $rate, $rate * 2, 2, 16) . 'data' . pack('V', strlen($pcm)) . $pcm);
    }
};

/** "Artist - Title.mp3" becomes artist and title; a leading track number is dropped. */
$describe = function (string $path) use ($audio): ?array {
    $name = preg_replace('/^\d+[\s.\-_]+/', '', pathinfo($path, PATHINFO_FILENAME));
    [$artist, $title] = str_contains($name, ' - ') ? explode(' - ', $name, 2) : ['Unknown artist', $name];
    $length = 0.0;
    if ($audio->engine) {
        try { $length = $audio->engine->soundFromDisk($path)->getLength(); } catch (Throwable) { return null; }
    }
    return ['path' => $path, 'title' => trim($title), 'artist' => trim($artist), 'length' => $length];
};

$save = function () use ($state, $libraryFile) {
    file_put_contents($libraryFile, json_encode(['tracks' => $state->tracks, 'volume' => $state->volume,
        'shuffle' => $state->shuffle, 'repeat' => $state->repeat], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
};

$add = function (array $paths) use ($state, $describe, $save) {
    $known = array_column($state->tracks, 'path');
    $found = [];
    foreach ($paths as $path) {
        if (is_dir($path)) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
            foreach ($files as $file) $found[] = $file->getPathname();
        } else {
            $found[] = $path;
        }
    }
    sort($found, SORT_NATURAL | SORT_FLAG_CASE);
    $added = 0;
    foreach ($found as $file) {
        if (!preg_match('/\.(mp3|flac|wav)$/i', $file) || in_array($file, $known, true)) continue;
        if ($track = $describe($file)) {
            $state->tracks[] = $track;
            $known[] = $file;
            $added++;
        }
    }
    $state->notice = $added ? "Added $added " . ($added === 1 ? 'track' : 'tracks') : 'No new MP3, FLAC or WAV files there';
    $save();
};

/** Peaks for the waveform: decode the file once, keeping the loudest sample of each slice. */
$waveform = function (string $path, int $bars = 140) use ($audio): array {
    if (!$audio->engine) return [];
    $sound = $audio->engine->soundFromDisk($path);
    $frames = max(1, intdiv($sound->getLengthPCM(), $bars));
    $channels = max(1, $sound->getChannels());
    $peaks = [];
    for ($b = 0; $b < $bars; $b++) {
        $buffer = new FloatBuffer();
        $read = $sound->readFrames($frames, $buffer);
        $max = 0.0;
        // Every 16th sample is plenty for a picture and keeps long files fast.
        for ($i = 0, $n = $read * $channels; $i < $n; $i += 16) $max = max($max, abs($buffer[$i]));
        $peaks[] = $max;
    }
    $top = max(0.001, ...$peaks);
    return array_map(fn ($p) => $p / $top, $peaks);
};

$play = null;
$tick = function () use ($state, $audio, &$play) {
    if (!$state->playing || !$audio->sound || $audio->sound->isPlaying()) return;
    // The track ended by itself.
    $last = $state->current === count($state->tracks) - 1;
    if ($state->repeat === 'one') $play($state->current);
    elseif ($state->shuffle) $play(array_rand($state->tracks));
    elseif (!$last || $state->repeat === 'all') $play(($state->current + 1) % count($state->tracks));
    else { $state->playing = false; $audio->sound->seekTo(0); }
};
$play = function (int $index) use ($state, $audio, $waveform, $tick) {
    if (!$audio->engine || !isset($state->tracks[$index])) return;
    $audio->sound?->stop();
    try {
        $audio->sound = $audio->engine->soundFromDisk($state->tracks[$index]['path']);
    } catch (Throwable $e) {
        $state->notice = 'Cannot play ' . basename($state->tracks[$index]['path']);
        return;
    }
    $audio->sound->setVolume($state->volume / 100);
    $audio->sound->play();
    [$state->current, $state->playing] = [$index, true];
    $state->peaks = $waveform($state->tracks[$index]['path']);
    // Redraw while playing, for the time and the waveform.
    $app = App::current();
    if ($app && $audio->timer === null) $audio->timer = $app->every(1 / 30, $tick);
};
$toggle = function () use ($state, $audio, $play, $tick) {
    if ($state->current === null || !$audio->sound) { if ($state->tracks) $play(0); return; }
    if ($state->playing) {
        $audio->sound->stop();
        $state->playing = false;
        if ($audio->timer !== null) { App::current()?->cancel($audio->timer); $audio->timer = null; }
    } else {
        $audio->sound->play();
        $state->playing = true;
        $app = App::current();
        if ($app && $audio->timer === null) $audio->timer = $app->every(1 / 30, $tick);
    }
};
$step = function (int $direction) use ($state, $audio, $play) {
    if (!$state->tracks) return;
    if ($direction < 0 && $audio->sound && $audio->sound->getCursor() > 3) { $audio->sound->seekTo(0); return; }
    $count = count($state->tracks);
    $play($state->shuffle && $direction > 0 ? array_rand($state->tracks) : ((($state->current ?? 0) + $direction) % $count + $count) % $count);
};
$seek = function (float $fraction) use ($state, $audio) {
    if (!$audio->sound || $state->current === null) return;
    $audio->sound->seekTo(max(0, min(0.999, $fraction)) * $state->tracks[$state->current]['length']);
};
$setVolume = function (float $v) use ($state, $audio, $save) {
    $state->volume = max(0, min(100, $v));
    $audio->sound?->setVolume($state->volume / 100);
    $save();
};

// Library: saved list, or the demo tracks on first run.
$saved = is_file($libraryFile) ? json_decode((string) file_get_contents($libraryFile), true) : null;
if (is_array($saved)) {
    $state->tracks = array_values(array_filter($saved['tracks'] ?? [], fn ($t) => is_file($t['path'] ?? '')));
    [$state->volume, $state->shuffle, $state->repeat] = [(float) ($saved['volume'] ?? 80), (bool) ($saved['shuffle'] ?? false), $saved['repeat'] ?? 'all'];
}
if (!$state->tracks) {
    $makeDemos("$home/demo");
    $add(["$home/demo"]);
    $state->notice = 'Drop music files or folders on the window to add them';
}

$clock = fn (float $s) => sprintf('%d:%02d', intdiv((int) $s, 60), (int) $s % 60);
/** Cover art from the title: two theme colors and the first letter. */
$cover = function (array $track, float $size) {
    $colors = [t('chart-1'), t('chart-2'), t('chart-3'), t('chart-4'), t('chart-5')];
    $h = crc32($track['title']);
    return row()->size($size)->center()->rounded($size / 10)
        ->gradient($colors[$h % 5], Theme::mix($colors[($h >> 3) % 5], '#000000', 0.25))
        ->child(text(mb_strtoupper(mb_substr($track['title'], 0, 1)))->textSize($size * 0.4)->bold()->textColor('#ffffff'));
};

$render = function () use ($state, $audio, $play, $toggle, $step, $seek, $setVolume, $add, $clock, $cover) {
    $query = mb_strtolower(trim($state->search));
    $rows = [];
    foreach ($state->tracks as $i => $track) {
        if ($query !== '' && !str_contains(mb_strtolower("{$track['title']} {$track['artist']}"), $query)) continue;
        $on = $i === $state->current;
        $rows[] = row()->itemsCenter()->gap(10)->h(52)->px(8)->rounded(8)->id("track-$i")->transition()
            ->when($on, fn ($e) => $e->bg(t('accent')), fn ($e) => $e->hover(fn ($s) => $s->bg(t('muted', 0.6))))
            ->onClick(fn () => $play($i))
            ->schema([
                $cover($track, 36),
                col()->flex1()->schema([
                    text($track['title'])->medium()->truncate()->when($on, fn ($e) => $e->textColor(t('chart-2'))),
                    text($track['artist'])->textSize(12)->textColor(t('muted-foreground'))->truncate(),
                ]),
                $on && $state->playing ? icon('volume-2', 16, t('chart-2')) : text($clock($track['length']))->textSize(12)->textColor(t('muted-foreground')),
            ]);
    }

    $track = $state->tracks[$state->current ?? 0] ?? null;
    $position = $audio->sound && $state->current !== null ? $audio->sound->getCursor() : 0.0;
    $length = $track['length'] ?? 0.0;
    $done = $length > 0 ? min(1, $position / $length) : 0;
    $peaks = $state->peaks;
    $wave = canvas(function (Canvas $c) use ($peaks, $done) {
        $n = max(1, count($peaks) ?: 140);
        $w = $c->width / $n;
        for ($i = 0; $i < $n; $i++) {
            $h = max(3, ($peaks[$i] ?? 0.08) * $c->height);
            $c->beginPath();
            $c->roundRect($i * $w + 1, ($c->height - $h) / 2, max(1, $w - 2), $h, 1.5);
            $c->fillStyle = ($i + 0.5) / $n <= $done ? t('chart-2') : t('muted-foreground', 0.35);
            $c->fill();
        }
    })->h(64)->id('wave')->onDrag(fn ($x, $y, $e) => $seek(($x - $e->x) / max(1, $e->w)));

    // Shuffle and repeat light up when on.
    $control = fn (string $ico, callable $fn, string $id, bool $on = false) => row()->size(40)->center()->rounded(20)->id($id)
        ->label(ucfirst($id))->role('button', $id === 'shuffle' || $id === 'repeat' ? ['pressed' => $on] : [])
        ->transition()->hover(fn ($s) => $s->bg(t('accent')))->onClick($fn)
        ->schema([icon($ico, 20, $on ? t('chart-2') : t('foreground'))]);
    $repeatNext = ['off' => 'all', 'all' => 'one', 'one' => 'off'];

    $now = col()->flex1()->center()->gap(20)->p(40)->when($track !== null, fn ($e) => $e->schema([
        $cover($track, 220)->shadow('lg'),
        col()->itemsCenter()->gap(4)->schema([
            text($track['title'])->textSize(24)->bold()->id('now-title'),
            text($track['artist'])->textColor(t('muted-foreground')),
        ]),
        col()->w(460)->gap(6)->schema([
            $wave,
            row()->textSize(12)->textColor(t('muted-foreground'))->schema([text($clock($position))->mono(), spacer(), text($clock($length))->mono()]),
        ]),
        row()->itemsCenter()->gap(12)->schema([
            $control('shuffle', function () use ($state) { $state->shuffle = !$state->shuffle; }, 'shuffle', $state->shuffle),
            $control('skip-back', fn () => $step(-1), 'previous'),
            button()->size('icon')->icon($state->playing ? 'pause' : 'play')->label($state->playing ? 'Pause' : 'Play')
                ->onClick($toggle)->w(52)->h(52)->rounded(26)->id('play'),
            $control('skip-forward', fn () => $step(1), 'next'),
            $control($state->repeat === 'one' ? 'repeat-1' : 'repeat', function () use ($state, $repeatNext) { $state->repeat = $repeatNext[$state->repeat]; }, 'repeat', $state->repeat !== 'off'),
        ]),
        row()->itemsCenter()->gap(10)->w(240)->schema([
            icon($state->volume > 0 ? 'volume-2' : 'volume-x', 16, t('muted-foreground')),
            slider()->bind($state->volume)->afterStateUpdated($setVolume)->flex1()->id('volume')->label('Volume'),
        ]),
    ]));

    return row()->full()->bg(t('background'))->onDrop(fn (array $paths) => $add($paths))->schema([
        col()->w(300)->gap(12)->p(14)->bg(t('muted', 0.4))->borderR(1, t('border'))->schema([
            row()->itemsCenter()->gap(8)->px(4)->schema([
                icon('list-music', 18),
                text('Library')->textSize(16)->semibold(),
                spacer(),
                button()->color('ghost')->size('icon-sm')->icon('folder-open')->label('Add a music folder')->id('add-folder')
                    ->onClick(fn () => Desktop::pickFolder(fn ($dir) => $dir && $add([$dir]), 'Add a music folder')),
                button()->color('ghost')->size('icon-sm')->icon(Theme::dark() ? 'sun' : 'moon')->label('Switch theme')
                    ->onClick(fn () => Theme::toggle())->id('theme'),
            ]),
            textInput('search')->placeholder('Search')->prefixIcon('search')->bind($state->search),
            col()->gap(2)->scroll('tracks')->grow()->schema($rows),
            text($state->notice)->wrap()->textSize(12)->textColor(t('muted-foreground'))->id('notice'),
        ]),
        $now,
    ]);
};

Theme::use('dark');
$app = new App('MyPHP Player', 1000, 640, $render);
$app->onKey(function (int $key) use ($toggle, $step, $seek, $setVolume, $state, $audio) {
    $length = $state->tracks[$state->current ?? 0]['length'] ?? 0;
    match ($key) {
        GLFW_KEY_SPACE => $toggle(),
        GLFW_KEY_N => $step(1),
        GLFW_KEY_P => $step(-1),
        GLFW_KEY_RIGHT => $length && $audio->sound ? $seek(($audio->sound->getCursor() + 5) / $length) : null,
        GLFW_KEY_LEFT => $length && $audio->sound ? $seek(($audio->sound->getCursor() - 5) / $length) : null,
        GLFW_KEY_UP => $setVolume($state->volume + 5),
        GLFW_KEY_DOWN => $setVolume($state->volume - 5),
        default => null,
    };
});
$app->onShutdown(fn () => $audio->sound?->stop());

if ($dir = getenv('PHPUI_SCREENSHOTS')) {
    @mkdir($dir, 0777, true);
    $audio->engine?->setMasterVolume(0);
    $play(0);
    $audio->sound?->seekTo(5.5);
    $app->screenshot("$dir/player.png", 1000, 640, 2);
    exit;
}
if (!getenv('PHPUI_NO_RUN')) $app->run();
