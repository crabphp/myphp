<?php

declare(strict_types=1);

require __DIR__ . '/../src/autoload.php';

use PhpSdl\Renderer;
use PhpSdl\Sound;
use PhpUi\{App, Camera3D, Canvas, Key, Mouse, Voxels};
use function PhpUi\{canvas, col, dataDir, state};

// Sky Patrol: an arcade shooter in 3D over an endless archipelago. The game plays on a
// flat field (W by H) high above the sea; a tilted camera (PhpUi\Camera3D) shows it in
// perspective over hilly islands (a height map ray marched on the GPU), and every ship,
// tree, house and cloud is a voxel model drawn as stacked slices (sprite stacking,
// PhpUi\Voxels). The ships are their pixel art extruded into 3D. Everything is made
// here: the islands from noise, the ships from text, the sounds from math.
// Fly with the mouse or WASD/arrows; click or Space shoots, right-click or X fires a
// homing missile; Esc pauses. Closures only, so `myphp run --watch` reloads it mid-flight.

// The playing field, in world units. (define(): a hot reload runs this again.)
defined('W') || define('W', 180);
defined('H') || define('H', 270);
defined('TERRAIN') || define('TERRAIN', 1024);  // ground texels; the world wraps every TERRAIN * 2 units
defined('FIELD_Z') || define('FIELD_Z', 260);
defined('PEAK') || define('PEAK', 150);        // the highest ground    // how high the field is over the ground

// Assets -----------------------------------------------------------------------

$dir = dataDir('myphp-skypatrol') . '/v5';
@mkdir($dir, 0777, true);

$hex = fn (string $c) => [hexdec(substr($c, 1, 2)), hexdec(substr($c, 3, 2)), hexdec(substr($c, 5, 2))];

/**
 * A ship from rows of letters (left halves, mirrored when $mirror), extruded into
 * voxels: each letter a color and a height, so the hull rises and the wings stay thin.
 */
$ship = function (array $half, array $palette, array $heights, bool $mirror = true): array {
    $rows = array_map(fn ($r) => $mirror ? $r . strrev(substr($r, 0, -1)) : $r, $half);
    [$w, $d] = [strlen($rows[0]), count($rows)];
    $h = max($heights);
    $voxel = function ($x, $y, $z) use ($rows, $palette, $heights) {
        $letter = $rows[$y][$x];
        if (!isset($palette[$letter]) || $z >= ($heights[$letter] ?? 1)) return null;
        return $palette[$letter];
    };
    return ['size' => [$w, $d, $h], 'unit' => 1.25, 'voxel' => $voxel, 'rows' => $rows];
};

$ink = ['k' => '#151922', 'g' => '#343b4a', 'G' => '#566073', 'l' => '#8e99ad', 'w' => '#eef2f8',
    'o' => '#ff8a2a', 'O' => '#d4511b', 'c' => '#62e6ef', 'C' => '#2a98b0'];
