<?php

declare(strict_types=1);

require __DIR__ . '/../src/autoload.php';

use PhpSdl\Renderer;
use PhpSdl\Sound;
use PhpUi\{App, Canvas, Key, Mouse};
use function PhpUi\{canvas, col, dataDir, state};

// Island Hop: 3D from 2D. Every model (jet, drones, trees, houses, a lighthouse) is a
// voxel shape cut into flat slices; drawing the slices stacked and turned together
// makes them solid from any angle ("sprite stacking"). The camera flies behind the jet
// and the whole world turns as you steer. Art and sound are made by this file.
// A/D or ←/→ turn, W/S or ↑/↓ speed, Space or click shoots. Esc pauses.

defined('MAP') || define('MAP', 384);     // the world wraps every MAP units
defined('ZOOM') || define('ZOOM', 3);     // screen pixels per world unit
defined('TILT') || define('TILT', 0.62);  // how flat the ground looks: 1 is straight down

$dir = dataDir('myphp-islandhop') . '/v2';
@mkdir($dir, 0777, true);
$hex = fn (string $c) => [hexdec(substr($c, 1, 2)), hexdec(substr($c, 3, 2)), hexdec(substr($c, 5, 2))];

// Noise, as in Sky Patrol, wrapping both ways.
$noise = function (int $seed, int $cell) {
    mt_srand($seed);
    $n = intdiv(MAP, $cell);
    $grid = [];
    for ($i = 0; $i < $n * $n; $i++) $grid[] = mt_rand() / mt_getrandmax();
    return function (float $x, float $y) use ($grid, $n, $cell): float {
        [$fx, $fy] = [$x / $cell, $y / $cell];
        [$ix, $iy] = [(int) floor($fx), (int) floor($fy)];
        [$tx, $ty] = [$fx - $ix, $fy - $iy];
        [$tx, $ty] = [$tx * $tx * (3 - 2 * $tx), $ty * $ty * (3 - 2 * $ty)];
        $at = fn ($a, $b) => $grid[(($b % $n + $n) % $n) * $n + ($a % $n + $n) % $n];
        $top = $at($ix, $iy) + ($at($ix + 1, $iy) - $at($ix, $iy)) * $tx;
        $bottom = $at($ix, $iy + 1) + ($at($ix + 1, $iy + 1) - $at($ix, $iy + 1)) * $tx;
        return $top + ($bottom - $top) * $ty;
    };
};
$octaves = [[$noise(41, 96), 0.55], [$noise(43, 32), 0.28], [$noise(47, 8), 0.17]];
$height = function (float $x, float $y) use ($octaves): float {
    $h = 0.0;
    foreach ($octaves as [$n, $w]) $h += $n($x, $y) * $w;
    return $h;
};

// The ground: sea, surf, sand, meadows and woods.
$groundFile = "$dir/ground.png";
if (!is_file($groundFile)) {
    $bands = array_map(fn ($b) => [$b[0], $hex($b[1])], [[0.44, '#1b5a99'], [0.5, '#2470b8'], [0.53, '#4b9bd6'],
        [0.545, '#d9eef7'], [0.565, '#ead9a2'], [0.64, '#6cbc52'], [0.72, '#4f9e44'], [9, '#3c8239']]);
    mt_srand(9);
    $px = '';
    for ($y = 0; $y < MAP; $y++) {
        $row = [];
        for ($x = 0; $x < MAP; $x++) {
            $h = $height($x, $y);
            foreach ($bands as [$top, $c]) if ($h < $top) break;
            $k = $h < 0.53 && mt_rand(0, 90) === 0 ? 1.25 : ($h >= 0.565 ? 1 + ($h - $height($x - 1, $y - 1)) * 6 : 1);
            foreach ($c as $v) $row[] = max(0, min(255, (int) ($v * $k)));
            $row[] = 255;
        }
        $px .= pack('C*', ...$row);
    }
    Renderer::savePng($groundFile, MAP, MAP, $px);
}

