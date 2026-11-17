<?php

declare(strict_types=1);

require __DIR__ . '/../src/autoload.php';

use PhpSdl\Renderer;
use PhpSdl\Sound;
use PhpUi\{App, Camera3D, Canvas, Key, Mouse, Voxels};
use function PhpUi\{canvas, col, dataDir, state};

// Island Hop: 3D from 2D. The ground is one flat image seen in perspective ("mode 7",
// drawn by the GPU with Canvas::drawGround). Every model on it (the jet, drones, trees,
// houses, mountains, clouds) is a voxel shape cut into flat slices; drawn stacked and
// turned together, the slices make it solid from any angle ("sprite stacking"). The
// camera chases the jet low over the sea. Art and sound are made by this file.
// A/D or ←/→ turn, W/S or ↑/↓ speed, Space or click shoots, V switches to the cockpit
// and back. Esc pauses.

defined('MAP') || define('MAP', 512);        // ground texels; the world wraps every MAP * TEXEL units
defined('TEXEL') || define('TEXEL', 2);      // world units a ground texel
defined('JET_Z') || define('JET_Z', 60);     // how high the jet flies
defined('CAM_UP') || define('CAM_UP', 19);   // the camera, above the jet...
defined('CHASE') || define('CHASE', 40);     // ...and behind it
defined('FOG') || define('FOG', 900);        // how far one sees

$dir = dataDir('myphp-islandhop') . '/v3';
@mkdir($dir, 0777, true);
$hex = fn (string $c) => [hexdec(substr($c, 1, 2)), hexdec(substr($c, 3, 2)), hexdec(substr($c, 5, 2))];
$world = MAP * TEXEL;

// Noise, wrapping both ways, in texels.
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
$octaves = [[$noise(41, 128), 0.55], [$noise(43, 32), 0.28], [$noise(47, 8), 0.17]];
$height = function (float $x, float $y) use ($octaves): float {
    $h = 0.0;
    foreach ($octaves as [$n, $w]) $h += $n($x, $y) * $w;
    return $h;
};

// The ground: deep and shallow sea, surf, sand, meadows and woods.
$groundFile = "$dir/ground.png";
if (!is_file($groundFile)) {
    $bands = array_map(fn ($b) => [$b[0], $hex($b[1])], [[0.42, '#17508f'], [0.48, '#1f68ad'], [0.52, '#3a8fd0'],
        [0.535, '#7cc4e6'], [0.548, '#e6f4f8'], [0.57, '#ecd8a0'], [0.63, '#7cc35a'], [0.7, '#5eaa48'], [9, '#468f3e']]);
    mt_srand(9);
    $px = '';
    for ($y = 0; $y < MAP; $y++) {
        $row = [];
        for ($x = 0; $x < MAP; $x++) {
            $h = $height($x, $y);
            foreach ($bands as [$top, $c]) if ($h < $top) break;
            // Glints on the sea; on land, slopes facing the sun lighter.
            $k = $h < 0.52 && mt_rand(0, 70) === 0 ? 1.2 : ($h >= 0.57 ? 1 + ($h - $height($x - 1, $y - 1)) * 7 : 1);
            foreach ($c as $v) $row[] = max(0, min(255, (int) ($v * $k)));
            $row[] = 255;
        }
        $px .= pack('C*', ...$row);
    }
    Renderer::savePng($groundFile, MAP, MAP, $px);
}

// The models: voxel shapes, sliced into one atlas for sprite stacking (PhpUi\Voxels).
// [width, depth, height] in voxels, world units a voxel, how dark the lowest sides are.
$specs = [
    'jet' => [25, 30, 9, 1, 0.55], 'drone' => [13, 13, 6, 1.5, 0.55], 'oak' => [11, 11, 13, 1.4, 0.55],
    'pine' => [9, 9, 16, 1.4, 0.55], 'house' => [14, 12, 12, 1.4, 0.6], 'rock' => [9, 8, 5, 1.4, 0.6],
    'lighthouse' => [9, 9, 28, 1.4, 0.6], 'mountain' => [36, 36, 24, 2.6, 0.5],
    'cloud' => [28, 20, 8, 3, 0.86], 'cloud2' => [22, 18, 7, 3, 0.86],
];
$shapes = [];
$model = function (string $name, callable $voxel) use (&$shapes, $specs): void {
    [$w, $d, $h, $unit, $shade] = $specs[$name];
    $shapes[$name] = ['size' => [$w, $d, $h], 'unit' => $unit, 'shade' => $shade, 'voxel' => $voxel];
};
$near = fn ($x, $y, $z, $cx, $cy, $cz, $r) => ($x - $cx) ** 2 + ($y - $cy) ** 2 + ($z - $cz) ** 2 < $r * $r;
mt_srand(5);

