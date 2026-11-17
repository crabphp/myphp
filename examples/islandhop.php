<?php

declare(strict_types=1);

require __DIR__ . '/../src/autoload.php';

use PhpSdl\Renderer;
use PhpSdl\Sound;
use PhpUi\{App, Camera3D, Canvas, Key, Voxels};
use function PhpUi\{canvas, col, dataDir, state};

// Island Hop: a flight game in 3D over islands with real hills. The land is a height
// map ray marched on the GPU ("voxel space": Camera3D::terrain); the jet, drones, trees,
// houses and clouds are voxel models drawn as stacked slices ("sprite stacking": Voxels
// and Camera3D::stack), hidden behind hills by the depth buffer. Art, music and sound
// are made by this file.
// A/D or ←/→ bank and turn, W/S or ↑/↓ climb and dive, Shift for speed, Space shoots,
// V switches between the chase camera and the cockpit, M mutes, Esc pauses.

defined('MAP') || define('MAP', 512);        // height map texels; the world wraps every MAP * TEXEL units
defined('TEXEL') || define('TEXEL', 4);      // world units a texel
defined('PEAK') || define('PEAK', 230);      // the highest ground, in world units
defined('FOG') || define('FOG', 1900);       // how far one sees

$dir = dataDir('myphp-islandhop') . '/v4';
@mkdir($dir, 0777, true);
$world = MAP * TEXEL;

// The land ---------------------------------------------------------------------------

/** Value noise, wrapping both ways, in texels. */
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

// Heights, 0 (the sea) to 1 (the peaks), and the shallows' depth under the sea.
// Worked out once, then read back from a file.
$heights = [];
$depths = [];
$heightFile = "$dir/heights.bin";
if (is_file($heightFile)) $heights = array_values(unpack('g*', file_get_contents($heightFile)));
$octaves = [[$noise(41, 128), 0.5], [$noise(43, 64), 0.25], [$noise(47, 16), 0.15], [$noise(53, 4), 0.1]];
if (!$heights || !is_file("$dir/terrain.png")) for ($y = 0; $y < MAP; $y++) for ($x = 0; $x < MAP; $x++) {
    $h = 0.0;
    $smooth = 0.0;
    foreach ($octaves as $o => [$n, $w]) {
        $v = $n($x, $y) * $w;
        $h += $v;
        if ($o < 2) $smooth += $v;
    }
    $land = ($h - 0.53) / 0.47;
    $heights[$y * MAP + $x] = $land > 0 ? min(1, $land * 1.25) ** 1.5 : 0.0;
    // Under the sea, depth follows the broad shapes only: no speckle.
    $depths[$y * MAP + $x] = $land > 0 ? [0.0, 0.0] : [min(1, -$land * 5), min(1, max(0, (0.53 * 0.75 - $smooth) / 0.75 / 0.47 * 5))];
}
if (!is_file($heightFile)) file_put_contents($heightFile, pack('g*', ...$heights));
/** The ground's height in world units at world (x, y), between texels. */
$groundAt = function (float $x, float $y) use ($heights): float {
    [$fx, $fy] = [$x / TEXEL - 0.5, $y / TEXEL - 0.5];
    [$ix, $iy] = [(int) floor($fx), (int) floor($fy)];
    [$tx, $ty] = [$fx - $ix, $fy - $iy];
    $at = fn ($a, $b) => $heights[(($b % MAP + MAP) % MAP) * MAP + ($a % MAP + MAP) % MAP];
    $top = $at($ix, $iy) + ($at($ix + 1, $iy) - $at($ix, $iy)) * $tx;
    $bottom = $at($ix, $iy + 1) + ($at($ix + 1, $iy + 1) - $at($ix, $iy + 1)) * $tx;
    return ($top + ($bottom - $top) * $ty) * PEAK;
};

// The map image: colors lit by the sun (from the north-west), heights in alpha.
$terrainFile = "$dir/terrain.png";
if (!is_file($terrainFile)) {
    $mix = fn (array $a, array $b, float $t) => [$a[0] + ($b[0] - $a[0]) * $t, $a[1] + ($b[1] - $a[1]) * $t, $a[2] + ($b[2] - $a[2]) * $t];
    $rgb = fn (string $c) => [hexdec(substr($c, 1, 2)), hexdec(substr($c, 3, 2)), hexdec(substr($c, 5, 2))];
    [$deep, $sea, $shallow, $foam] = [$rgb('#123f78'), $rgb('#1f66ad'), $rgb('#3aa6d6'), $rgb('#e8f6fb')];
    [$sand, $grass, $wood, $rock, $snow] = [$rgb('#e6d39a'), $rgb('#74bf54'), $rgb('#3f8f3c'), $rgb('#8a8174'), $rgb('#f3f6fa')];
    $sun = [-0.45, -0.55, 0.7];
    $blur = $noise(59, 4);
    mt_srand(9);
    $px = '';
    for ($y = 0; $y < MAP; $y++) {
        for ($x = 0; $x < MAP; $x++) {
            $i = $y * MAP + $x;
            $e = $heights[$i];
            if ($e <= 0) {
                // Surf right at the shore; under it, shallows to deep sea by the broad shapes.
                [$shore, $d] = $depths[$i];
                $d = max($d, $shore);
                $c = $d < 0.4 ? $mix($shallow, $sea, $d / 0.4) : $mix($sea, $deep, min(1, ($d - 0.4) / 0.6));
                if ($shore < 0.04) $c = $mix($foam, $c, $shore / 0.04);
                $px .= pack('C4', (int) $c[0], (int) $c[1], (int) $c[2], 0);
                continue;
            }
            // The slope, in world units, and how it faces the sun.
            $hx = ($heights[$y * MAP + ($x + 1) % MAP] - $heights[$y * MAP + ($x + MAP - 1) % MAP]) * PEAK / (2 * TEXEL);
            $hy = ($heights[(($y + 1) % MAP) * MAP + $x] - $heights[(($y + MAP - 1) % MAP) * MAP + $x]) * PEAK / (2 * TEXEL);
            $len = sqrt($hx * $hx + $hy * $hy + 1);
            $light = (-$hx * $sun[0] - $hy * $sun[1] + $sun[2]) / $len;
            $steep = 1 - 1 / $len;
            $jitter = $blur($x * 3, $y * 3) * 0.06;
            $c = match (true) {
                $e < 0.025 => $sand,
                $e < 0.22 => $mix($grass, $wood, min(1, ($e - 0.025) / 0.2 + $jitter)),
                $e < 0.5 => $mix($wood, $rock, min(1, ($e - 0.22) / 0.2 + $steep * 0.8)),
                $e < 0.68 => $mix($rock, $snow, max(0, min(1, ($e - 0.6) / 0.08 - $steep))),
                default => $mix($snow, $rock, min(1, $steep * 1.6)),
            };
            if ($e > 0.025 && $e < 0.4 && $steep > 0.45) $c = $mix($c, $rock, 0.6);
            $k = (0.55 + 0.6 * max(0, $light)) * (1 - $jitter);
            $px .= pack('C4', min(255, (int) ($c[0] * $k)), min(255, (int) ($c[1] * $k)), min(255, (int) ($c[2] * $k)), max(1, (int) round($e * 255)));
        }
    }
    Renderer::savePng($terrainFile, MAP, MAP, $px);
}

