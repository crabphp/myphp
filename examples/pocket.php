<?php

declare(strict_types=1);

require __DIR__ . '/../src/autoload.php';

use GL\Audio\{Engine, Sound};
use PhpUi\{App, Theme};
use function PhpUi\{col, dataDir, icon, row, spacer, state, text};

// A pocket music player with a click wheel. Turn the wheel (drag around it, or
// Up/Down) to move through menus and change the volume; press the center (Enter)
// to choose, MENU (Escape) to go back, and the play button (Space) to play or pause.
// It plays the music player example's library, or three demo songs made on first run.

$home = getenv('MYPHP_PLAYER_DIR') ?: dataDir('myphp-player');

$state = state(new class {
    /** @var list<array{path: string, title: string, artist: string, length: float}> */
    public array $tracks = [];
    /** Screens, newest last: 'menu', 'songs', 'artists', 'now', 'about'. */
    public array $screens = ['menu'];
    /** Highlighted row per screen. */
    public array $selected = [];
    public ?int $current = null;
    public bool $playing = false;
    public float $volume = 70;
    public bool $shuffle = false;
    /** When the volume bar was last shown on the now playing screen. */
    public float $volumeShown = 0;
});

$audio = new class {
    public ?Engine $engine = null;
    public ?Sound $sound = null;
    public ?int $timer = null;
    /** The wheel gesture: last angle, time and how far it turned. */
    public ?float $angle = null;
    public float $at = 0;
    public float $turned = 0;
    public float $carry = 0;
};
try {
    $audio->engine = new Engine([]);
    $audio->engine->start();
} catch (Throwable) {
    // No audio device: the player still works, silently.
}

/** Three short synthesized songs, so there is something to play. */
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
            $note = $chord + [0, 4, 7, 12, 7, 4, 0, -5][(int) ($b * 2) % 8];
            $f = $root * 2 ** ($note / 12);
            $lead = (sin(2 * M_PI * $f * $t) + 0.3 * sin(4 * M_PI * $f * $t)) * exp(-fmod($b * 2, 1) * 3) * 0.28;
            $e4 = fmod($b, 1);
            $bass = sin(2 * M_PI * $root / 2 * 2 ** ($chord / 12) * $t) * exp(-$e4 * 2) * 0.3;
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

// The music player example's library, or the demo songs.
$saved = is_file("$home/library.json") ? json_decode((string) file_get_contents("$home/library.json"), true) : null;
$state->tracks = array_values(array_filter($saved['tracks'] ?? [], fn ($t) => is_file($t['path'] ?? '')));
if (!$state->tracks) {
    $makeDemos("$home/demo");
    foreach (glob("$home/demo/*.wav") as $path) {
        [$artist, $title] = explode(' - ', preg_replace('/^\d+\s+/', '', basename($path, '.wav')), 2);
        $length = 0.0;
        try { $length = $audio->engine?->soundFromDisk($path)->getLength() ?? 0.0; } catch (Throwable) {}
        $state->tracks[] = ['path' => $path, 'title' => $title, 'artist' => $artist, 'length' => $length];
    }
}

$play = null;
$tick = function () use ($state, $audio, &$play) {
    if (!$state->playing || !$audio->sound || $audio->sound->isPlaying()) return;
    $play($state->shuffle ? array_rand($state->tracks) : ($state->current + 1) % count($state->tracks));
};
$play = function (int $index) use ($state, $audio, $tick) {
    if (!isset($state->tracks[$index])) return;
    $audio->sound?->stop();
    [$state->current, $state->playing] = [$index, true];
    if (!$audio->engine) return;
    try {
        $audio->sound = $audio->engine->soundFromDisk($state->tracks[$index]['path']);
    } catch (Throwable) {
        $state->playing = false;
        return;
    }
    $audio->sound->setVolume($state->volume / 100);
    $audio->sound->play();
    if (($app = App::current()) && $audio->timer === null) $audio->timer = $app->every(1 / 10, $tick);
};
$toggle = function () use ($state, $audio, $play) {
    if ($state->current === null || !$audio->sound) { if ($state->tracks) $play(0); return; }
    $state->playing ? $audio->sound->stop() : $audio->sound->play();
    $state->playing = !$state->playing;
};
$skip = function (int $direction) use ($state, $audio, $play) {
    if (!$state->tracks) return;
    if ($direction < 0 && $audio->sound && $audio->sound->getCursor() > 3) { $audio->sound->seekTo(0); return; }
    $count = count($state->tracks);
    $play($state->shuffle && $direction > 0 ? array_rand($state->tracks) : ((($state->current ?? -1) + $direction) % $count + $count) % $count);
};