/**
 * A voxel model, sliced: $voxel(x, y, z) gives a color or null. Each layer becomes a
 * top (lit) and a side (shaded) image; drawn stacked, they make the model solid. All
 * slices of all models go in one atlas, so the GPU draws a scene in a few calls.
 */
$slices = [];   // name => [w, d, h, [[top rgba, side rgba], ...]]
$model = function (string $name, int $w, int $d, int $h, callable $voxel) use (&$slices, $hex): void {
    $layers = [];
    for ($z = 0; $z < $h; $z++) {
        [$a, $b] = ['', ''];
        for ($y = 0; $y < $d; $y++) for ($x = 0; $x < $w; $x++) {
            $c = $voxel($x, $y, $z);
            if ($c === null) { $a .= "\0\0\0\0"; $b .= "\0\0\0\0"; continue; }
            [$r, $g, $bl] = $hex($c);
            $a .= pack('C4', $r, $g, $bl, 255);
            // Sides darker toward the ground, as if shaded from above.
            $k = 0.55 + 0.25 * $z / max(1, $h - 1);
            $b .= pack('C4', (int) ($r * $k), (int) ($g * $k), (int) ($bl * $k), 255);
        }
        $layers[] = [$a, $b];
    }
    $slices[$name] = [$w, $d, $h, $layers];
};

$near = fn ($x, $y, $z, $cx, $cy, $cz, $r) => ($x - $cx) ** 2 + ($y - $cy) ** 2 + ($z - $cz) ** 2 < $r * $r;
mt_srand(5);
$atlasFile = "$dir/models.png";
$build = !is_file($atlasFile);
if ($build) {
    // Faces up (-y): nose at the top.
    $model('jet', 17, 20, 7, function ($x, $y, $z) {
        $cx = abs($x - 8);
        if ($z <= 3 && $y >= 2 && $y <= 17 && $cx <= ($y < 5 ? 1 : 2)) return $z === 3 && $y >= 5 && $y <= 8 ? '#5fe0ea' : ($cx === 2 ? '#3c4454' : '#5b6578');
        if ($z === 1 && $y >= 7 && $y <= 13 && $cx <= ($y - 6) * 1.2) return $cx >= ($y - 6) * 1.2 - 1.3 ? '#ff8a2a' : '#4b5466';
        if ($z >= 3 && $z <= 6 && $y >= 14 && $y <= 17 && $cx === 2 && $y >= 14 + ($z - 3)) return '#d4511b';
        if ($z <= 2 && $y >= 18 && $y <= 19 && $cx === 1) return '#ffb02e';
        if ($y >= 0 && $y < 2 && $cx === 0 && $z >= 1 && $z <= 2) return '#8e99ad';
        return null;
    });
    $model('drone', 13, 13, 6, function ($x, $y, $z) use ($near) {
        $r = hypot($x - 6, $y - 6);
        if ($z <= 1 && $r <= 6 && $r >= 4.6) return '#c8303e';
        if ($z <= 2 && $r < 4.6) return $z === 2 ? '#ff6b6b' : '#8f1f2c';
        if ($z >= 3 && $near($x, $y, $z, 6, 6, 2.5, 2.6)) return $near($x, $y, $z, 6, 4.5, 3.5, 1.2) ? '#ffd23f' : '#2b1a22';
        return null;
    });
    $model('oak', 11, 11, 13, function ($x, $y, $z) use ($near) {
        if ($z <= 4 && abs($x - 5) <= 0 && abs($y - 5) <= 1) return '#6b4a2b';
        if ($near($x, $y, $z, 5, 5, 8, 4.4) && mt_rand(0, 12)) return ['#2f7a34', '#3d9440', '#56b04a', '#6cc25a'][min(3, intdiv($z - 4, 2))];
        return null;
    });
    $model('pine', 9, 9, 16, function ($x, $y, $z) {
        if ($z <= 3 && $x === 4 && $y === 4) return '#5a3d22';
        $r = (16 - $z) * 0.36;
        if ($z >= 3 && hypot($x - 4, $y - 4) <= $r) return $z % 3 === 0 ? '#1f5c34' : '#2c7a45';
        return null;
    });
    $model('house', 14, 12, 12, function ($x, $y, $z) {
        $wall = $x >= 1 && $x <= 12 && $y >= 1 && $y <= 10;
        if ($z <= 5 && $wall) {
            $edge = $x === 1 || $x === 12 || $y === 1 || $y === 10;
            if ($edge && $z >= 2 && $z <= 3 && ($x % 3 === 0 || $y % 3 === 0)) return '#3b6ea8';
            if ($edge && $y === 10 && $x >= 6 && $x <= 7 && $z <= 3) return '#7a4a28';
            return $edge ? '#efe3c8' : '#d8c8a8';
        }
        // A gable roof along x.
        if ($z >= 6 && $x >= 0 && $x <= 13 && abs($y - 5.5) <= 6.5 - ($z - 6) * 1.3) return $z === 11 ? '#8f2f24' : '#c4473a';
        if ($z >= 6 && $z <= 9 && $x === 10 && $y === 3) return '#7d7d7d';
        return null;
    });
    $model('rock', 9, 8, 5, function ($x, $y, $z) use ($near) {
        return $near($x, $y, $z * 1.6, 4, 4, 0, 4.2) && mt_rand(0, 8) ? ($z >= 3 ? '#b5b5ad' : '#8c8c84') : null;
    });
    $model('lighthouse', 9, 9, 28, function ($x, $y, $z) {
        $r = hypot($x - 4, $y - 4);
        if ($z <= 1 && $r <= 4.4) return '#9a9a92';
        if ($z >= 2 && $z <= 21 && $r <= 3.2 - $z * 0.05) return intdiv($z - 2, 4) % 2 ? '#f2f2ee' : '#d63c33';
        if ($z >= 22 && $z <= 24 && $r <= 2.4) return $r <= 1.5 ? '#ffe680' : '#2a2a30';
        if ($z >= 25 && $r <= 2.6 - ($z - 25)) return '#d63c33';
        return null;
    });
}
// The atlas: one row a model, its layers side by side (top, then side).
$models = [];
$names = ['jet' => [17, 20, 7], 'drone' => [13, 13, 6], 'oak' => [11, 11, 13], 'pine' => [9, 9, 16], 'house' => [14, 12, 12],
    'rock' => [9, 8, 5], 'lighthouse' => [9, 9, 28]];