$jet = [
    '........k', '.......kc', '.......kC', '......kgc', '......kgl', '......kgl', '.....kgGl',
    '....kgGGl', '...kgGGGl', '..kgGGGGl', '.kOgGGGGl', 'kOOgGGGgl', 'kOkkgGGgl', 'kk..kgGgl',
    '....kgGgl', '...kOgggg', '..kOOkgkk', '..kkk.k..',
];
$noise = function (int $seed, int $cell) {
    mt_srand($seed);
    $n = intdiv(TERRAIN, $cell);
    $grid = [];
    for ($i = 0; $i < $n * $n; $i++) $grid[] = mt_rand() / mt_getrandmax();
    return function (float $x, float $y) use ($grid, $n, $cell): float {
        [$fx, $fy] = [$x / $cell, $y / $cell];
        [$ix, $iy] = [(int) floor($fx), (int) floor($fy)];
        [$tx, $ty] = [$fx - $ix, $fy - $iy];
        [$tx, $ty] = [$tx * $tx * (3 - 2 * $tx), $ty * $ty * (3 - 2 * $ty)];
        $at = fn ($a, $b) => $grid[(($b % $n + $n) % $n) * $n + ($a % $n + $n) % $n];
        $a = $at($ix, $iy) + ($at($ix + 1, $iy) - $at($ix, $iy)) * $tx;
        $b = $at($ix, $iy + 1) + ($at($ix + 1, $iy + 1) - $at($ix, $iy + 1)) * $tx;
        return $a + ($b - $a) * $ty;
    };
};
$near = fn ($x, $y, $z, $cx, $cy, $cz, $r) => ($x - $cx) ** 2 + ($y - $cy) ** 2 + ($z - $cz) ** 2 < $r * $r;
$shapes = [
    'jet' => $ship($jet, $ink, ['k' => 2, 'g' => 3, 'G' => 3, 'l' => 4, 'c' => 5, 'C' => 5, 'o' => 2, 'O' => 2]),
    'drone' => $ship([
        'kk.....', 'kRk....', '.kRk..k', '..kRkkr', '...kRRr', '..kRrrr', '.kRrDDy', 'kRrrDDd', '.kkrrrd', '...kkrd', '.....kk',
    ], ['k' => '#1a1016', 'R' => '#ff6b6b', 'r' => '#c8303e', 'D' => '#3a1f2b', 'd' => '#5c2733', 'y' => '#ffd23f'],
        ['k' => 1, 'R' => 2, 'r' => 2, 'D' => 4, 'd' => 3, 'y' => 4]),
    'bomber' => $ship([
        '.......kkkk', '......kPPPp', 'kk...kPpppp', 'kPkkkPppppp', 'kPPPPpppppp', 'kPppppppkyy', '.kpppppkkYY',
        '..kppppppkk', '...kkpppppp', '.....kkpppp', '.......kkkk',
    ], ['k' => '#120f1c', 'P' => '#a78bfa', 'p' => '#5b47b8', 'y' => '#ffe066', 'Y' => '#ff9f1c'],
        ['k' => 2, 'P' => 4, 'p' => 3, 'y' => 5, 'Y' => 5]),
    'pine' => ['size' => [9, 9, 16], 'unit' => 0.8, 'voxel' => function ($x, $y, $z) {
        if ($z <= 3 && $x === 4 && $y === 4) return '#5a3d22';
        if ($z >= 3 && hypot($x - 4, $y - 4) <= (16 - $z) * 0.36) return $z % 3 === 0 ? '#1f5c34' : '#2c7a45';
        return null;
    }],
    'oak' => ['size' => [11, 11, 13], 'unit' => 0.8, 'voxel' => function ($x, $y, $z) use ($near) {
        if ($z <= 4 && $x === 5 && abs($y - 5) <= 1) return '#6b4a2b';
        if ($near($x, $y, $z, 5, 5, 8, 4.4) && mt_rand(0, 12)) return ['#2f7a34', '#3d9440', '#56b04a', '#6cc25a'][min(3, intdiv($z - 4, 2))];
        return null;
    }],
    'house' => ['size' => [14, 12, 12], 'unit' => 0.8, 'shade' => 0.6, 'voxel' => function ($x, $y, $z) {
        if ($z <= 5 && $x >= 1 && $x <= 12 && $y >= 1 && $y <= 10) {
            $edge = $x === 1 || $x === 12 || $y === 1 || $y === 10;
            if ($edge && $z >= 2 && $z <= 3 && ($x % 3 === 0 || $y % 3 === 0)) return '#3b6ea8';
            return $edge ? '#efe3c8' : '#d8c8a8';
        }
        if ($z >= 6 && abs($y - 5.5) <= 6.5 - ($z - 6) * 1.3) return $z === 11 ? '#8f2f24' : '#c4473a';
        return null;
    }],
];
foreach (['cloud' => [30, 22, 8, 23], 'cloud2' => [24, 18, 7, 29]] as $name => [$w, $d, $h, $seed]) {
    mt_srand($seed);
    $puffs = [];
    for ($i = 0; $i < 7; $i++) $puffs[] = [mt_rand(6, $w - 7), mt_rand(5, $d - 6), mt_rand(0, 2), mt_rand(35, 60) / 10];
    $shapes[$name] = ['size' => [$w, $d, $h], 'unit' => 2.2, 'shade' => 0.86, 'voxel' => function ($x, $y, $z) use ($puffs, $near) {
        foreach ($puffs as [$px, $py, $pz, $r]) if ($near($x, $y, $z * 1.4, $px, $py, $pz, $r)) return $z >= 4 ? '#ffffff' : '#f2f6fb';
        return null;
    }];
}
mt_srand(5);
$voxels = new Voxels("$dir/models.png", $shapes);
if ($voxels->built) Renderer::savePng("$dir/jet.png", 17, 18, $voxels->layer('jet', 1));

/** The archipelago: sea, shallows, beaches, grass, forest, rock and snow, lit from the top left; it wraps both ways. */
$octaves = [[$noise(7, 128), 0.5], [$noise(11, 64), 0.27], [$noise(13, 32), 0.14], [$noise(17, 8), 0.09]];
$height = function (float $x, float $y) use ($octaves): float {
    $h = 0.0;
    foreach ($octaves as [$n, $weight]) $h += $n($x, $y) * $weight;
    return $h;
};
/** How high the land rises, 0 (the sea) to 1, from the noise's height. */
$rise = fn (float $h) => $h < 0.555 ? 0.0 : min(1, ($h - 0.555) / 0.445 * 1.7) ** 1.5;
/** The ground's height in world units at world (x, y). */
$groundAt = fn (float $x, float $y) => $rise($height($x / 2, $y / 2)) * PEAK;
$terrainFile = "$dir/terrain.png";
if (!is_file($terrainFile)) {
    $bands = [[0.42, '#16467f'], [0.5, '#1f63a8'], [0.535, '#3f8fd0'], [0.555, '#7cc4e4'], [0.575, '#e9dba4'],
        [0.64, '#5fb34d'], [0.7, '#3e9140'], [0.76, '#2b6e33'], [0.8, '#7b7b6a'], [0.84, '#a3a291'], [9, '#f2f4f2']];
    $colors = array_map(fn ($b) => [$b[0], $hex($b[1])], $bands);
    mt_srand(3);
    $px = '';
    $above = [];
    for ($x = 0; $x < TERRAIN; $x++) $above[$x] = $height($x, -1);
    for ($y = 0; $y < TERRAIN; $y++) {
        [$row, $line] = [[], []];
        for ($x = 0; $x < TERRAIN; $x++) {
            $h = $line[$x] = $height($x, $y);
            foreach ($colors as [$top, $c]) if ($h < $top) break;
            // Land facing the light is brighter; the sea glints.
            $slope = $h - $above[($x + TERRAIN - 1) % TERRAIN];
            $k = $h >= 0.555 ? 1 + max(-0.3, min(0.3, $slope * 14)) : 1;
            foreach ($c as $v) $row[] = min(255, (int) ($v * $k));
            // The land's height in alpha, for the GPU's ray march: the sea 0.
            $row[] = $h < 0.555 ? 0 : max(1, (int) round($rise($h) * 255));
        }
        $above = $line;
        $px .= pack('C*', ...$row);
    }
    Renderer::savePng($terrainFile, TERRAIN, TERRAIN, $px);
}
$world = TERRAIN * 2;