/** The rows of a menu screen: [label, action, detail?]. */
$rows = function (string $screen) use ($state, $play, $toggle): array {
    $go = fn (string $to) => function () use ($state, $to) { $state->screens[] = $to; };
    return match ($screen) {
        'menu' => [
            ['Now Playing', $go('now')],
            ['Songs', $go('songs')],
            ['Artists', $go('artists')],
            ['Shuffle Songs', function () use ($state, $play) { $state->shuffle = true; $play(array_rand($state->tracks)); $state->screens[] = 'now'; }],
            ['Shuffle', function () use ($state) { $state->shuffle = !$state->shuffle; }, $state->shuffle ? 'On' : 'Off'],
            ['About', $go('about')],
        ],
        'songs' => array_map(fn ($i) => [$state->tracks[$i]['title'], function () use ($state, $play, $i) {
            $play($i);
            $state->screens[] = 'now';
        }], array_keys($state->tracks)),
        'artists' => array_map(fn ($a) => [$a, $go('songs')], array_values(array_unique(array_column($state->tracks, 'artist')))),
        default => [],
    };
};

$screen = fn () => end($state->screens);
/** Turning the wheel: one step per notch, through a menu or the volume. */
$turn = function (int $steps) use ($state, $audio, $screen, $rows) {
    if ($steps === 0) return;
    $name = $screen();
    if ($name === 'now') {
        $state->volume = max(0, min(100, $state->volume + $steps * 4));
        $audio->sound?->setVolume($state->volume / 100);
        $state->volumeShown = microtime(true);
        return;
    }
    $count = count($rows($name));
    if ($count) $state->selected[$name] = max(0, min($count - 1, ($state->selected[$name] ?? 0) + $steps));
};
$choose = function () use ($state, $screen, $rows) {
    $items = $rows($screen());
    $item = $items[$state->selected[$screen()] ?? 0] ?? null;
    if ($item) ($item[1])();
};
$back = function () use ($state) { if (count($state->screens) > 1) array_pop($state->screens); };

$clock = fn (float $s) => sprintf('%d:%02d', intdiv((int) $s, 60), (int) $s % 60);
$ink = '#1d1d1f';
$soft = '#86868b';
$blue = '#2f7cf6';