// The models ---------------------------------------------------------------------------

$near = fn ($x, $y, $z, $cx, $cy, $cz, $r) => ($x - $cx) ** 2 + ($y - $cy) ** 2 + ($z - $cz) ** 2 < $r * $r;

/**
 * The jet, our own: a white twin-tail fighter with blue bands, drawn as shapes in its
 * own units (25 wide, 30 long, nose at the top) and sampled at twice that.
 */
$jet = function (float $x, float $y, float $z): ?string {
    $dx = abs($x - 12.5);
    // The fuselage: round in section, a sharp nose, narrowing to the engines.
    $hw = $y < 7 ? 0.25 + ($y - 0.5) * 0.3 : ($y < 25 ? 2.2 : 2.2 - ($y - 25) * 0.2);
    if ($y >= 0.5 && $y <= 29.5 && ($dx / $hw) ** 2 + (($z - 2.2) / ($y < 7 ? 0.5 + $y * 0.14 : 1.4)) ** 2 <= 1) {
        if ($z < 1.5) return '#8c97a8';
        if ($dx < 0.45 && $y > 13 && $z > 3.2) return '#2d6cdf';
        if ($y < 1.6) return '#c8d0dc';
        return '#eef2f7';
    }
    // The canopy, glass with a glint.
    if (($dx / 1.3) ** 2 + (($y - 9.5) / 3.6) ** 2 + (($z - 3.6) / 1.6) ** 2 <= 1) return $y < 8 && $z > 4.4 ? '#cdeeff' : '#203050';
    // Air intakes beside the cockpit.
    if ($dx >= 2.0 && $dx <= 2.9 && $y >= 11 && $y <= 15 && $z >= 1.3 && $z <= 2.7) return $y < 11.8 ? '#1a1f2b' : '#d0d7e2';
    // Wings: swept, thin, blue toward the trailing edge, yellow tips.
    if ($z >= 1.7 && $z <= 2.5 && $y >= 11 && $y <= 21.5 && $dx >= 2 && $dx <= min(12.4, ($y - 9.6) * 1.3)) {
        if ($dx >= 11.2) return '#ffc93a';
        return $y >= 18.5 && $dx >= 4.5 ? '#2d6cdf' : '#e2e8f0';
    }
    // Tailplanes.
    if ($z >= 1.7 && $z <= 2.4 && $y >= 24 && $y <= 28.5 && $dx >= 2 && $dx <= min(7.2, 2 + ($y - 23.4) * 1.4)) return '#d6dde8';
    // Twin fins, leaning out.
    $fin = 2.9 + ($z - 3) * 0.12;
    if (abs($dx - $fin) <= 0.4 && $z >= 3 && $z <= 8.6 && $y >= 20.5 + ($z - 3) * 0.9 && $y <= 27.8) return $z >= 7.4 ? '#2d6cdf' : '#e2e8f0';
    // Engines: dark rings round a hot core.
    if ($y >= 28.6 && $y <= 30 && $dx >= 0.5 && $dx <= 2.2 && $z >= 1.2 && $z <= 3.0) {
        return hypot($dx - 1.35, $z - 2.1) < 0.5 ? '#ffb347' : '#3a3d46';
    }
    return null;
};
mt_srand(5);
$puffs = [];
foreach (['cloud' => [28, 20], 'cloud2' => [22, 18]] as $name => [$w, $d]) {
    for ($i = 0; $i < 8; $i++) $puffs[$name][] = [mt_rand(6, $w - 7), mt_rand(5, $d - 6), mt_rand(0, 2), mt_rand(35, 62) / 10];
}
$cloud = fn (string $name) => function ($x, $y, $z) use ($puffs, $near, $name) {
    [$x, $y, $z] = [($x + 0.5) / 2, ($y + 0.5) / 2, ($z + 0.5) / 2];
    foreach ($puffs[$name] as [$px, $py, $pz, $r]) if ($near($x, $y, $z * 1.4, $px, $py, $pz, $r)) return $z >= 4 ? '#ffffff' : '#eef3f9';
    return null;
};
$voxels = new Voxels("$dir/models.png", [
    'jet' => ['size' => [50, 60, 18], 'unit' => 0.5, 'voxel' => fn ($x, $y, $z) => $jet(($x + 0.5) / 2, ($y + 0.5) / 2, ($z + 0.5) / 2)],
    'drone' => ['size' => [26, 26, 12], 'unit' => 0.8, 'voxel' => function ($x, $y, $z) use ($near) {
        [$x, $y, $z] = [($x + 0.5) / 2, ($y + 0.5) / 2, ($z + 0.5) / 2];
        $r = hypot($x - 6.5, $y - 6.5);
        if ($z <= 1.6 && $r <= 6.4 && $r >= 4.8) return abs(fmod(atan2($y - 6.5, $x - 6.5) + 7, M_PI / 2) - M_PI / 4) < 0.25 ? '#ffd23f' : '#c8303e';
        if ($z <= 2.6 && $r < 4.8) return $z > 2 ? '#ff6b6b' : '#8f1f2c';
        if ($near($x, $y, $z, 6.5, 6.5, 2.8, 2.7)) return $near($x, $y, $z, 6.5, 4.7, 3.6, 1.2) ? '#ffd23f' : '#2b1a22';
        return null;
    }],
    'oak' => ['size' => [11, 11, 13], 'unit' => 1.6, 'voxel' => function ($x, $y, $z) use ($near) {
        if ($z <= 4 && $x === 5 && abs($y - 5) <= 1) return '#6b4a2b';
        if ($near($x, $y, $z, 5, 5, 8, 4.4) && mt_rand(0, 12)) return ['#2f7a34', '#3d9440', '#56b04a', '#6cc25a'][min(3, intdiv($z - 4, 2))];
        return null;
    }],
    'pine' => ['size' => [9, 9, 16], 'unit' => 1.6, 'voxel' => function ($x, $y, $z) {
        if ($z <= 3 && $x === 4 && $y === 4) return '#5a3d22';
        if ($z >= 3 && hypot($x - 4, $y - 4) <= (16 - $z) * 0.36) return $z % 3 === 0 ? '#1f5c34' : '#2c7a45';
        return null;
    }],
    'house' => ['size' => [14, 12, 12], 'unit' => 1.5, 'shade' => 0.6, 'voxel' => function ($x, $y, $z) {
        if ($z <= 5 && $x >= 1 && $x <= 12 && $y >= 1 && $y <= 10) {
            $edge = $x === 1 || $x === 12 || $y === 1 || $y === 10;
            if ($edge && $z >= 2 && $z <= 3 && ($x % 3 === 0 || $y % 3 === 0)) return '#3b6ea8';
            if ($edge && $y === 10 && $x >= 6 && $x <= 7 && $z <= 3) return '#7a4a28';
            return $edge ? '#efe3c8' : '#d8c8a8';
        }
        if ($z >= 6 && abs($y - 5.5) <= 6.5 - ($z - 6) * 1.3) return $z === 11 ? '#8f2f24' : '#c4473a';
        if ($z >= 6 && $z <= 9 && $x === 10 && $y === 3) return '#7d7d7d';
        return null;
    }],
    'lighthouse' => ['size' => [9, 9, 28], 'unit' => 1.5, 'shade' => 0.6, 'voxel' => function ($x, $y, $z) {
        $r = hypot($x - 4, $y - 4);
        if ($z <= 1 && $r <= 4.4) return '#9a9a92';
        if ($z >= 2 && $z <= 21 && $r <= 3.2 - $z * 0.05) return intdiv($z - 2, 4) % 2 ? '#f2f2ee' : '#d63c33';
        if ($z >= 22 && $z <= 24 && $r <= 2.4) return $r <= 1.5 ? '#ffe680' : '#2a2a30';
        if ($z >= 25 && $r <= 2.6 - ($z - 25)) return '#d63c33';
        return null;
    }],
    'cloud' => ['size' => [56, 40, 16], 'unit' => 2, 'shade' => 0.93, 'lit' => false, 'voxel' => $cloud('cloud')],
    'cloud2' => ['size' => [44, 36, 14], 'unit' => 2, 'shade' => 0.93, 'lit' => false, 'voxel' => $cloud('cloud2')],
]);
if ($voxels->built) Renderer::savePng("$dir/icon.png", 50, 60, $voxels->layer('jet', 4));
$whiteFile = "$dir/white.png";
if (!is_file($whiteFile)) Renderer::savePng($whiteFile, 1, 1, "\xff\xff\xff\xff");
// The sky and the sun's glow as small images the GPU stretches: no gradient drawn each frame.
$skyFile = "$dir/sky.png";
if (!is_file($skyFile)) {
    $px = '';
    foreach (range(0, 255) as $i) {
        $t = $i / 255;
        [$a, $b, $k] = $t < 0.65 ? [[47, 111, 196], [127, 184, 232], $t / 0.65] : [[127, 184, 232], [214, 235, 246], ($t - 0.65) / 0.35];
        $px .= pack('C4', (int) ($a[0] + ($b[0] - $a[0]) * $k), (int) ($a[1] + ($b[1] - $a[1]) * $k), (int) ($a[2] + ($b[2] - $a[2]) * $k), 255);
    }
    Renderer::savePng($skyFile, 1, 256, $px);
}
$glowFile = "$dir/glow.png";
if (!is_file($glowFile)) {
    $px = '';
    for ($y = 0; $y < 128; $y++) for ($x = 0; $x < 128; $x++) {
        $r = hypot($x - 63.5, $y - 63.5) / 64;
        $a = $r >= 1 ? 0 : (1 - $r) ** 3 * 0.7 + ($r < 0.08 ? 1 - $r / 0.08 : 0) * 0.3;
        $px .= pack('C4', 255, 246, 214, (int) (min(1, $a) * 255));
    }
    Renderer::savePng($glowFile, 128, 128, $px);
}