[$row, $width] = [0, 0];
foreach ($names as $name => [$w, $d, $h]) {
    $layers = [];
    for ($z = 0; $z < $h; $z++) $layers[] = [[$z * 2 * $w, $row, $w, $d], [($z * 2 + 1) * $w, $row, $w, $d]];
    $models[$name] = ['w' => $w, 'd' => $d, 'h' => $h, 'layers' => $layers];
    $width = max($width, $h * 2 * $w);
    $row += $d;
}
if ($build) {
    $px = array_fill(0, $row, str_repeat("\0", $width * 4));
    foreach ($slices as $name => [$w, $d, $h, $layers]) {
        $top = $models[$name]['layers'][0][0][1];
        foreach ($layers as $z => $pair) foreach ($pair as $which => $rgba) {
            for ($y = 0; $y < $d; $y++) $px[$top + $y] = substr_replace($px[$top + $y], substr($rgba, $y * $w * 4, $w * 4), ($z * 2 + $which) * $w * 4, $w * 4);
        }
    }
    Renderer::savePng($atlasFile, $width, $row, implode('', $px));
    // The top of the jet, for the window's icon.
    [$w, $d, , $layers] = $slices['jet'];
    Renderer::savePng("$dir/icon.png", $w, $d, $layers[3][0]);
}