// On the land: woods, and houses lower down. In the air, clouds between the field and
// the camera. All in world units (2 a texel).
$places = [];
$taken = [];  // 64-unit cells => places in them, to keep things apart
mt_srand(77);
for ($i = 0; $i < 20000 && count($places) < 900; $i++) {
    [$x, $y] = [mt_rand(0, TERRAIN - 1) * 2, mt_rand(0, TERRAIN - 1) * 2];
    $h = $height($x / 2, $y / 2);
    if ($h < 0.585 || $h > 0.75) continue;
    $kind = $h > 0.68 ? (mt_rand(0, 3) ? 'pine' : 'oak') : ['oak', 'pine', 'house', 'oak'][mt_rand(0, 3)];
    $room = 5;
    [$cx, $cy] = [intdiv($x, 64), intdiv($y, 64)];
    for ($a = -2; $a <= 2; $a++) for ($b = -2; $b <= 2; $b++) {
        foreach ($taken[($cx + $a) . ',' . ($cy + $b)] ?? [] as $p) if (hypot($p['x'] - $x, $p['y'] - $y) < $room + $p['room']) continue 3;
    }
    $place = ['kind' => $kind, 'x' => $x, 'y' => $y, 'z' => $groundAt($x, $y) - 1, 'yaw' => mt_rand(0, 3) * M_PI / 2, 'room' => $room];
    $places[] = $place;
    $taken["$cx,$cy"][] = $place;
}
unset($taken);
for ($i = 0; $i < 70; $i++) {
    $places[] = ['kind' => mt_rand(0, 1) ? 'cloud' : 'cloud2', 'x' => mt_rand(0, $world - 1), 'y' => mt_rand(0, $world - 1),
        'z' => FIELD_Z + mt_rand(30, 140), 'yaw' => mt_rand(0, 628) / 100, 'room' => 0];
}

/** Sound effects, synthesized: 22 kHz mono WAV. */
$wav = function (string $name, float $seconds, callable $sample) use ($dir): string {
    $file = "$dir/$name.wav";
    if (is_file($file)) return $file;
    [$rate, $data] = [22050, ''];
    $n = (int) ($seconds * $rate);
    for ($i = 0; $i < $n; $i++) $data .= pack('v', (int) (max(-1, min(1, $sample($i / $rate, $i / $n))) * 26000) & 0xffff);
    file_put_contents($file, 'RIFF' . pack('V', 36 + strlen($data)) . 'WAVEfmt ' . pack('VvvVVvv', 16, 1, 1, $rate, $rate * 2, 2, 16) . 'data' . pack('V', strlen($data)) . $data);
    return $file;
};
mt_srand();
$square = fn (float $t, float $f) => fmod($t * $f, 1) < 0.5 ? 1 : -1;
$sounds = [
    'shoot' => $wav('shoot', 0.07, fn ($t, $p) => $square($t, 1500 - 900 * $p) * (1 - $p) * 0.35),
    'boom' => $wav('boom', 0.7, function ($t, $p) { static $v = 0.0; $v += (mt_rand(-1000, 1000) / 1000 - $v) * (0.5 - 0.45 * $p); return $v * (1 - $p) ** 2 * 1.6; }),
    'missile' => $wav('missile', 0.4, fn ($t, $p) => (mt_rand(-1000, 1000) / 1000 * 0.5 + $square($t, 200 + 500 * $p) * 0.2) * (1 - $p)),
    'hit' => $wav('hit', 0.18, fn ($t, $p) => $square($t, 170 - 80 * $p) * (1 - $p) * 0.7),
    'start' => $wav('start', 0.45, fn ($t, $p) => $square($t, [523, 659, 784, 1047][min(3, (int) ($p * 4))]) * 0.25 * (1 - $p * 0.5)),
];

// Game ---------------------------------------------------------------------------