// Woods and houses on gentle ground, a lighthouse on the shore; clouds over it all.
$places = [];
$taken = [];
mt_srand(77);
for ($i = 0; $i < 30000 && count($places) < 900; $i++) {
    [$x, $y] = [mt_rand(0, MAP - 1), mt_rand(0, MAP - 1)];
    $e = $heights[$y * MAP + $x];
    if ($e < 0.012 || $e > 0.3) continue;
    $slope = abs($heights[$y * MAP + ($x + 1) % MAP] - $e) + abs($heights[(($y + 1) % MAP) * MAP + $x] - $e);
    if ($slope * PEAK / TEXEL > 0.9) continue;
    $kind = $e > 0.12 ? (mt_rand(0, 3) ? 'pine' : 'oak') : ['oak', 'oak', 'pine', 'house'][mt_rand(0, 3)];
    $key = intdiv($x, 3) . ',' . intdiv($y, 3);
    if (isset($taken[$key])) continue;
    $taken[$key] = true;
    [$wx, $wy] = [($x + 0.5) * TEXEL, ($y + 0.5) * TEXEL];
    $places[] = ['kind' => $kind, 'x' => $wx, 'y' => $wy, 'z' => $groundAt($wx, $wy) - 1, 'yaw' => mt_rand(0, 3) * M_PI / 2];
}
for ($i = 0; $i < 20000; $i++) {
    [$x, $y] = [mt_rand(0, MAP - 1), mt_rand(0, MAP - 1)];
    $e = $heights[$y * MAP + $x];
    if ($e > 0 && $e < 0.01) {
        [$wx, $wy] = [($x + 0.5) * TEXEL, ($y + 0.5) * TEXEL];
        $places[] = ['kind' => 'lighthouse', 'x' => $wx, 'y' => $wy, 'z' => $groundAt($wx, $wy) - 1, 'yaw' => 0];
        break;
    }
}
for ($i = 0; $i < 60; $i++) {
    $places[] = ['kind' => mt_rand(0, 1) ? 'cloud' : 'cloud2', 'x' => mt_rand(0, $world - 1), 'y' => mt_rand(0, $world - 1),
        'z' => mt_rand(260, 420), 'yaw' => mt_rand(0, 628) / 100];
}
unset($taken);