// Things on the land: woods inland, houses and rocks near the shore, one lighthouse.
$places = [];
mt_srand(77);
for ($i = 0; $i < 900 && count($places) < 170; $i++) {
    [$x, $y] = [mt_rand(0, MAP - 1), mt_rand(0, MAP - 1)];
    $h = $height($x, $y);
    if ($h < 0.57) continue;
    foreach ($places as $p) if (hypot($p['x'] - $x, $p['y'] - $y) < 9) continue 2;
    $kind = $h > 0.66 ? (mt_rand(0, 2) ? 'pine' : 'oak') : ['oak', 'oak', 'house', 'rock', 'pine'][mt_rand(0, 4)];
    $places[] = ['kind' => $kind, 'x' => $x, 'y' => $y, 'z' => 0, 'yaw' => mt_rand(0, 3) * M_PI / 2];
}
for ($i = 0; $i < 400; $i++) {
    [$x, $y] = [mt_rand(0, MAP - 1), mt_rand(0, MAP - 1)];
    $h = $height($x, $y);
    if ($h > 0.565 && $h < 0.58) { $places[] = ['kind' => 'lighthouse', 'x' => $x, 'y' => $y, 'z' => 0, 'yaw' => 0]; break; }
}

// Sounds, synthesized.
$wav = function (string $name, float $seconds, callable $sample) use ($dir): string {
    $file = "$dir/$name.wav";
    if (is_file($file)) return $file;
    [$rate, $data] = [22050, ''];
    for ($i = 0, $n = (int) ($seconds * $rate); $i < $n; $i++) $data .= pack('v', (int) (max(-1, min(1, $sample($i / $rate, $i / $n))) * 26000) & 0xffff);
    file_put_contents($file, 'RIFF' . pack('V', 36 + strlen($data)) . 'WAVEfmt ' . pack('VvvVVvv', 16, 1, 1, $rate, $rate * 2, 2, 16) . 'data' . pack('V', strlen($data)) . $data);
    return $file;
};
mt_srand();
$sounds = [
    'shoot' => $wav('shoot', 0.06, fn ($t, $p) => (fmod($t * (1300 - 700 * $p), 1) < 0.5 ? 1 : -1) * (1 - $p) * 0.3),
    'boom' => $wav('boom', 0.7, function ($t, $p) { static $v = 0.0; $v += (mt_rand(-1000, 1000) / 1000 - $v) * (0.5 - 0.45 * $p); return $v * (1 - $p) ** 2 * 1.6; }),
];
$audio = new class { public bool $on = false; public array $playing = []; };
$audio->on = Sound::available();
$sfx = function (string $name, float $volume) use ($audio, $sounds) {
    if (!$audio->on) return;
    $audio->playing = array_values(array_filter($audio->playing, fn (Sound $s) => $s->playing()));
    if (count($audio->playing) > 10) return;
    $s = new Sound($sounds[$name]);
    $s->volume($volume);
    $s->play();
    $audio->playing[] = $s;
};

// Game ----------------------------------------------------------------------------

$g = state(new class {
    public float $x = 0, $y = 0, $z = 14, $yaw = 0, $speed = 46, $turn = 0, $time = 0, $gun = 0, $spawn = 2;
    public int $score = 0;
    public bool $paused = false, $autopilot = false;
    public array $drones = [], $bullets = [], $particles = [];
});
// Start over the first lighthouse, or anywhere on land.
$home = end($places) ?: ['x' => MAP / 2, 'y' => MAP / 2];
if ($g->time == 0) [$g->x, $g->y] = [$home['x'], $home['y'] + 40];

/** The shortest way from $a to $b in a world that wraps. */
$wrap = fn (float $d) => $d - MAP * round($d / MAP);

$boom = function (float $x, float $y, float $z, float $size) use ($g, $sfx) {
    for ($i = 0; $i < 26 * $size; $i++) {
        $a = mt_rand(0, 628) / 100;
        $v = mt_rand(10, 60) * $size;
        $g->particles[] = ['x' => $x, 'y' => $y, 'z' => $z, 'vx' => cos($a) * $v, 'vy' => sin($a) * $v, 'vz' => mt_rand(-20, 50),
            'life' => mt_rand(40, 100) / 100, 'color' => ['#ffffff', '#ffe066', '#ffb02e', '#ff6b2c', '#5a5560'][mt_rand(0, 4)]];
    }
    $sfx('boom', 0.5 * $size);
};