$render = function () use ($state, $audio, $screen, $rows, $turn, $choose, $back, $toggle, $skip, $clock, $ink, $soft, $blue) {
    $name = $screen();
    $titles = ['menu' => 'Pocket', 'songs' => 'Songs', 'artists' => 'Artists', 'now' => 'Now Playing', 'about' => 'About'];

    // Status bar: title, and a play or pause mark while music is loaded.
    $status = row()->h(26)->px(10)->itemsCenter()->borderB(1, '#d2d2d7')->gradient('#fbfbfd', '#e8e8ed')->schema([
        row()->w(18)->schema([$state->current !== null ? icon($state->playing ? 'play' : 'pause', 12, $ink) : text('')]),
        spacer(),
        text($titles[$name])->textSize(13)->semibold()->textColor($ink),
        spacer(),
        row()->w(18)->justifyEnd()->itemsCenter()->label('Battery full')->schema([
            row()->w(16)->h(9)->rounded(2)->border(1, $ink)->p(1)->schema([row()->grow()->rounded(1)->gradient('#7ad46a', '#34a853')]),
            row()->w(1.5)->h(4)->bg($ink),
        ]),
    ]);

    if ($name === 'now' && $state->current !== null) {
        $track = $state->tracks[$state->current];
        $position = $audio->sound?->getCursor() ?? 0.0;
        $length = max(0.001, $track['length']);
        $volume = microtime(true) - $state->volumeShown < 1.5;
        $bar = fn (float $fraction, ?string $icon = null) => row()->itemsCenter()->gap(6)->schema([
            ...($icon !== null ? [icon($icon, 12, $soft)] : []),
            row()->flex1()->h(6)->rounded(3)->bg('#e5e5ea')->schema([
                row()->w(max(6, 196 * min(1, $fraction)))->h(6)->rounded(3)->gradient('#5aa2ff', $blue),
            ]),
        ]);
        $body = col()->flex1()->px(16)->py(10)->gap(10)->schema([
            text(($state->current + 1) . ' of ' . count($state->tracks))->textSize(11)->textColor($soft),
            row()->gap(12)->itemsCenter()->schema([
                row()->size(64)->center()->rounded(6)->gradient('#ff5f6d', '#5b3cc4')->schema([
                    text(mb_strtoupper(mb_substr($track['title'], 0, 1)))->textSize(28)->bold()->textColor('#ffffff'),
                ]),
                col()->flex1()->gap(2)->schema([
                    text($track['title'])->textSize(15)->bold()->textColor($ink)->truncate()->id('now-title'),
                    text($track['artist'])->textSize(12)->textColor($soft)->truncate(),
                ]),
            ]),
            spacer(),
            $volume
                ? $bar($state->volume / 100, 'volume-2')->id('volume')
                : col()->gap(4)->schema([
                    $bar($position / $length),
                    row()->textSize(10)->textColor($soft)->schema([
                        text($clock($position))->mono(),
                        spacer(),
                        text('-' . $clock(max(0, $length - $position)))->mono(),
                    ]),
                ]),
        ]);
    } elseif ($name === 'about') {
        $body = col()->flex1()->center()->gap(4)->p(16)->schema([
            text('Pocket')->textSize(18)->bold()->textColor($ink),
            text(count($state->tracks) . ' songs')->textSize(12)->textColor($soft),
            text('Written in PHP')->textSize(12)->textColor($soft),
        ]);
    } else {
        $items = $rows($name);
        $at = $state->selected[$name] ?? 0;
        // Six rows fit: scroll so the highlighted one stays on screen.
        $first = max(0, min($at - 5, count($items) - 6));
        $line = function (int $i, array $item) use ($at, $name, $ink, $soft, $blue) {
            $on = $i === $at;
            $color = $on ? '#ffffff' : $soft;
            return row()->h(26)->px(10)->itemsCenter()->id("item-$i")->when($on, fn ($e) => $e->gradient('#5aa2ff', $blue))->schema([
                text($item[0])->textSize(13)->medium()->truncate()->flex1()->textColor($on ? '#ffffff' : $ink),
                ...(isset($item[2]) ? [text($item[2])->textSize(12)->textColor($color)]
                    : ($name !== 'songs' ? [icon('chevron-right', 14, $color)] : [])),
            ]);
        };
        $shown = array_slice($items, $first, 6, true);
        $body = col()->flex1()->schema(array_map($line, array_keys($shown), $shown));
    }

    $screenBox = row()->p(3)->rounded(10)->bg('#1c1c1e')->shadow('sm')->schema([
        col()->w(236)->h(184)->rounded(7)->bg('#ffffff')->clip()->schema([$status, $body]),
    ]);

    // The wheel: drag around it to turn; its four labels and the center are buttons.
    $label = fn ($child, string $id, string $side) => row()->absolute()->size(48)->center()->id($id)
        ->{$side === 'top' || $side === 'bottom' ? 'left' : 'top'}(81)->{$side}(4)->schema([$child]);
    $wheel = row()->size(210)->rounded(105)->gradient('#ffffff', '#ececf0')->border(1, '#d2d2d7')->shadow('sm')->id('wheel')
        ->label('Click wheel')
        ->onDrag(function (float $x, float $y, $e) use ($audio, $turn) {
            $cx = $e->x + $e->w / 2;
            $cy = $e->y + $e->h / 2;
            if (hypot($x - $cx, $y - $cy) < 38) return;
            $angle = atan2($y - $cy, $x - $cx);
            $now = microtime(true);
            // A new press, or a pause, starts a new turn.
            if ($audio->angle === null || $now - $audio->at > 0.25) {
                [$audio->angle, $audio->turned, $audio->carry] = [$angle, 0, 0];
            }
            $delta = fmod($angle - $audio->angle + 3 * M_PI, 2 * M_PI) - M_PI;
            [$audio->angle, $audio->at] = [$angle, $now];
            $audio->turned += abs($delta);
            // A notch every 20 degrees.
            $audio->carry += $delta / deg2rad(20);
            $steps = (int) $audio->carry;
            $audio->carry -= $steps;
            $turn($steps);
        })
        ->onClick(function (float $x, float $y, $e) use ($audio, $back, $toggle, $skip) {
            // Releasing ends the turn. A press without turning: the side of the wheel decides.
            $turned = $audio->turned;
            [$audio->angle, $audio->turned] = [null, 0];
            if ($turned > 0.2) return;
            $angle = rad2deg(atan2($y - ($e->y + $e->h / 2), $x - ($e->x + $e->w / 2)));
            match (true) {
                $angle > -135 && $angle < -45 => $back(),
                $angle >= -45 && $angle <= 45 => $skip(1),
                $angle > 45 && $angle < 135 => $toggle(),
                default => $skip(-1),
            };
        })
        ->schema([
            $label(text('MENU')->textSize(12)->bold()->textColor($soft), 'menu', 'top'),
            $label(icon('skip-back', 16, $soft), 'previous', 'left'),
            $label(icon('skip-forward', 16, $soft), 'next', 'right'),
            $label(row()->gap(1)->schema([icon('play', 12, $soft), icon('pause', 12, $soft)]), 'play-pause', 'bottom'),
            row()->absolute()->top(67)->left(67)->size(76)->rounded(38)->gradient('#f2f2f5', '#ffffff')
                ->border(1, '#d2d2d7')->id('select')->label('Select')->onClick(fn () => $choose()),
        ]);

    return col()->full()->center()->bg('#d9d9de')->schema([
        col()->w(276)->h(500)->rounded(28)->gradient('#fdfdfd', '#e4e4e9')->border(1, '#c7c7cc')->shadow('lg')
            ->itemsCenter()->pt(20)->gap(34)->schema([$screenBox, $wheel]),
    ]);
};