// Sound --------------------------------------------------------------------------------

$wav = function (string $name, float $seconds, callable $sample) use ($dir): string {
    $file = "$dir/$name.wav";
    if (is_file($file)) return $file;
    [$rate, $data] = [22050, ''];
    for ($i = 0, $n = (int) ($seconds * $rate); $i < $n; $i++) $data .= pack('v', (int) (max(-1, min(1, $sample($i / $rate, $i / $n))) * 26000) & 0xffff);
    file_put_contents($file, 'RIFF' . pack('V', 36 + strlen($data)) . 'WAVEfmt ' . pack('VvvVVvv', 16, 1, 1, $rate, $rate * 2, 2, 16) . 'data' . pack('V', strlen($data)) . $data);
    return $file;
};
mt_srand();
$square = fn (float $t, float $f) => fmod($t * $f, 1) < 0.5 ? 1 : -1;
$sounds = [
    'shoot' => $wav('shoot', 0.06, fn ($t, $p) => $square($t, 1300 - 700 * $p) * (1 - $p) * 0.3),
    'boom' => $wav('boom', 0.8, function ($t, $p) { static $v = 0.0; $v += (mt_rand(-1000, 1000) / 1000 - $v) * (0.5 - 0.45 * $p); return $v * (1 - $p) ** 2 * 1.6; }),
    'hit' => $wav('hit', 0.25, fn ($t, $p) => $square($t, 150 - 70 * $p) * (1 - $p) * 0.6),
    'zap' => $wav('zap', 0.18, fn ($t, $p) => sin($t * 2 * M_PI * (900 - 600 * $p)) * (1 - $p) * 0.35),
    'start' => $wav('start', 0.5, fn ($t, $p) => $square($t, [523, 659, 784, 1047][min(3, (int) ($p * 4))]) * 0.22 * (1 - $p * 0.5)),
    // The engine: a low hum with a rough edge, looping.
    'engine' => $wav('engine', 2.0, function ($t) { static $v = 0.0; $v += (mt_rand(-1000, 1000) / 1000 - $v) * 0.08;
        return (sin($t * 2 * M_PI * 55) * 0.35 + sin($t * 2 * M_PI * 110) * 0.15 + $v * 0.5) * 0.5; }),
    // Music: a calm loop of four chords, a soft pad under a plucked arpeggio.
    'music' => $wav('music', 16.0, function ($t) {
        $chords = [[220, 261.6, 329.6], [174.6, 220, 261.6], [196, 246.9, 293.7], [164.8, 207.7, 246.9]];
        $chord = $chords[(int) ($t / 4) % 4];
        $pad = 0.0;
        foreach ($chord as $f) $pad += sin($t * 2 * M_PI * $f) * 0.06 + sin($t * 2 * M_PI * $f * 2.003) * 0.02;
        $step = (int) ($t * 4);
        $since = $t * 4 - $step;
        $note = $chord[[0, 1, 2, 1][$step % 4]] * 2;
        $pluck = sin($t * 2 * M_PI * $note) * exp(-$since * 6) * 0.18;
        return $pad + $pluck;
    }),
];
$audio = new class {
    public bool $on = false, $muted = false;
    public array $playing = [];
    public ?Sound $engine = null, $music = null;
};
$audio->on = Sound::available();
$sfx = function (string $name, float $volume) use ($audio, $sounds) {
    if (!$audio->on || $audio->muted) return;
    $audio->playing = array_values(array_filter($audio->playing, fn (Sound $s) => $s->playing()));
    if (count($audio->playing) > 12) return;
    $s = new Sound($sounds[$name]);
    $s->volume($volume);
    $s->play();
    $audio->playing[] = $s;
};
if ($audio->on && !getenv('PHPUI_SCREENSHOTS') && !getenv('PHPUI_NO_RUN')) {
    foreach (['engine' => 0, 'music' => 0.35] as $name => $volume) {
        $audio->$name = new Sound($sounds[$name]);
        $audio->$name->loop();
        $audio->$name->volume($volume);
        $audio->$name->play();
    }
}

// Game ----------------------------------------------------------------------------------

$best = (int) @file_get_contents(dataDir('myphp-islandhop') . '/best');
$g = state(new class {
    public string $mode = 'title', $camera = 'chase';
    public float $x = 0, $y = 0, $z = 150, $yaw = 0, $view = 0, $speed = 150, $turn = 0, $climb = 0, $time = 0, $clock = 0;
    public float $gun = 0, $spawn = 3, $trail = 0, $hurt = 0, $shake = 0;
    public int $score = 0, $best = 0, $hp = 5, $kills = 0;
    public bool $paused = false, $autopilot = false;
    public array $drones = [], $bullets = [], $orbs = [], $particles = [], $trails = [[], []];
});
$g->best = max($g->best, $best);
$home = current(array_filter($places, fn ($p) => $p['kind'] === 'lighthouse')) ?: ['x' => $world / 2, 'y' => $world / 2];

$wrap = fn (float $d) => $d - $world * round($d / $world);
$ahead = fn (float $yaw) => [sin($yaw), -cos($yaw)];

$reset = function () use ($g, $home, $sfx) {
    [$g->x, $g->y, $g->z, $g->yaw, $g->view, $g->speed, $g->turn, $g->climb] = [$home['x'], $home['y'] + 500, 150, 0, 0, 150, 0, 0];
    [$g->hp, $g->score, $g->kills, $g->spawn, $g->hurt, $g->time] = [5, 0, 0, 3, 2, 0];
    [$g->drones, $g->bullets, $g->orbs, $g->particles, $g->trails] = [[], [], [], [], [[], []]];
    [$g->mode, $g->paused] = ['play', false];
    $sfx('start', 0.4);
};
if ($g->clock == 0) [$g->x, $g->y] = [$home['x'], $home['y'] + 500];