$best = (int) @file_get_contents(dataDir('myphp-skypatrol') . '/best');
$g = state(new class {
    public string $mode = 'title';
    public bool $paused = false;
    public float $time = 0, $scroll = 0, $shake = 0;
    public float $x = W / 2, $y = H - 50, $bank = 0;
    public int $hp = 5, $score = 0, $best = 0, $missiles = 6, $kills = 0;
    public float $invulnerable = 0, $gun = 0, $missileWait = 0, $recharge = 0, $spawn = 1, $bomberIn = 14, $shots = 0;
    public array $bullets = [], $enemyShots = [], $enemies = [], $rockets = [], $particles = [];
    public array $mouse = [-1, -1];
    /** The mouse in game pixels, from the last frame drawn. */
    public ?array $toGame = null;
    public bool $wasClicking = true, $wasFiring = false;
    /** Flies and shoots by itself (the screenshot). */
    public bool $autopilot = false;
});
$g->best = max($g->best, $best);

$audio = new class {
    public bool $on = false;
    public array $playing = [];
};
$audio->on = Sound::available();
$sfx = function (string $name, float $volume = 0.5) use ($audio, $sounds) {
    if (!$audio->on) return;
    $audio->playing = array_values(array_filter($audio->playing, fn (Sound $s) => $s->playing()));
    if (count($audio->playing) > 12) return;
    $s = new Sound($sounds[$name]);
    $s->volume($volume);
    $s->play();
    $audio->playing[] = $s;
};

$burst = function (float $x, float $y, int $n, array $colors, float $speed, float $life) use ($g) {
    for ($i = 0; $i < $n; $i++) {
        $a = mt_rand(0, 628) / 100;
        $v = $speed * (0.3 + mt_rand(0, 100) / 140);
        $g->particles[] = ['x' => $x, 'y' => $y, 'vx' => cos($a) * $v, 'vy' => sin($a) * $v, 'life' => $life * (0.5 + mt_rand(0, 100) / 200),
            'max' => $life, 'color' => $colors[array_rand($colors)], 'size' => mt_rand(1, 3)];
    }
};
$explode = function (float $x, float $y, float $size) use ($burst, $g, $sfx) {
    $burst($x, $y, (int) (14 * $size), ['#ffffff', '#ffe066', '#ffb02e', '#ff6b2c'], 70 * $size, 0.55);
    $burst($x, $y, (int) (8 * $size), ['#5a5560', '#3d3a44', '#77707e'], 30 * $size, 1.1);
    $g->shake = max($g->shake, 2.5 * $size);
    $sfx('boom', min(0.9, 0.35 * $size));
};

$reset = function () use ($g, $sfx) {
    foreach (['bullets', 'enemyShots', 'enemies', 'rockets', 'particles'] as $k) $g->$k = [];
    [$g->x, $g->y, $g->hp, $g->score, $g->kills, $g->missiles] = [W / 2, H - 50, 5, 0, 0, 6];
    [$g->invulnerable, $g->spawn, $g->bomberIn, $g->time, $g->paused, $g->mode] = [2, 1, 14, 0, false, 'play'];
    $sfx('start', 0.4);
};

$spawnDrones = function () use ($g) {
    $n = mt_rand(1, min(5, 2 + intdiv((int) $g->time, 25)));
    $x0 = mt_rand(20, W - 20);
    for ($i = 0; $i < $n; $i++) {
        $g->enemies[] = ['kind' => 'drone', 'x' => $x0, 'x0' => $x0, 'y' => -12 - $i * 16, 'vy' => 38 + mt_rand(0, 20) + $g->time * 0.25,
            'amp' => mt_rand(10, 34), 'phase' => $i * 0.7, 'hp' => 2, 'r' => 6, 'flash' => 0, 'fire' => 1 + mt_rand(0, 150) / 100];
    }
};