$update = function (float $dt) use ($g, $wrap, $boom, $sfx) {
    $g->time += $dt;
    if ($g->paused) return;
    $app = App::current();
    $left = $app->isKeyDown(Key::LEFT) || $app->isKeyDown(Key::A);
    $right = $app->isKeyDown(Key::RIGHT) || $app->isKeyDown(Key::D);
    if ($g->autopilot) [$left, $right] = [false, fmod($g->time, 6) < 2.5];
    $target = ($right ? 1 : 0) - ($left ? 1 : 0);
    $g->turn += ($target - $g->turn) * min(1, $dt * 5);
    $g->yaw += $g->turn * 1.6 * $dt;
    $faster = $app->isKeyDown(Key::UP) || $app->isKeyDown(Key::W);
    $slower = $app->isKeyDown(Key::DOWN) || $app->isKeyDown(Key::S);
    $g->speed = max(24, min(90, $g->speed + (($faster ? 40 : 0) - ($slower ? 40 : 0)) * $dt));
    [$g->x, $g->y] = [fmod($g->x + sin($g->yaw) * $g->speed * $dt + MAP, MAP), fmod($g->y - cos($g->yaw) * $g->speed * $dt + MAP, MAP)];

    // Guns.
    $g->gun -= $dt;
    if (($app->isKeyDown(Key::SPACE) || $app->isMouseDown(Mouse::LEFT) || $g->autopilot) && $g->gun <= 0) {
        $g->gun = 0.1;
        foreach ([-3, 3] as $side) {
            $g->bullets[] = ['x' => $g->x + cos($g->yaw) * $side, 'y' => $g->y + sin($g->yaw) * $side, 'z' => $g->z,
                'vx' => sin($g->yaw) * 220, 'vy' => -cos($g->yaw) * 220, 'life' => 1.1];
        }
        $sfx('shoot', 0.12);
    }
    foreach ($g->bullets as $i => &$b) {
        [$b['x'], $b['y']] = [$b['x'] + $b['vx'] * $dt, $b['y'] + $b['vy'] * $dt];
        $b['life'] -= $dt;
        if ($b['life'] <= 0) unset($g->bullets[$i]);
    }
    unset($b);

    // Drones come from ahead and circle in.
    $g->spawn -= $dt;
    if ($g->spawn <= 0 && count($g->drones) < 6) {
        $g->spawn = mt_rand(15, 35) / 10;
        $a = $g->yaw + mt_rand(-60, 60) / 100;
        $g->drones[] = ['x' => fmod($g->x + sin($a) * 150 + MAP, MAP), 'y' => fmod($g->y - cos($a) * 150 + MAP, MAP), 'z' => mt_rand(8, 20),
            'yaw' => $a + M_PI, 'hp' => 3, 'phase' => mt_rand(0, 100) / 10];
    }
    foreach ($g->drones as $i => &$d) {
        $want = atan2($wrap($g->x - $d['x']), -$wrap($g->y - $d['y'])) + sin($g->time + $d['phase']) * 0.8;
        $d['yaw'] += atan2(sin($want - $d['yaw']), cos($want - $d['yaw'])) * min(1, $dt * 1.5);
        [$d['x'], $d['y']] = [fmod($d['x'] + sin($d['yaw']) * 30 * $dt + MAP, MAP), fmod($d['y'] - cos($d['yaw']) * 30 * $dt + MAP, MAP)];
        foreach ($g->bullets as $bi => $b) {
            if (hypot($wrap($b['x'] - $d['x']), $wrap($b['y'] - $d['y'])) < 7 && abs($b['z'] - $d['z']) < 9) {
                unset($g->bullets[$bi]);
                if (--$d['hp'] <= 0) { $boom($d['x'], $d['y'], $d['z'], 1); $g->score += 100; unset($g->drones[$i]); continue 2; }
            }
        }
        if (hypot($wrap($g->x - $d['x']), $wrap($g->y - $d['y'])) > 260) unset($g->drones[$i]);
    }
    unset($d);
    foreach ($g->particles as $i => &$p) {
        [$p['x'], $p['y'], $p['z']] = [$p['x'] + $p['vx'] * $dt, $p['y'] + $p['vy'] * $dt, max(0, $p['z'] + $p['vz'] * $dt)];
        $p['vz'] -= 40 * $dt;
        $p['life'] -= $dt;
        if ($p['life'] <= 0) unset($g->particles[$i]);
    }
    unset($p);
    if (mt_rand(0, 1)) {
        $g->particles[] = ['x' => $g->x - sin($g->yaw) * 10, 'y' => $g->y + cos($g->yaw) * 10, 'z' => $g->z + 1, 'vx' => 0, 'vy' => 0, 'vz' => 0,
            'life' => 0.25, 'color' => mt_rand(0, 1) ? '#ffb02e' : '#fff3b0'];
    }
};