$boom = function (float $x, float $y, float $z, float $size) use ($g, $sfx) {
    for ($i = 0; $i < 34 * $size; $i++) {
        [$a, $e] = [mt_rand(0, 628) / 100, mt_rand(-150, 150) / 100];
        $v = mt_rand(20, 90) * $size;
        $g->particles[] = ['x' => $x, 'y' => $y, 'z' => $z, 'vx' => cos($a) * cos($e) * $v, 'vy' => sin($a) * cos($e) * $v, 'vz' => sin($e) * $v,
            'life' => mt_rand(50, 120) / 100, 'size' => 1.8, 'fall' => 40, 'color' => ['#ffffff', '#ffe066', '#ffb02e', '#ff6b2c', '#5a5560'][mt_rand(0, 4)]];
    }
    $sfx('boom', min(0.9, 0.45 * $size));
};

$damage = function (int $amount) use ($g, $boom, $sfx) {
    if ($g->hurt > 0 || $g->mode !== 'play') return;
    $g->hp -= $amount;
    [$g->hurt, $g->shake] = [1.2, 6];
    $sfx('hit', 0.7);
    if ($g->hp <= 0) {
        $boom($g->x, $g->y, $g->z, 2.5);
        [$g->mode, $g->time] = ['over', 0];
        if ($g->score > $g->best) {
            $g->best = $g->score;
            file_put_contents(dataDir('myphp-islandhop') . '/best', (string) $g->best);
        }
    }
};

$update = function (float $dt) use ($g, $wrap, $ahead, $boom, $sfx, $world, $groundAt, $reset, $damage, $audio) {
    $g->clock += $dt;
    $app = App::current();
    $start = $app->isKeyDown(Key::SPACE) || $app->isKeyDown(Key::ENTER);
    if ($g->mode !== 'play') {
        $g->time += $dt;
        // The title flies itself, gently.
        if ($g->mode === 'title' || $g->time < 3) $g->autopilot = true;
        if ($start && $g->time > 0.8) { $g->autopilot = false; $reset(); }
    }
    if ($g->paused) return;
    $g->time += $g->mode === 'play' ? $dt : 0;
    $g->hurt = max(0, $g->hurt - $dt);
    $g->shake = max(0, $g->shake - 12 * $dt);
    $alive = $g->mode !== 'over';

    // Steering: bank to turn, climb and dive; the ground below pushes back.
    $left = $app->isKeyDown(Key::LEFT) || $app->isKeyDown(Key::A);
    $right = $app->isKeyDown(Key::RIGHT) || $app->isKeyDown(Key::D);
    $up = $app->isKeyDown(Key::UP) || $app->isKeyDown(Key::W);
    $down = $app->isKeyDown(Key::DOWN) || $app->isKeyDown(Key::S);
    $fast = $app->isKeyDown(Key::LEFT_SHIFT) || $app->isKeyDown(Key::RIGHT_SHIFT);
    [$fx, $fy] = $ahead($g->yaw);
    if ($g->autopilot) {
        // Weave, and keep well over what lies ahead.
        $look = max($groundAt($g->x + $fx * 120, $g->y + $fy * 120), $groundAt($g->x + $fx * 260, $g->y + $fy * 260));
        [$left, $right] = [false, fmod($g->clock, 7) < 2.6];
        [$up, $down] = [$g->z < $look + 70, $g->z > $look + 120];
        $fast = false;
    }
    if (!$alive) [$left, $right, $up, $down] = [false, false, false, true];
    $g->turn += ((($right ? 1 : 0) - ($left ? 1 : 0)) - $g->turn) * min(1, $dt * 4);
    $g->climb += ((($up ? 1 : 0) - ($down ? 1 : 0)) - $g->climb) * min(1, $dt * 4);
    $g->yaw += $g->turn * 1.15 * $dt;
    $g->view += atan2(sin($g->yaw - $g->view), cos($g->yaw - $g->view)) * min(1, $dt * 5);
    $g->speed += (($fast ? 240 : 150) - $g->speed) * min(1, $dt * 2);
    [$fx, $fy] = $ahead($g->yaw);
    [$g->x, $g->y] = [fmod($g->x + $fx * $g->speed * $dt + $world, $world), fmod($g->y + $fy * $g->speed * $dt + $world, $world)];
    $g->z = max(8, min(520, $g->z + $g->climb * 90 * $dt));
    $floor = $groundAt($g->x, $g->y);
    if ($g->z < $floor + 6) {
        // Scraping the hills: bounced up, and hurt.
        $g->z = $floor + 14;
        $g->climb = 1;
        $damage(2);
        $boom($g->x, $g->y, $floor + 4, 0.6);
    }
    if ($audio->engine) $audio->engine->volume($audio->muted || !$alive ? 0 : 0.18 + ($g->speed - 150) / 600);
    [$rx, $ry] = [-$fy, $fx];

    // Guns, under the wings.
    $g->gun -= $dt;
    if ($alive && $g->mode === 'play' && ($app->isKeyDown(Key::SPACE) || $g->autopilot) && $g->gun <= 0) {
        $g->gun = 0.09;
        foreach ([-5, 5] as $side) {
            $g->bullets[] = ['x' => $g->x + $rx * $side, 'y' => $g->y + $ry * $side, 'z' => $g->z + 1,
                'vx' => $fx * ($g->speed + 480), 'vy' => $fy * ($g->speed + 480), 'vz' => $g->climb * 90, 'life' => 1.3];
        }
        $sfx('shoot', 0.1);
    }
    foreach ($g->bullets as $i => &$b) {
        [$b['x'], $b['y'], $b['z']] = [$b['x'] + $b['vx'] * $dt, $b['y'] + $b['vy'] * $dt, $b['z'] + $b['vz'] * $dt];
        $b['life'] -= $dt;
        if ($b['life'] <= 0 || $b['z'] < $groundAt($b['x'], $b['y'])) unset($g->bullets[$i]);
    }
    unset($b);

    // Drones: they come from ahead, circle, and fire glowing orbs at the jet.
    $g->spawn -= $dt;
    if ($g->mode === 'play' && $g->spawn <= 0 && count($g->drones) < 3 + min(5, intdiv((int) $g->time, 30))) {
        $g->spawn = max(1.2, 3.5 - $g->time / 60);
        $a = $g->yaw + mt_rand(-50, 50) / 100;
        [$ax, $ay] = $ahead($a);
        [$dx, $dy] = [fmod($g->x + $ax * 900 + $world, $world), fmod($g->y + $ay * 900 + $world, $world)];
        $g->drones[] = ['x' => $dx, 'y' => $dy, 'z' => max($groundAt($dx, $dy) + 40, $g->z + mt_rand(-30, 40)),
            'yaw' => $a + M_PI, 'hp' => 3, 'phase' => mt_rand(0, 100) / 10, 'fire' => 2 + mt_rand(0, 200) / 100, 'flash' => 0];
    }
    foreach ($g->drones as $i => &$d) {
        [$ox, $oy] = [$wrap($g->x - $d['x']), $wrap($g->y - $d['y'])];
        $far = hypot($ox, $oy);
        $want = atan2($ox, -$oy) + sin($g->clock * 1.3 + $d['phase']) * 0.9;
        $d['yaw'] += atan2(sin($want - $d['yaw']), cos($want - $d['yaw'])) * min(1, $dt * 1.4);
        [$ax, $ay] = $ahead($d['yaw']);
        [$d['x'], $d['y']] = [fmod($d['x'] + $ax * 85 * $dt + $world, $world), fmod($d['y'] + $ay * 85 * $dt + $world, $world)];
        $d['z'] += (max($groundAt($d['x'], $d['y']) + 30, $g->z + sin($g->clock + $d['phase']) * 25) - $d['z']) * min(1, $dt * 0.8);
        $d['flash'] = max(0, $d['flash'] - $dt);
        $d['fire'] -= $dt;
        if ($alive && $d['fire'] <= 0 && $far < 700 && $far > 80) {
            $d['fire'] = 2.2 + mt_rand(0, 150) / 100;
            // Aim where the jet will be.
            $lead = $far / 260;
            [$tx, $ty, $tz] = [$ox + $fx * $g->speed * $lead, $oy + $fy * $g->speed * $lead, $g->z - $d['z']];
            $n = max(1, sqrt($tx * $tx + $ty * $ty + $tz * $tz));
            $g->orbs[] = ['x' => $d['x'], 'y' => $d['y'], 'z' => $d['z'], 'vx' => $tx / $n * 260, 'vy' => $ty / $n * 260, 'vz' => $tz / $n * 260, 'life' => 4];
            $sfx('zap', 0.2 * max(0.2, 1 - $far / 700));
        }
        foreach ($g->bullets as $bi => $b) {
            if (hypot($wrap($b['x'] - $d['x']), $wrap($b['y'] - $d['y'])) < 14 && abs($b['z'] - $d['z']) < 12) {
                unset($g->bullets[$bi]);
                $d['flash'] = 0.08;
                if (--$d['hp'] <= 0) { $boom($d['x'], $d['y'], $d['z'], 1.2); $g->score += 100; $g->kills++; unset($g->drones[$i]); continue 2; }
            }
        }
        if ($far > 1400) unset($g->drones[$i]);
    }
    unset($d);
    foreach ($g->orbs as $i => &$o) {
        [$o['x'], $o['y'], $o['z']] = [$o['x'] + $o['vx'] * $dt, $o['y'] + $o['vy'] * $dt, $o['z'] + $o['vz'] * $dt];
        $o['life'] -= $dt;
        if (hypot($wrap($o['x'] - $g->x), $wrap($o['y'] - $g->y)) < 10 && abs($o['z'] - $g->z) < 8) {
            unset($g->orbs[$i]);
            $damage(1);
            continue;
        }
        if ($o['life'] <= 0 || $o['z'] < $groundAt($o['x'], $o['y'])) unset($g->orbs[$i]);
    }
    unset($o);
    foreach ($g->particles as $i => &$p) {
        [$p['x'], $p['y'], $p['z']] = [$p['x'] + $p['vx'] * $dt, $p['y'] + $p['vy'] * $dt, $p['z'] + $p['vz'] * $dt];
        $p['vz'] -= $p['fall'] * $dt;
        $p['life'] -= $dt;
        if ($p['life'] <= 0) unset($g->particles[$i]);
    }
    unset($p);
    // Contrails off the wing tips; smoke when the hull is failing.
    $g->trail -= $dt;
    if ($g->trail <= 0 && $alive) {
        $g->trail = 0.025;
        foreach ([-12, 12] as $n => $side) {
            array_unshift($g->trails[$n], [$g->x + $rx * $side - $fx * 4, $g->y + $ry * $side - $fy * 4, $g->z + 1]);
            $g->trails[$n] = array_slice($g->trails[$n], 0, 18);
        }
        if ($g->hp <= 2 && $g->mode === 'play') {
            $g->particles[] = ['x' => $g->x - $fx * 14, 'y' => $g->y - $fy * 14, 'z' => $g->z + 2, 'vx' => 0, 'vy' => 0, 'vz' => 8,
                'fall' => 0, 'life' => 0.9, 'size' => 2.4, 'color' => '#4a4650'];
        }
    }
    if (!$alive) $g->trails = [[], []];
};