$update = function (float $dt) use ($g, $reset, $burst, $explode, $sfx, $spawnDrones) {
    $app = App::current();
    $g->time += $dt;
    $g->scroll += ($g->mode === 'play' ? 34 : 18) * $dt;
    $g->shake = max(0, $g->shake - 14 * $dt);
    foreach ($g->particles as $i => &$p) {
        [$p['x'], $p['y']] = [$p['x'] + $p['vx'] * $dt, $p['y'] + ($p['vy'] + 18) * $dt];
        [$p['vx'], $p['vy']] = [$p['vx'] * 0.96, $p['vy'] * 0.96];
        $p['life'] -= $dt;
        if ($p['life'] <= 0) unset($g->particles[$i]);
    }
    unset($p);

    $clicking = $app->isMouseDown(Mouse::LEFT);
    $start = $app->isKeyDown(Key::ENTER) || $app->isKeyDown(Key::SPACE) || ($clicking && !$g->wasClicking);
    $g->wasClicking = $clicking;
    if ($g->mode !== 'play') {
        if ($start && $g->time > 0.6) $reset();
        return;
    }
    if ($g->paused) return;

    // The jet: follows the mouse when it moves, or the keys.
    [$mx, $my] = $app->pointerAt();
    [$vx, $vy] = [0.0, 0.0];
    $dx = ($app->isKeyDown(Key::RIGHT) || $app->isKeyDown(Key::D) ? 1 : 0) - ($app->isKeyDown(Key::LEFT) || $app->isKeyDown(Key::A) ? 1 : 0);
    $dy = ($app->isKeyDown(Key::DOWN) || $app->isKeyDown(Key::S) ? 1 : 0) - ($app->isKeyDown(Key::UP) || $app->isKeyDown(Key::W) ? 1 : 0);
    if ($dx || $dy) {
        [$vx, $vy] = [$dx * 120, $dy * 110];
        $g->mouse = [-1, -1];
    } elseif ($mx >= 0 && [$mx, $my] !== $g->mouse) {
        $g->mouse = [$mx, $my];
    }
    if ($g->autopilot) {
        // Line up under the nearest enemy, keep low.
        $near = null;
        foreach ($g->enemies as $e) if ($e['y'] > 0 && (!$near || $e['y'] > $near['y'])) $near = $e;
        [$vx, $vy] = [(($near['x'] ?? W / 2) - $g->x) * 3, (H - 60 - $g->y) * 2];
    } elseif ($g->mouse[0] >= 0 && !$dx && !$dy) {
        [$tx, $ty] = $g->toGame ?? [$g->x, $g->y];
        $follow = min(1, $dt * 9);
        [$vx, $vy] = [($tx - $g->x) * $follow / max($dt, 1e-3), ($ty - $g->y) * $follow / max($dt, 1e-3)];
    }
    $g->x = max(9, min(W - 9, $g->x + $vx * $dt));
    $g->y = max(24, min(H - 16, $g->y + $vy * $dt));
    $g->bank += (max(-1, min(1, $vx / 90)) - $g->bank) * min(1, $dt * 10);

    // Guns, and missiles that recharge.
    $g->gun -= $dt;
    if (($clicking || $g->autopilot || $app->isKeyDown(Key::SPACE)) && $g->gun <= 0) {
        $g->gun = 0.085;
        $g->bullets[] = ['x' => $g->x - 4, 'y' => $g->y - 6];
        $g->bullets[] = ['x' => $g->x + 4, 'y' => $g->y - 6];
        if ((int) $g->shots++ % 2 === 0) $sfx('shoot', 0.18);
    }
    $firing = $app->isMouseDown(Mouse::RIGHT) || $app->isKeyDown(Key::X) || $app->isKeyDown(Key::LEFT_SHIFT) || ($g->autopilot && fmod($g->time, 2) < 0.05);
    $g->missileWait -= $dt;
    if ($firing && !$g->wasFiring && $g->missiles > 0 && $g->missileWait <= 0) {
        $g->missiles--;
        $g->missileWait = 0.3;
        $g->rockets[] = ['x' => $g->x, 'y' => $g->y, 'vx' => ($g->rockets ? -1 : 1) * 40, 'vy' => -20, 'life' => 3];
        $sfx('missile', 0.4);
    }
    $g->wasFiring = $firing;
    $g->recharge += $dt;
    if ($g->recharge > 2.5) { $g->recharge = 0; $g->missiles = min(6, $g->missiles + 1); }

    // Enemies arrive faster as time goes on; a bomber now and then.
    $g->spawn -= $dt;
    if ($g->spawn <= 0) { $spawnDrones(); $g->spawn = max(0.45, 1.6 - $g->time / 90); }
    $g->bomberIn -= $dt;
    if ($g->bomberIn <= 0) {
        $g->bomberIn = max(7, 16 - $g->time / 20);
        $x = mt_rand(30, W - 30);
        $g->enemies[] = ['kind' => 'bomber', 'x' => $x, 'x0' => $x, 'y' => -16, 'vy' => 16, 'amp' => 24, 'phase' => 0, 'hp' => 14, 'r' => 10, 'flash' => 0, 'fire' => 1.5];
    }

    foreach ($g->bullets as $i => &$b) { $b['y'] -= 270 * $dt; if ($b['y'] < -8) unset($g->bullets[$i]); }
    unset($b);
    foreach ($g->enemyShots as $i => &$s) {
        [$s['x'], $s['y']] = [$s['x'] + $s['vx'] * $dt, $s['y'] + $s['vy'] * $dt];
        if ($s['y'] > H + 6 || $s['y'] < -6 || $s['x'] < -6 || $s['x'] > W + 6) unset($g->enemyShots[$i]);
    }
    unset($s);
    foreach ($g->enemies as $i => &$e) {
        $e['y'] += $e['vy'] * $dt;
        $e['x'] = $e['x0'] + sin($g->time * 1.6 + $e['phase']) * $e['amp'];
        $e['flash'] = max(0, $e['flash'] - $dt);
        $e['fire'] -= $dt;
        if ($e['fire'] <= 0 && $e['y'] > 0 && $e['y'] < H * 0.65) {
            $e['fire'] = $e['kind'] === 'bomber' ? 1.3 : 1.8 + mt_rand(0, 120) / 100;
            $aim = atan2($g->y - $e['y'], $g->x - $e['x']);
            foreach ($e['kind'] === 'bomber' ? [-0.3, 0, 0.3] : [0] as $spread) {
                $g->enemyShots[] = ['x' => $e['x'], 'y' => $e['y'] + 4, 'vx' => cos($aim + $spread) * 70, 'vy' => sin($aim + $spread) * 70];
            }
        }
        if ($e['y'] > H + 20) unset($g->enemies[$i]);
    }
    unset($e);

    // Homing missiles: turn toward the nearest enemy, leaving smoke.
    foreach ($g->rockets as $i => &$r) {
        $target = null;
        foreach ($g->enemies as $e) if ($e['y'] > -8 && (!$target || hypot($e['x'] - $r['x'], $e['y'] - $r['y']) < hypot($target['x'] - $r['x'], $target['y'] - $r['y']))) $target = $e;
        $want = $target ? atan2($target['y'] - $r['y'], $target['x'] - $r['x']) : -M_PI / 2;
        $speed = min(230, hypot($r['vx'], $r['vy']) + 300 * $dt);
        $heading = atan2($r['vy'], $r['vx']);
        $turn = atan2(sin($want - $heading), cos($want - $heading));
        $heading += max(-5 * $dt, min(5 * $dt, $turn));
        [$r['vx'], $r['vy']] = [cos($heading) * $speed, sin($heading) * $speed];
        [$r['x'], $r['y']] = [$r['x'] + $r['vx'] * $dt, $r['y'] + $r['vy'] * $dt];
        $r['life'] -= $dt;
        $g->particles[] = ['x' => $r['x'], 'y' => $r['y'], 'vx' => mt_rand(-10, 10), 'vy' => mt_rand(-10, 10), 'life' => 0.6, 'max' => 0.6, 'color' => '#e8ecf2', 'size' => 2];
        if ($r['life'] <= 0 || $r['y'] < -20 || $r['y'] > H + 20) unset($g->rockets[$i]);
    }
    unset($r);

    // Hits.
    $damage = function (int $i, int $amount) use ($g, $explode) {
        $e = &$g->enemies[$i];
        $e['hp'] -= $amount;
        $e['flash'] = 0.08;
        if ($e['hp'] > 0) return;
        $big = $e['kind'] === 'bomber';
        $explode($e['x'], $e['y'], $big ? 2 : 1);
        $g->score += $big ? 500 : 100;
        $g->kills++;
        unset($g->enemies[$i]);
    };
    foreach ($g->bullets as $bi => $b) foreach ($g->enemies as $ei => $e) {
        if (abs($b['x'] - $e['x']) < $e['r'] && abs($b['y'] - $e['y']) < $e['r']) { unset($g->bullets[$bi]); $damage($ei, 1); break; }
    }
    foreach ($g->rockets as $ri => $r) foreach ($g->enemies as $ei => $e) {
        if (hypot($r['x'] - $e['x'], $r['y'] - $e['y']) < $e['r'] + 3) { unset($g->rockets[$ri]); $damage($ei, 8); break; }
    }
    $g->invulnerable -= $dt;
    if ($g->invulnerable <= 0) {
        $hurt = false;
        foreach ($g->enemyShots as $i => $s) if (hypot($s['x'] - $g->x, $s['y'] - $g->y) < 5) { unset($g->enemyShots[$i]); $hurt = true; }
        foreach ($g->enemies as $i => $e) if (hypot($e['x'] - $g->x, $e['y'] - $g->y) < $e['r'] + 5) { $damage($i, 99); $hurt = true; }
        if ($hurt) {
            $g->hp--;
            $g->invulnerable = 1.4;
            $g->shake = 5;
            $sfx('hit', 0.7);
            $burst($g->x, $g->y, 12, ['#ffffff', '#ff6b6b'], 60, 0.4);
            if ($g->hp <= 0) {
                $explode($g->x, $g->y, 3);
                [$g->mode, $g->time] = ['over', 0];
                if ($g->score > $g->best) {
                    $g->best = $g->score;
                    file_put_contents(dataDir('myphp-skypatrol') . '/best', (string) $g->best);
                }
            }
        }
    }
    // The flame behind the jet.
    if (mt_rand(0, 1)) $g->particles[] = ['x' => $g->x + mt_rand(-1, 1), 'y' => $g->y + 9, 'vx' => 0, 'vy' => 60, 'life' => 0.12, 'max' => 0.12, 'color' => mt_rand(0, 1) ? '#ffb02e' : '#fff3b0', 'size' => 2];
};