// Drawing ---------------------------------------------------------------------------

$draw = function (Canvas $c) use ($g, $models, $places, $groundFile, $atlasFile, $wrap) {
    $c->imageSmoothingEnabled = false;
    [$cw, $ch] = [$c->width, $c->height];
    [$cx, $cy] = [$cw / 2, $ch * 0.66];
    // The camera turns with the jet: world angle -yaw on screen.
    [$co, $si] = [cos(-$g->yaw), sin(-$g->yaw)];
    $k = ZOOM;
    $q = ZOOM * TILT;
    /** World (x, y, height) to screen. */
    $at = function (float $x, float $y, float $z) use ($g, $wrap, $co, $si, $k, $q, $cx, $cy): array {
        [$dx, $dy] = [$wrap($x - $g->x), $wrap($y - $g->y)];
        return [$cx + $k * ($co * $dx - $si * $dy), $cy + $q * ($si * $dx + $co * $dy) - $k * $z];
    };

    $c->fillStyle = '#1b5a99';
    $c->fillRect(0, 0, $cw, $ch);
    // The ground, turned and tilted; nine copies around the camera, as the world wraps.
    foreach ([-1, 0, 1] as $i) foreach ([-1, 0, 1] as $j) {
        [$tx, $ty] = [-$g->x + $i * MAP, -$g->y + $j * MAP];
        $c->setTransform($k * $co, $q * $si, -$k * $si, $q * $co, $cx + $k * ($co * $tx - $si * $ty), $cy + $q * ($si * $tx + $co * $ty));
        $c->drawImage($groundFile, 0, 0);
    }

    // Everything that stands: models, nearest the camera last.
    $things = [];
    foreach ($places as $p) {
        [$sx, $sy] = $at($p['x'], $p['y'], 0);
        if ($sx < -80 || $sx > $cw + 80 || $sy < -160 || $sy > $ch + 60) continue;
        $things[] = [$sy, $p['kind'], $sx, $sy, $p['yaw'], 0];
    }
    foreach ($g->drones as $d) {
        [$sx, $sy] = $at($d['x'], $d['y'], 0);
        $things[] = [$sy, 'drone', $sx, $sy, $d['yaw'] + $g->time * 3, $d['z']];
    }
    $things[] = [$cy + 0.01, 'jet', $cx, $cy, $g->yaw, $g->z];
    // Things in the air pass over everything on the ground; each group far to near.
    usort($things, fn ($a, $b) => [$a[5] > 0, $a[0]] <=> [$b[5] > 0, $b[0]]);

    foreach ($things as [, $kind, $sx, $sy, $yaw, $z]) {
        $m = $models[$kind];
        $phi = $yaw - $g->yaw + ($kind === 'jet' ? $g->turn * 0.12 : 0);
        [$pc, $ps] = [cos($phi), sin($phi)];
        // The jet a size up, so it reads as the hero.
        $u = $kind === 'jet' ? 1.6 : 1.0;
        [$a, $b, $cc, $d] = [$k * $u * $pc, $q * $u * $ps, -$k * $u * $ps, $q * $u * $pc];
        // A soft shadow on the ground under things that fly.
        if ($z > 0) {
            $c->setTransform(1, 0, 0, 1, 0, 0);
            $c->globalAlpha = 0.22;
            $c->fillStyle = '#000000';
            $c->beginPath();
            $c->ellipse($sx, $sy, $m['w'] * $k * $u * 0.45, $m['d'] * $q * $u * 0.45, 0, 0, 2 * M_PI);
            $c->fill();
            $c->globalAlpha = 1;
        }
        // The stack: each layer as ZOOM slices, shaded sides below a lit top.
        foreach ($m['layers'] as $layer => [$top, $side]) {
            $steps = (int) ceil($k * $u);
            for ($s = 0; $s < $steps; $s++) {
                $c->setTransform($a, $b, $cc, $d, $sx, $sy - $k * $z - $k * $u * $layer - $s);
                $c->drawImage($atlasFile, ...($s === $steps - 1 ? $top : $side), ...[-$m['w'] / 2, -$m['d'] / 2, $m['w'], $m['d']]);
            }
        }
    }
    $c->setTransform(1, 0, 0, 1, 0, 0);

    foreach ($g->bullets as $b) {
        [$sx, $sy] = $at($b['x'], $b['y'], $b['z'] + 2);
        $c->fillStyle = '#fff6c4';
        $c->fillRect(round($sx) - 1, round($sy) - 1, 3, 3);
    }
    foreach ($g->particles as $p) {
        [$sx, $sy] = $at($p['x'], $p['y'], $p['z']);
        $c->globalAlpha = min(1, $p['life'] * 2);
        $c->fillStyle = $p['color'];
        $c->fillRect(round($sx), round($sy), 3, 3);
    }
    $c->globalAlpha = 1;

    // HUD.
    $c->font = '11px mono-semibold';
    $c->letterSpacing = '1.5px';
    $c->textBaseline = 'middle';
    foreach ([[sprintf('SCORE %05d', $g->score), 14, 'left'], [sprintf('SPD %03d  ALT %02d', $g->speed * 4, $g->z * 10), $cw - 14, 'right']] as [$text, $x, $align]) {
        $w = $c->measureText($text)->width + 16;
        $left = $align === 'right' ? $x - $w : $x;
        $c->fillStyle = 'rgba(10, 18, 32, 0.72)';
        $c->beginPath();
        $c->roundRect($left, 14, $w, 22, 4);
        $c->fill();
        $c->fillStyle = '#dbe6f5';
        $c->textAlign = 'left';
        $c->fillText($text, $left + 8, 25);
    }
    if ($g->paused) {
        $c->font = 'bold 36px sans-serif';
        $c->textAlign = 'center';
        $c->fillStyle = '#ffffff';
        $c->fillText('PAUSED', $cw / 2, $ch / 2);
    }
    $c->letterSpacing = '0px';
};

$app = new App('Island Hop', 960, 600, fn () => col()->full()->bg('#1b5a99')->schema([canvas($draw)->flex1()->id('game')]));
$app->icon("$dir/icon.png");
$last = microtime(true);
$app->every(1 / 120, function () use ($update, &$last) {
    $now = microtime(true);
    $update(min(0.05, $now - $last));
    $last = $now;
});
$app->onKey(function (int $key) use ($g) { if ($key === Key::ESCAPE) $g->paused = !$g->paused; });

if ($shots = getenv('PHPUI_SCREENSHOTS')) {
    @mkdir($shots, 0777, true);
    $g->autopilot = true;
    mt_srand(3);
    for ($i = 0; $i < 400; $i++) $update(1 / 60);
    $app->screenshot("$shots/islandhop.png", 960, 600, 1);
    exit;
}
if (!getenv('PHPUI_NO_RUN')) $app->run();