// The jet, our own: a white twin-tail fighter with blue wing bands. Nose up (-y).
$model('jet', function ($x, $y, $z) {
    $dx = abs($x - 12);
    $hull = $y < 4 ? 0 : ($y < 9 ? 1 : ($y < 25 ? 2 : 1));
    if ($y >= 1 && $y <= 28 && $dx <= $hull && $z >= ($y < 5 ? 2 : 1) && $z <= ($y < 5 ? 2 : 3)) {
        return $z === 1 ? '#8c97a8' : ($dx === 0 && $y >= 13 && $z === 3 ? '#2d6cdf' : '#eef2f7');
    }
    if ($y >= 7 && $y <= 12 && $dx <= 1 && $z >= 4 && $z <= ($y <= 10 ? 5 : 4)) return $y === 8 && $z === 5 ? '#bfe9ff' : '#24314d';
    if ($z === 2 && $y >= 11 && $y <= 21 && $dx >= 2 && $dx <= min(12, ($y - 9) * 1.25)) {
        if ($dx >= 11) return '#ffc93a';
        return $y >= 18 && $dx >= 5 ? '#2d6cdf' : '#e2e8f0';
    }
    if ($z === 2 && $y >= 24 && $y <= 28 && $dx >= 2 && $dx <= min(7, 2 + ($y - 23) * 1.4)) return '#d6dde8';
    if ($dx === 3 && $z >= 3 && $z <= 8 && $y >= 20 + ($z - 3) && $y <= 27) return $z >= 7 ? '#2d6cdf' : '#e2e8f0';
    if ($y === 29 && $dx >= 1 && $dx <= 2 && $z === 2) return $dx === 1 ? '#ff9a3a' : '#4a4e58';
    if ($y === 0 && $dx === 0 && $z === 2) return '#b8c2d0';
    return null;
});
$model('drone', function ($x, $y, $z) use ($near) {
    $r = hypot($x - 6, $y - 6);
    if ($z <= 1 && $r <= 6 && $r >= 4.6) return '#c8303e';
    if ($z <= 2 && $r < 4.6) return $z === 2 ? '#ff6b6b' : '#8f1f2c';
    if ($z >= 3 && $near($x, $y, $z, 6, 6, 2.5, 2.6)) return $near($x, $y, $z, 6, 4.5, 3.5, 1.2) ? '#ffd23f' : '#2b1a22';
    return null;
});
$model('oak', function ($x, $y, $z) use ($near) {
    if ($z <= 4 && $x === 5 && abs($y - 5) <= 1) return '#6b4a2b';
    if ($near($x, $y, $z, 5, 5, 8, 4.4) && mt_rand(0, 12)) return ['#2f7a34', '#3d9440', '#56b04a', '#6cc25a'][min(3, intdiv($z - 4, 2))];
    return null;
});
$model('pine', function ($x, $y, $z) {
    if ($z <= 3 && $x === 4 && $y === 4) return '#5a3d22';
    if ($z >= 3 && hypot($x - 4, $y - 4) <= (16 - $z) * 0.36) return $z % 3 === 0 ? '#1f5c34' : '#2c7a45';
    return null;
});
$model('house', function ($x, $y, $z) {
    $wall = $x >= 1 && $x <= 12 && $y >= 1 && $y <= 10;
    if ($z <= 5 && $wall) {
        $edge = $x === 1 || $x === 12 || $y === 1 || $y === 10;
        if ($edge && $z >= 2 && $z <= 3 && ($x % 3 === 0 || $y % 3 === 0)) return '#3b6ea8';
        if ($edge && $y === 10 && $x >= 6 && $x <= 7 && $z <= 3) return '#7a4a28';
        return $edge ? '#efe3c8' : '#d8c8a8';
    }
    if ($z >= 6 && abs($y - 5.5) <= 6.5 - ($z - 6) * 1.3) return $z === 11 ? '#8f2f24' : '#c4473a';
    if ($z >= 6 && $z <= 9 && $x === 10 && $y === 3) return '#7d7d7d';
    return null;
});
$model('rock', function ($x, $y, $z) use ($near) {
    return $near($x, $y, $z * 1.6, 4, 4, 0, 4.2) && mt_rand(0, 8) ? ($z >= 3 ? '#b5b5ad' : '#8c8c84') : null;
});
$model('lighthouse', function ($x, $y, $z) {
    $r = hypot($x - 4, $y - 4);
    if ($z <= 1 && $r <= 4.4) return '#9a9a92';
    if ($z >= 2 && $z <= 21 && $r <= 3.2 - $z * 0.05) return intdiv($z - 2, 4) % 2 ? '#f2f2ee' : '#d63c33';
    if ($z >= 22 && $z <= 24 && $r <= 2.4) return $r <= 1.5 ? '#ffe680' : '#2a2a30';
    if ($z >= 25 && $r <= 2.6 - ($z - 25)) return '#d63c33';
    return null;
});
// A mountain: a ridged cone, grass at the foot, bare rock, snow on top.
$ridge = $noise(61, 4);
$model('mountain', function ($x, $y, $z) use ($ridge) {
    $r = hypot($x - 17.5, $y - 17.5) / 17.5;
    $top = 24 * (1 - $r) ** 1.2 + ($ridge($x * 3, $y * 3) - 0.5) * 7 * (1 - $r);
    if ($r > 1 || $z > $top) return null;
    $v = mt_rand(0, 9);
    if ($z >= 17 - $v % 3) return $v ? '#f4f7fb' : '#d5dde8';
    if ($z >= 6 + $v % 3) return ['#8b8478', '#7a7368', '#9a9284'][$v % 3];
    return ['#5a9a48', '#4d8a3f', '#6aa852'][$v % 3];
});
// Clouds: lumps of round puffs, flat underneath.
foreach (['cloud' => [28, 20, 8], 'cloud2' => [22, 18, 7]] as $name => [$w, $d, $h]) {
    $puffs = [];
    for ($i = 0; $i < 7; $i++) {
        $puffs[] = [mt_rand(6, $w - 7), mt_rand(5, $d - 6), mt_rand(0, 2), mt_rand(35, 60) / 10];
    }
    $model($name, function ($x, $y, $z) use ($puffs, $near) {
        foreach ($puffs as [$px, $py, $pz, $r]) {
            if ($near($x, $y, $z * 1.4, $px, $py, $pz, $r)) return $z >= 4 ? '#ffffff' : '#f2f6fb';
        }
        return null;
    });
}
$voxels = new Voxels("$dir/models.png", $shapes);
$models = $voxels->models;
$atlasFile = $voxels->file;
// A white pixel, stretched into contrails.
$whiteFile = "$dir/white.png";
if (!is_file($whiteFile)) Renderer::savePng($whiteFile, 1, 1, "\xff\xff\xff\xff");
// The top of the jet, for the window's icon.
if ($voxels->built) Renderer::savePng("$dir/icon.png", 25, 30, $voxels->layer('jet', 2));