Theme::use('light');
$app = new App('Pocket', 340, 560, $render);
$app->onKey(function (int $key) use ($turn, $choose, $back, $toggle, $skip) {
    match ($key) {
        GLFW_KEY_DOWN => $turn(1),
        GLFW_KEY_UP => $turn(-1),
        GLFW_KEY_ENTER, GLFW_KEY_KP_ENTER => $choose(),
        GLFW_KEY_ESCAPE, GLFW_KEY_BACKSPACE => $back(),
        GLFW_KEY_SPACE => $toggle(),
        GLFW_KEY_RIGHT => $skip(1),
        GLFW_KEY_LEFT => $skip(-1),
        default => null,
    };
});
// Redraw while playing, for the time.
$app->every(1 / 4, fn () => null);
$app->onShutdown(fn () => $audio->sound?->stop());

if ($dir = getenv('PHPUI_SCREENSHOTS')) {
    @mkdir($dir, 0777, true);
    $audio->engine?->setMasterVolume(0);
    $app->screenshot("$dir/pocket-menu.png", 340, 560, 2);
    $play(0);
    $audio->sound?->seekTo(5.5);
    $state->screens[] = 'now';
    $app->screenshot("$dir/pocket.png", 340, 560, 2);
    exit;
}
if (!getenv('PHPUI_NO_RUN')) $app->run();