// Drawing -------------------------------------------------------------------------------

$draw = function (Canvas $c) use ($g, $voxels, $places, $terrainFile, $whiteFile, $skyFile, $glowFile, $ahead, $world, $groundAt) {
    $c->imageSmoothingEnabled = false;
    [$cw, $ch] = [$c->width, $c->height];
    $shake = $g->shake > 0 ? [mt_rand(-10, 10) / 10 * $g->shake, mt_rand(-10, 10) / 10 * $g->shake] : [0, 0];
    // The camera: behind and above the jet, swinging round after it, or in the cockpit.
    $cockpit = $g->camera === 'cockpit' && $g->mode !== 'over';
    if ($cockpit) {
        [$fx, $fy] = $ahead($g->yaw);
        $cam = new Camera3D($g->x + $fx * 8, $g->y + $fy * 8, $g->z + 4, $g->yaw, 0.06 - $g->climb * 0.1, $ch * 0.9,
            $cw / 2 + $shake[0], $ch * 0.45 + $shake[1], $world, -$g->turn * 0.4);
    } else {
        [$fx, $fy] = $ahead($g->view);
        $back = $g->mode === 'over' ? 90 : 46;
        [$camX, $camY] = [$g->x - $fx * $back, $g->y - $fy * $back];
        $camZ = max($g->z + 16 - $g->climb * 6, $groundAt($camX, $camY) + 10);
        $cam = new Camera3D($camX, $camY, $camZ, $g->view, 0.1 + $g->climb * 0.05, $ch * 0.85,
            $cw / 2 + $shake[0], $ch * 0.36 + $shake[1], $world, -$g->turn * 0.08);
    }

    // The sky: deep blue up high to haze at the horizon, and the sun.
    // It turns with the camera's roll; past its ends, plain color.
    $horizon = $cam->horizon();
    $c->fillStyle = '#d6ebf6';
    $c->fillRect(0, 0, $cw, $ch);
    $c->save();
    $c->translate($cw / 2, $horizon);
    $c->rotate($cam->roll);
    $c->imageSmoothingEnabled = true;
    $c->drawImage($skyFile, 0, 0, 1, 256, -$cw, -$ch * 1.6, $cw * 2, $ch * 1.6);
    $c->drawImage($skyFile, 0, 0, 1, 1, -$cw, -$ch * 4, $cw * 2, $ch * 2.4 + 1);
    $c->restore();
    if ($sun = $cam->toward(-0.45, -0.55, 0.28)) {
        $r = $ch * 0.5;
        $c->drawImage($glowFile, $sun[0] - $r, $sun[1] - $r, $r * 2, $r * 2);
    }
    $c->imageSmoothingEnabled = false;
    // The land, ray marched; then everything else, far to near, hidden behind hills.
    $cam->terrain($c, $terrainFile, $world, PEAK, 2, '#d6ebf6', FOG, FOG + 200, $g->clock);

    $things = [];
    $add = function (array $thing, float $x, float $y, float $z) use ($cam, &$things) {
        $p = $cam->project($x, $y, $z);
        if ($p && $p[3] < FOG && $p[0] > -400 && $p[0] < $cam->cx * 2 + 400) $things[] = [$p[3], $p, ...$thing];
    };
    foreach ($places as $p) $add(['model', $p['kind'], $p['x'], $p['y'], $p['z'], $p['yaw'], 0], $p['x'], $p['y'], $p['z']);
    foreach ($g->drones as $d) $add(['model', 'drone', $d['x'], $d['y'], $d['z'], $d['yaw'] + $g->clock * 3, $d['flash'] > 0 ? 0.6 : 0], $d['x'], $d['y'], $d['z']);
    $jetShown = !$cockpit && $g->mode !== 'over' && ($g->hurt <= 0 || fmod($g->clock, 0.16) < 0.1);
    if ($jetShown) $add(['model', 'jet', $g->x, $g->y, $g->z - 2, $g->yaw, $g->turn * 0.6], $g->x, $g->y, $g->z);
    foreach ($g->bullets as $b) $add(['bullet'], $b['x'], $b['y'], $b['z']);
    foreach ($g->orbs as $o) $add(['orb'], $o['x'], $o['y'], $o['z']);
    foreach ($g->particles as $q) $add(['spark', $q], $q['x'], $q['y'], $q['z']);
    usort($things, fn ($a, $b) => $b[0] <=> $a[0]);

    foreach ($things as $t) {
        [$depth, [$sx, $sy, $s], $kind] = $t;
        $alpha = 1 - ($depth / FOG) ** 2;
        $c->setDepth($depth);
        switch ($kind) {
            case 'bullet':
                if ($depth < 25) break;
                $c->globalAlpha = $alpha;
                $c->fillStyle = '#fff6c4';
                $z = min(8, max(2, $s * 1.5));
                $c->fillRect($sx - $z / 2, $sy - $z, $z, $z * 2);
                break;
            case 'orb':
                $c->globalAlpha = $alpha;
                $r = min(30, max(2, $s * 3));
                $c->fillStyle = 'rgba(255, 70, 140, 0.35)';
                $c->beginPath();
                $c->arc($sx, $sy, $r * 1.8, 0, 2 * M_PI);
                $c->fill();
                $c->fillStyle = '#ffd6ea';
                $c->beginPath();
                $c->arc($sx, $sy, $r, 0, 2 * M_PI);
                $c->fill();
                break;
            case 'spark':
                $q = $t[3];
                $c->globalAlpha = $alpha * min(1, $q['life'] * 3);
                $c->fillStyle = $q['color'];
                $z = min(14, max(1, $s * $q['size']));
                $c->fillRect($sx - $z / 2, $sy - $z / 2, $z, $z);
                break;
            default:
                [, , , $name, $x, $y, $z, $yaw, $roll] = $t;
                if (str_starts_with($name, 'cloud')) {
                    // Clouds thin out when near or right overhead, so they never fill the view.
                    $steep = abs($cam->z - $z) / $depth;
                    $alpha *= 0.9 * min(1, ($depth - 60) / 140) * max(0, min(1, (1.1 - $steep) / 0.5));
                }
                // A hit drone shows it by flickering.
                if ($name === 'drone' && $roll > 0) [$alpha, $roll] = [$alpha * 0.4, 0];
                if ($alpha > 0.02) $cam->stack($c, $voxels, $name, $x, $y, $z, $yaw, $alpha, $roll);
        }
        $c->globalAlpha = 1;
    }

    // Contrails: white pixels stretched and turned on the GPU, behind hills too.
    foreach ($g->trails as $trail) {
        $last = null;
        foreach ($trail as $i => [$x, $y, $z]) {
            $p = $cam->project($x, $y, $z);
            if ($p && $last && $i > 0) {
                [$dx, $dy] = [$p[0] - $last[0], $p[1] - $last[1]];
                $long = hypot($dx, $dy);
                if ($long > 0) {
                    $wide = min(6, max(1, $p[2] * 0.9));
                    $c->setDepth($p[3]);
                    $c->globalAlpha = 0.5 * (1 - $i / count($trail));
                    $c->setTransform($dx, $dy, -$dy / $long * $wide, $dx / $long * $wide, $last[0], $last[1]);
                    $c->drawImage($whiteFile, 0, -0.5, 1, 1);
                }
            }
            $last = $p;
        }
    }
    $c->setTransform(1, 0, 0, 1, 0, 0);
    $c->setDepth(0);
    $c->globalAlpha = 1;

    // Hurt: a red flash at the edges.
    if ($g->hurt > 0.6 && $g->mode === 'play') {
        $edge = $c->createRadialGradient($cw / 2, $ch / 2, $ch * 0.3, $cw / 2, $ch / 2, $ch * 0.9);
        $edge->addColorStop(0, 'rgba(255, 40, 40, 0)');
        $edge->addColorStop(1, sprintf('rgba(255, 40, 40, %.2f)', ($g->hurt - 0.6) * 0.8));
        $c->fillStyle = $edge;
        $c->fillRect(0, 0, $cw, $ch);
    }
    if ($cockpit) {
        // The gun sight, and a pitch ladder that stays level with the horizon.
        [$mx, $my] = [$cw / 2, $ch * 0.45];
        $c->strokeStyle = 'rgba(140, 255, 170, 0.9)';
        $c->lineWidth = 2;
        $c->beginPath();
        $c->arc($mx, $my, 18, 0, 2 * M_PI);
        foreach ([[-34, 0, -22, 0], [22, 0, 34, 0], [0, -34, 0, -22], [0, 22, 0, 34]] as [$a, $b, $e, $f]) {
            $c->moveTo($mx + $a, $my + $b);
            $c->lineTo($mx + $e, $my + $f);
        }
        $c->stroke();
        $c->save();
        $c->translate($mx, $cam->horizon());
        $c->rotate($cam->roll);
        $c->strokeStyle = 'rgba(140, 255, 170, 0.5)';
        $c->beginPath();
        foreach ([-1, 1] as $side) {
            $c->moveTo($side * 60, 0);
            $c->lineTo($side * 160, 0);
        }
        $c->stroke();
        $c->restore();
    }

    // HUD.
    $chip = function (string $text, float $x, float $y, string $align = 'left', int $full = -1, int $of = 0, string $color = '#ffffff') use ($c) {
        $c->font = '11px mono-semibold';
        $c->letterSpacing = '1.5px';
        $w = $c->measureText($text)->width + 16 + ($of ? $of * 8 + 4 : 0);
        $left = $align === 'right' ? $x - $w : $x;
        $c->fillStyle = 'rgba(10, 18, 32, 0.6)';
        $c->beginPath();
        $c->roundRect($left, $y, $w, 22, 4);
        $c->fill();
        $c->fillStyle = '#eef4fb';
        $c->textAlign = 'left';
        $c->textBaseline = 'middle';
        $c->fillText($text, $left + 8, $y + 11);
        $c->letterSpacing = '0px';
        for ($i = 0; $i < $of; $i++) {
            $c->fillStyle = $i < $full ? $color : 'rgba(219, 230, 245, 0.22)';
            $c->fillRect($left + $w - 8 - ($of - $i) * 8 + 2, $y + 7, 6, 8);
        }
    };
    if ($g->mode === 'play') {
        $chip(sprintf('SCORE %05d', $g->score), 14, 14);
        $chip(sprintf('SPD %03d  ALT %04d', $g->speed * 2, max(0, $g->z - $groundAt($g->x, $g->y)) * 10), $cw - 14, 14, 'right');
        $chip('HULL', 14, $ch - 36, 'left', $g->hp, 5, $g->hp <= 2 ? '#ff6b6b' : '#7ee787');
        $chip('V VIEW  M SOUND', $cw - 14, $ch - 36, 'right');
    }
    $c->textAlign = 'center';
    $c->textBaseline = 'middle';
    $title = function (string $big, string $small, string $hint) use ($c, $cw, $ch, $g) {
        $c->fillStyle = 'rgba(8, 20, 40, 0.45)';
        $c->fillRect(0, $ch * 0.3 - 50, $cw, 150);
        $c->font = 'bold 54px sans-serif';
        $c->letterSpacing = '8px';
        $c->fillStyle = 'rgba(8, 20, 40, 0.5)';
        $c->fillText($big, $cw / 2 + 3, $ch * 0.3 + 4);
        $c->fillStyle = '#ffffff';
        $c->fillText($big, $cw / 2, $ch * 0.3);
        $c->letterSpacing = '2px';
        $c->font = '13px mono-semibold';
        $c->fillStyle = '#eef4fb';
        $c->fillText($small, $cw / 2, $ch * 0.3 + 46);
        if (fmod($g->clock, 1.2) < 0.8) {
            $c->fillStyle = '#ffd23f';
            $c->fillText($hint, $cw / 2, $ch * 0.3 + 76);
        }
        $c->letterSpacing = '0px';
    };
    if ($g->mode === 'title') $title('ISLAND HOP', 'A/D BANK · W/S CLIMB · SHIFT SPEED · SPACE FIRE', 'PRESS SPACE TO FLY');
    if ($g->mode === 'over') $title('DOWN', sprintf('SCORE %d · %d DRONES · BEST %d', $g->score, $g->kills, $g->best), 'PRESS SPACE TO FLY AGAIN');
    if ($g->paused) {
        $c->font = 'bold 36px sans-serif';
        $c->fillStyle = '#ffffff';
        $c->fillText('PAUSED', $cw / 2, $ch / 2);
    }
};