// Things on the land, in world units: mountains inland, woods, houses and rocks near
// the shore, one lighthouse. Clouds low under the jet and high above it.
$places = [];
mt_srand(77);
$free = function (float $x, float $y, float $room) use (&$places): bool {
    foreach ($places as $p) if (hypot($p['x'] - $x, $p['y'] - $y) < $room + $p['room']) return false;
    return true;
};
for ($i = 0; $i < 3000 && count($places) < 14; $i++) {
    [$x, $y] = [mt_rand(0, MAP - 1), mt_rand(0, MAP - 1)];
    if ($height($x, $y) < 0.68 || !$free($x * TEXEL, $y * TEXEL, 40)) continue;
    $places[] = ['kind' => 'mountain', 'x' => $x * TEXEL, 'y' => $y * TEXEL, 'z' => 0, 'yaw' => mt_rand(0, 628) / 100, 'room' => 40];
}
for ($i = 0; $i < 4000 && count($places) < 260; $i++) {
    [$x, $y] = [mt_rand(0, MAP - 1), mt_rand(0, MAP - 1)];
    $h = $height($x, $y);
    if ($h < 0.57 || !$free($x * TEXEL, $y * TEXEL, 8)) continue;
    $kind = $h > 0.64 ? (mt_rand(0, 2) ? 'pine' : 'oak') : ['oak', 'oak', 'house', 'rock', 'pine'][mt_rand(0, 4)];
    $places[] = ['kind' => $kind, 'x' => $x * TEXEL, 'y' => $y * TEXEL, 'z' => 0, 'yaw' => mt_rand(0, 3) * M_PI / 2, 'room' => 8];
}
for ($i = 0; $i < 4000; $i++) {
    [$x, $y] = [mt_rand(0, MAP - 1), mt_rand(0, MAP - 1)];
    $h = $height($x, $y);
    if ($h > 0.552 && $h < 0.565) { $places[] = ['kind' => 'lighthouse', 'x' => $x * TEXEL, 'y' => $y * TEXEL, 'z' => 0, 'yaw' => 0, 'room' => 8]; break; }
}
for ($i = 0; $i < 46; $i++) {
    $places[] = ['kind' => mt_rand(0, 1) ? 'cloud' : 'cloud2', 'x' => mt_rand(0, $world - 1), 'y' => mt_rand(0, $world - 1),
        'z' => $i % 3 ? mt_rand(110, 170) : mt_rand(18, 34), 'yaw' => mt_rand(0, 628) / 100, 'room' => 0];
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
    public float $x = 0, $y = 0, $yaw = 0, $view = 0, $speed = 130, $turn = 0, $time = 0, $gun = 0, $spawn = 2, $trail = 0;
    public int $score = 0;
    public bool $paused = false, $autopilot = false;
    public string $camera = 'chase';
    public array $drones = [], $bullets = [], $particles = [], $trails = [[], []];
});
// Start near the lighthouse, heading for land.
$home = current(array_filter($places, fn ($p) => $p['kind'] === 'lighthouse')) ?: ['x' => $world / 2, 'y' => $world / 2];
if ($g->time == 0) [$g->x, $g->y] = [$home['x'], $home['y'] + 260];