// Drawing ------------------------------------------------------------------------

$draw = function (Canvas $c) use ($g, $voxels, $places, $terrainFile, $world, $groundAt) {
    [$cw, $ch] = [$c->width, $c->height];
    $c->imageSmoothingEnabled = false;
    // The camera: tilted down at the field from behind its bottom edge, drifting a little
    // after the jet so the scene shifts in depth; shaken by hits. A field point (x, y)
    // is world (x - W/2, y - scroll), FIELD_Z up; the ground scrolls under it.
    $pitch = 0.8;
    $focal = $ch * 2;
    $reach = $focal / 2.7 * min(1, $cw / $ch / (W / H) * 1.05);
    $shake = $g->shake > 0 ? [mt_rand(-10, 10) / 10 * $g->shake * 3, mt_rand(-10, 10) / 10 * $g->shake * 3] : [0, 0];
    $cam = new Camera3D(($g->x - W / 2) * 0.25, H / 2 - $g->scroll + $reach * cos($pitch), FIELD_Z + $reach * sin($pitch),
        0, $pitch, $focal, $cw / 2 + $shake[0], $ch * 0.5 + $shake[1], $world, -$g->bank * 0.04);
    $at = fn (float $x, float $y, float $z = FIELD_Z) => $cam->project($x - W / 2, $y - $g->scroll, $z);
    // The mouse on the field, for update().
    [$mx, $my] = App::current()->pointerAt();
    $hit = $cam->unproject($mx, $my, FIELD_Z);
    $g->toGame = $hit ? [$hit[0] + W / 2, $hit[1] + $g->scroll] : null;

    $c->fillStyle = '#bcd7ea';
    $c->fillRect(0, 0, $cw, $ch);
    $cam->terrain($c, $terrainFile, $world, PEAK, 2, '#bcd7ea', 2600, 3200, $g->time);

    // Shadows of the ships on the sea, far below.
    $ships = [];
    foreach ($g->enemies as $e) $ships[] = [$e['kind'], $e['x'], $e['y'], $e];
    $showJet = $g->mode === 'play' && ($g->invulnerable <= 0 || fmod($g->time, 0.16) < 0.1);
    if ($showJet) $ships[] = ['jet', $g->x, $g->y, null];
    foreach ($ships as [$kind, $x, $y]) {
        [$wx, $wy] = [$x - W / 2, $y - $g->scroll];
        $cam->shadow($c, $wx, $wy, $kind === 'bomber' ? 11 : 8, 0.22, $groundAt(...$cam->near($wx, $wy)) + 0.5);
    }

    // Everything else far to near: what stands on the land, clouds, ships, shots.
    $things = [];
    foreach ($places as $p) {
        $q = $cam->project($p['x'], $p['y'], $p['z']);
        if ($q && $q[1] > -200 && $q[1] < $ch + 200) $things[] = [$q[3], 'model', $p['kind'], $cam->near($p['x'], $p['y']), $p['z'], $p['yaw'], 0, 1];
    }
    foreach ($ships as [$kind, $x, $y, $e]) {
        if (!$q = $at($x, $y)) continue;
        // Enemies face where they fly, and lean into their weaving.
        [$yaw, $roll] = [0, $g->bank * 0.7];
        if ($e) {
            $vx = cos($g->time * 1.6 + $e['phase']) * 1.6 * $e['amp'];
            [$yaw, $roll] = [-atan2($vx, $e['vy']) * 0.6, max(-0.5, min(0.5, $vx / 90))];
        }
        $things[] = [$q[3] - 1, 'model', $kind, [$x - W / 2, $y - $g->scroll], FIELD_Z, $yaw, $roll, $e && $e['flash'] > 0 ? 0.45 : 1];
    }
    foreach ($g->bullets as $b) if ($q = $at($b['x'], $b['y'])) $things[] = [$q[3], 'bullet', $q];
    foreach ($g->rockets as $r) if ($q = $at($r['x'], $r['y'])) $things[] = [$q[3], 'rocket', $q];
    foreach ($g->enemyShots as $s) if ($q = $at($s['x'], $s['y'])) $things[] = [$q[3], 'shot', $q];
    foreach ($g->particles as $p) if ($q = $at($p['x'], $p['y'])) $things[] = [$q[3] - 2, 'spark', $q, $p];
    usort($things, fn ($a, $b) => $b[0] <=> $a[0]);

    foreach ($things as $t) {
        switch ($t[1]) {
            case 'model':
                [, , $name, [$x, $y], $z, $yaw, $roll, $alpha] = $t;
                if (str_starts_with($name, 'cloud')) $alpha = 0.85;
                $cam->stack($c, $voxels, $name, $x, $y, $z, $yaw, $alpha, $roll, 10);
                break;
            case 'bullet':
                [$x, $y, $s] = $t[2];
                $c->fillStyle = '#fff6c4';
                $c->fillRect($x - $s * 0.6, $y - $s * 2.5, $s * 1.2, $s * 5);
                break;
            case 'rocket':
                [$x, $y, $s] = $t[2];
                $c->fillStyle = '#ffffff';
                $c->fillRect($x - $s * 1.5, $y - $s * 1.5, $s * 3, $s * 3);
                break;
            case 'shot':
                [$x, $y, $s] = $t[2];
                $c->fillStyle = 'rgba(255, 80, 120, 0.35)';
                $c->beginPath();
                $c->arc($x, $y, $s * 3, 0, 2 * M_PI);
                $c->fill();
                $c->fillStyle = '#ffd0dc';
                $c->beginPath();
                $c->arc($x, $y, $s * 1.6, 0, 2 * M_PI);
                $c->fill();
                break;
            case 'spark':
                [[$x, $y, $s], $p] = [$t[2], $t[3]];
                $c->globalAlpha = max(0, min(1, $p['life'] / $p['max'] * 1.4));
                $c->fillStyle = $p['color'];
                $z = $p['size'] * $s;
                $c->fillRect($x - $z / 2, $y - $z / 2, $z, $z);
                $c->globalAlpha = 1;
                break;
        }
    }
    [$ox, $oy] = [0, 0];

    /** A HUD chip: a label, and a bar of $full of $of cells when given. */
    $chip = function (string $text, float $x, float $y, string $align = 'left', int $full = -1, int $of = 0, string $color = '#ffffff') use ($c) {
        $c->font = '11px mono-semibold';
        $c->letterSpacing = '1.5px';
        $w = $c->measureText($text)->width + 16 + ($of ? $of * 8 + 4 : 0);
        $left = $align === 'right' ? $x - $w : $x;
        $c->fillStyle = 'rgba(10, 18, 32, 0.72)';
        $c->beginPath();
        $c->roundRect($left, $y, $w, 22, 4);
        $c->fill();
        $c->fillStyle = '#dbe6f5';
        $c->textAlign = 'left';
        $c->textBaseline = 'middle';
        $c->fillText($text, $left + 8, $y + 11);
        $c->letterSpacing = '0px';
        for ($i = 0; $i < $of; $i++) {
            $c->fillStyle = $i < $full ? $color : 'rgba(219, 230, 245, 0.22)';
            $c->fillRect($left + $w - 8 - ($of - $i) * 8 + 2, $y + 7, 6, 8);
        }
        return $w;
    };
    $right = $cw - 12;
    $center = $cw / 2;
    if ($g->mode === 'play') {
        $chip(sprintf('SCORE %06d', $g->score), 12, 12);
        $chip('BEST ' . $g->best, $right, 12, 'right');
        $chip('HULL', 12, $ch - 34, 'left', $g->hp, 5, $g->hp <= 1 ? '#ff6b6b' : '#7ee787');
        $chip('MSL', $right, $ch - 34, 'right', $g->missiles, 6, '#ffd23f');
    }
    $c->textAlign = 'center';
    $c->textBaseline = 'middle';
    $title = function (string $big, string $small, string $hint) use ($c, $center, $ch, $g) {
        $c->fillStyle = 'rgba(5, 8, 15, 0.45)';
        $c->fillRect(0, $ch * 0.32, $c->width, $ch * 0.36);
        $c->font = 'bold 40px sans-serif';
        $c->letterSpacing = '6px';
        $c->fillStyle = '#0b1220';
        $c->fillText($big, $center + 3, $ch * 0.42 + 3);
        $c->fillStyle = '#ffffff';
        $c->fillText($big, $center, $ch * 0.42);
        $c->letterSpacing = '2px';
        $c->font = '13px mono-semibold';
        $c->fillStyle = '#9fb3cf';
        $c->fillText($small, $center, $ch * 0.5);
        if (fmod($g->time, 1.2) < 0.8) {
            $c->fillStyle = '#ffd23f';
            $c->fillText($hint, $center, $ch * 0.6);
        }
        $c->letterSpacing = '0px';
    };
    if ($g->mode === 'title') $title('SKY PATROL', 'MOUSE OR WASD · CLICK OR SPACE · X MISSILE', 'CLICK TO FLY');
    if ($g->mode === 'over') $title('DOWN', sprintf('SCORE %d · %d KILLS · BEST %d', $g->score, $g->kills, $g->best), 'CLICK TO FLY AGAIN');
    if ($g->mode === 'play' && $g->paused) $title('PAUSED', 'ESC TO GO ON', '');
};