$app = new App('Island Hop', 960, 600, fn () => col()->full()->bg('#2f6fc4')->schema([canvas($draw)->flex1()->id('game')]));
$app->icon("$dir/icon.png");
$last = microtime(true);
$app->every(1 / 120, function () use ($update, &$last) {
    $now = microtime(true);
    $update(min(0.05, $now - $last));
    $last = $now;
});
$app->onKey(function (int $key) use ($g, $audio) {
    if ($key === Key::ESCAPE && $g->mode === 'play') $g->paused = !$g->paused;
    if ($key === Key::V) $g->camera = $g->camera === 'chase' ? 'cockpit' : 'chase';
    if ($key === Key::M) {
        $audio->muted = !$audio->muted;
        $audio->music?->volume($audio->muted ? 0 : 0.35);
    }
});
$app->onShutdown(function () use ($audio) { $audio->playing = []; $audio->engine = $audio->music = null; });

if ($shots = getenv('PHPUI_SCREENSHOTS')) {
    @mkdir($shots, 0777, true);
    $reset();
    $g->autopilot = true;
    mt_srand(3);
    for ($i = 0; $i < 420; $i++) $update(1 / 60);
    [$g->hp, $g->hurt] = [5, 0];
    $app->screenshot("$shots/islandhop.png", 960, 600, 1);
    $g->camera = 'cockpit';
    $app->screenshot("$shots/islandhop-cockpit.png", 960, 600, 1);
    exit;
}
if (!getenv('PHPUI_NO_RUN')) $app->run();