/** The shortest way across a world that wraps. */
$wrap = fn (float $d) => $d - $world * round($d / $world);
$ahead = fn (float $yaw) => [sin($yaw), -cos($yaw)];

$boom = function (float $x, float $y, float $z, float $size) use ($g, $sfx) {
    for ($i = 0; $i < 30 * $size; $i++) {
        [$a, $e] = [mt_rand(0, 628) / 100, mt_rand(-150, 150) / 100];
        $v = mt_rand(20, 80) * $size;
        $g->particles[] = ['x' => $x, 'y' => $y, 'z' => $z, 'vx' => cos($a) * cos($e) * $v, 'vy' => sin($a) * cos($e) * $v, 'vz' => sin($e) * $v,
            'life' => mt_rand(50, 110) / 100, 'size' => 1.6, 'fall' => 40, 'color' => ['#ffffff', '#ffe066', '#ffb02e', '#ff6b2c', '#5a5560'][mt_rand(0, 4)]];
    }
    $sfx('boom', 0.5 * $size);
};

$update = function (float $dt) use ($g, $wrap, $ahead, $boom, $sfx, $world) {
    $g->time += $dt;
    if ($g->paused) return;
    $app = App::current();
    $left = $app->isKeyDown(Key::LEFT) || $app->isKeyDown(Key::A);
    $right = $app->isKeyDown(Key::RIGHT) || $app->isKeyDown(Key::D);
    if ($g->autopilot) [$left, $right] = [false, fmod($g->time, 6) < 2.5];
    $target = ($right ? 1 : 0) - ($left ? 1 : 0);
    $g->turn += ($target - $g->turn) * min(1, $dt * 4);
    $g->yaw += $g->turn * 1.1 * $dt;
    // The camera swings round after the jet, so turns show the jet's side.
    $g->view += atan2(sin($g->yaw - $g->view), cos($g->yaw - $g->view)) * min(1, $dt * 6);
    $faster = $app->isKeyDown(Key::UP) || $app->isKeyDown(Key::W);
    $slower = $app->isKeyDown(Key::DOWN) || $app->isKeyDown(Key::S);
    $g->speed = max(70, min(220, $g->speed + (($faster ? 90 : 0) - ($slower ? 90 : 0)) * $dt));
    [$fx, $fy] = $ahead($g->yaw);
    [$g->x, $g->y] = [fmod($g->x + $fx * $g->speed * $dt + $world, $world), fmod($g->y + $fy * $g->speed * $dt + $world, $world)];
    [$rx, $ry] = [-$fy, $fx];

    // Guns, under the wings.
    $g->gun -= $dt;
    if (($app->isKeyDown(Key::SPACE) || $app->isMouseDown(Mouse::LEFT) || $g->autopilot) && $g->gun <= 0) {
        $g->gun = 0.09;
        foreach ([-5, 5] as $side) {
            $g->bullets[] = ['x' => $g->x + $rx * $side, 'y' => $g->y + $ry * $side, 'z' => JET_Z + 2,
                'vx' => $fx * ($g->speed + 420), 'vy' => $fy * ($g->speed + 420), 'life' => 1.4];
        }
        $sfx('shoot', 0.12);
    }
    foreach ($g->bullets as $i => &$b) {
        [$b['x'], $b['y']] = [$b['x'] + $b['vx'] * $dt, $b['y'] + $b['vy'] * $dt];
        $b['life'] -= $dt;
        if ($b['life'] <= 0) unset($g->bullets[$i]);
    }
    unset($b);

    // Drones come from ahead and weave in.
    $g->spawn -= $dt;
    if ($g->spawn <= 0 && count($g->drones) < 6) {
        $g->spawn = mt_rand(12, 30) / 10;
        $a = $g->yaw + mt_rand(-40, 40) / 100;
        [$ax, $ay] = $ahead($a);
        $g->drones[] = ['x' => fmod($g->x + $ax * 650 + $world, $world), 'y' => fmod($g->y + $ay * 650 + $world, $world),
            'z' => JET_Z + mt_rand(-8, 8), 'yaw' => $a + M_PI, 'hp' => 3, 'phase' => mt_rand(0, 100) / 10];
    }
    foreach ($g->drones as $i => &$d) {
        $want = atan2($wrap($g->x - $d['x']), -$wrap($g->y - $d['y'])) + sin($g->time * 1.3 + $d['phase']) * 0.9;
        $d['yaw'] += atan2(sin($want - $d['yaw']), cos($want - $d['yaw'])) * min(1, $dt * 1.5);
        [$ax, $ay] = $ahead($d['yaw']);
        [$d['x'], $d['y']] = [fmod($d['x'] + $ax * 60 * $dt + $world, $world), fmod($d['y'] + $ay * 60 * $dt + $world, $world)];
        foreach ($g->bullets as $bi => $b) {
            if (hypot($wrap($b['x'] - $d['x']), $wrap($b['y'] - $d['y'])) < 14 && abs($b['z'] - $d['z']) < 12) {
                unset($g->bullets[$bi]);
                if (--$d['hp'] <= 0) { $boom($d['x'], $d['y'], $d['z'], 1); $g->score += 100; unset($g->drones[$i]); continue 2; }
            }
        }
        if (hypot($wrap($g->x - $d['x']), $wrap($g->y - $d['y'])) > 800) unset($g->drones[$i]);
    }
    unset($d);
    foreach ($g->particles as $i => &$p) {
        [$p['x'], $p['y'], $p['z']] = [$p['x'] + $p['vx'] * $dt, $p['y'] + $p['vy'] * $dt, max(0, $p['z'] + $p['vz'] * $dt)];
        $p['vz'] -= $p['fall'] * $dt;
        $p['life'] -= $dt;
        if ($p['life'] <= 0) unset($g->particles[$i]);
    }
    unset($p);
    // Contrails off the wing tips.
    $g->trail -= $dt;
    if ($g->trail <= 0) {
        $g->trail = 0.025;
        foreach ([-12, 12] as $n => $side) {
            array_unshift($g->trails[$n], [$g->x + $rx * $side - $fx * 4, $g->y + $ry * $side - $fy * 4]);
            $g->trails[$n] = array_slice($g->trails[$n], 0, 16);
        }
    }
};