$app = new App('Sky Patrol', 600, 800, fn () => col()->full()->bg('#05080f')->schema([canvas($draw)->flex1()->id('game')]));
$app->icon(file_exists("$dir/jet.png") ? "$dir/jet.png" : '');
$last = microtime(true);
$app->every(1 / 120, function () use ($update, &$last) {
    $now = microtime(true);
    $update(min(0.05, $now - $last));
    $last = $now;
});
$app->onKey(function (int $key) use ($g) {
    if ($key === Key::ESCAPE && $g->mode === 'play') $g->paused = !$g->paused;
});
$app->onShutdown(function () use ($audio) { $audio->playing = []; });

if ($shots = getenv('PHPUI_SCREENSHOTS')) {
    // A moment of play, for the docs.
    @mkdir($shots, 0777, true);
    $reset();
    $g->autopilot = true;
    mt_srand(5);
    // Until a busy moment: enemies on screen and something exploding.
    for ($i = 0; $i < 3000; $i++) {
        $update(1 / 60);
        if ($i > 500 && count($g->enemies) >= 4 && count($g->particles) > 40) break;
    }
    [$g->hp, $g->invulnerable] = [4, 0];
    $app->screenshot("$shots/skypatrol.png", 600, 800, 1);
    exit;
}
if (!getenv('PHPUI_NO_RUN')) $app->run();