// Drawing ---------------------------------------------------------------------------

$draw = function (Canvas $c) use ($g, $voxels, $places, $groundFile, $whiteFile, $ahead, $world) {
    $c->imageSmoothingEnabled = false;
    [$cw, $ch] = [$c->width, $c->height];
    // The camera (PhpUi\Camera3D): behind and above the jet, swinging round after it as
    // it turns; or in the cockpit, looking where the jet goes.
    $cockpit = $g->camera === 'cockpit';
    if ($cockpit) {
        [$fx, $fy] = $ahead($g->yaw);
        $cam = new Camera3D($g->x + $fx * 8, $g->y + $fy * 8, JET_Z + 6, $g->yaw, 0.08, $ch * 0.9, $cw / 2, $ch * 0.42, $world, -$g->turn * 0.4);
    } else {
        [$fx, $fy] = $ahead($g->view);
        $cam = new Camera3D($g->x - $fx * CHASE, $g->y - $fy * CHASE, JET_Z + CAM_UP, $g->view, 0, $ch * 0.8, $cw / 2, round($ch * 0.34), $world, -$g->turn * 0.06);
    }
    $horizon = $cam->horizon();

    // Sky, then the ground under it fading into the haze at the horizon.
    $sky = $c->createLinearGradient(0, 0, 0, $horizon);
    $sky->addColorStop(0, '#3f86d6');
    $sky->addColorStop(0.7, '#8cc3ec');
    $sky->addColorStop(1, '#d4ebf7');
    $c->fillStyle = $sky;
    $c->fillRect(0, 0, $cw, $ch);
    $cam->ground($c, $groundFile, $world, 2, '#d4ebf7', FOG);

    // Everything that stands or flies, far to near.
    $things = [];
    $add = function (array $thing, float $x, float $y, float $z) use ($cam, &$things) {
        $p = $cam->project($x, $y, $z);
        if ($p && $p[3] < FOG) $things[] = [$p[3], $p, ...$thing];
    };
    foreach ($places as $p) $add(['model', $p['kind'], $p['x'], $p['y'], $p['z'], $p['yaw'], 0], $p['x'], $p['y'], $p['z']);
    foreach ($g->drones as $d) $add(['model', 'drone', $d['x'], $d['y'], $d['z'], $d['yaw'] + $g->time * 3, 0], $d['x'], $d['y'], $d['z']);
    if (!$cockpit) $add(['model', 'jet', $g->x, $g->y, JET_Z, $g->yaw, $g->turn * 0.6], $g->x, $g->y, JET_Z);
    foreach ($g->bullets as $b) $add(['bullet'], $b['x'], $b['y'], $b['z']);
    foreach ($g->particles as $q) $add(['spark', $q], $q['x'], $q['y'], $q['z']);
    usort($things, fn ($a, $b) => $b[0] <=> $a[0]);

    foreach ($things as $t) {
        [$depth, [$sx, $sy, $s], $kind] = $t;
        // Far things fade into the haze, as the ground does.
        $alpha = 1 - ($depth / FOG) ** 2;
        if ($kind === 'bullet') {
            if ($depth < 25) continue;
            $c->globalAlpha = $alpha;
            $c->fillStyle = '#fff6c4';
            $z = min(8, max(2, $s * 1.5));
            $c->fillRect($sx - $z / 2, $sy - $z, $z, $z * 2);
            continue;
        }
        if ($kind === 'spark') {
            $q = $t[3];
            $c->globalAlpha = $alpha * min(1, $q['life'] * 3);
            $c->fillStyle = $q['color'];
            $z = min(12, max(1, $s * $q['size']));
            $c->fillRect($sx - $z / 2, $sy - $z / 2, $z, $z);
            continue;
        }
        [, , , $name, $x, $y, $z, $yaw, $roll] = $t;
        if (str_starts_with($name, 'cloud')) {
            // Clouds thin out when near or right overhead, so they never fill the view.
            $steep = abs($cam->z - $z) / $depth;
            $alpha *= 0.92 * min(1, ($depth - 60) / 100) * max(0, min(1, (1.1 - $steep) / 0.5));
        }
        if ($alpha > 0.02) $cam->stack($c, $voxels, $name, $x, $y, $z, $yaw, $alpha, $roll, $name === 'jet' ? 14 : 8);
    }

    // Contrails, from the wing tips back past the camera, thinning out: each piece a
    // white pixel stretched and turned (on the GPU, unlike a stroked line).
    foreach ($g->trails as $trail) {
        $last = null;
        foreach ($trail as $i => [$x, $y]) {
            $p = $cam->project($x, $y, JET_Z + 2);
            if ($p && $last && $i > 0) {
                [$dx, $dy] = [$p[0] - $last[0], $p[1] - $last[1]];
                $long = hypot($dx, $dy);
                $wide = min(6, max(1, $p[2] * 0.9));
                if ($long > 0) {
                    $c->globalAlpha = 0.55 * (1 - $i / count($trail));
                    $c->setTransform($dx, $dy, -$dy / $long * $wide, $dx / $long * $wide, $last[0], $last[1]);
                    $c->drawImage($whiteFile, 0, -0.5, 1, 1);
                }
            }
            $last = $p;
        }
    }
    $c->setTransform(1, 0, 0, 1, 0, 0);
    $c->globalAlpha = 1;

    if ($cockpit) {
        // The gun sight.
        [$mx, $my] = [$cw / 2, $ch * 0.42];
        $c->strokeStyle = 'rgba(140, 255, 170, 0.9)';
        $c->lineWidth = 2;
        $c->beginPath();
        $c->arc($mx, $my, 18, 0, 2 * M_PI);
        foreach ([[-34, 0, -22, 0], [22, 0, 34, 0], [0, -34, 0, -22], [0, 22, 0, 34]] as [$a, $b, $e, $f]) {
            $c->moveTo($mx + $a, $my + $b);
            $c->lineTo($mx + $e, $my + $f);
        }
        $c->stroke();
    }

    // HUD.
    $c->font = '11px mono-semibold';
    $c->letterSpacing = '1.5px';
    $c->textBaseline = 'middle';
    foreach ([[sprintf('SCORE %05d', $g->score), 14, 'left'], [sprintf('SPD %03d  ALT %03d', $g->speed * 2, JET_Z * 10), $cw - 14, 'right']] as [$text, $x, $align]) {
        $w = $c->measureText($text)->width + 16;
        $left = $align === 'right' ? $x - $w : $x;
        $c->fillStyle = 'rgba(10, 18, 32, 0.6)';
        $c->beginPath();
        $c->roundRect($left, 14, $w, 22, 4);
        $c->fill();
        $c->fillStyle = '#eef4fb';
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

$app = new App('Island Hop', 960, 600, fn () => col()->full()->bg('#3f86d6')->schema([canvas($draw)->flex1()->id('game')]));
$app->icon("$dir/icon.png");
$last = microtime(true);
$app->every(1 / 120, function () use ($update, &$last) {
    $now = microtime(true);
    $update(min(0.05, $now - $last));
    $last = $now;
});
$app->onKey(function (int $key) use ($g) {
    if ($key === Key::ESCAPE) $g->paused = !$g->paused;
    // V: the chase camera, or the cockpit.
    if ($key === Key::V) $g->camera = $g->camera === 'chase' ? 'cockpit' : 'chase';
});

if ($shots = getenv('PHPUI_SCREENSHOTS')) {
    @mkdir($shots, 0777, true);
    $g->autopilot = true;
    mt_srand(3);
    for ($i = 0; $i < 330; $i++) $update(1 / 60);
    $app->screenshot("$shots/islandhop.png", 960, 600, 1);
    $g->camera = 'cockpit';
    $app->screenshot("$shots/islandhop-cockpit.png", 960, 600, 1);
    exit;
}
if (!getenv('PHPUI_NO_RUN')) $app->run();
