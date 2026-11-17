<?php

declare(strict_types=1);

namespace PhpUi;

use PhpSdl\Renderer;

/**
 * Voxel models cut into horizontal slices, all in one atlas image, for sprite stacking:
 * a model drawn as its slices stacked bottom to top, turned together, looks solid from
 * any side. Each layer has a top image (lit) and a side image (shaded darker toward the
 * ground). One atlas lets the GPU draw a whole scene in a few calls.
 *
 *     $voxels = new Voxels("$dir/models.png", [
 *         'tree' => ['size' => [9, 9, 16], 'unit' => 1.5, 'voxel' => fn ($x, $y, $z) => $color ?? null],
 *     ]);
 *
 * 'size' is [width, depth, height] in voxels, nose toward -y; 'unit' world units a
 * voxel (1); 'shade' how dark the lowest sides are (0.55). The voxel functions run only
 * when the atlas file is missing; delete it (or change its name) to rebuild.
 */
final class Voxels
{
    /** name => ['w', 'd', 'h', 'unit', 'layers' => [[top rect, side rect], ...]], rects [x, y, w, h] in the atlas. */
    public array $models = [];
    /** Whether this run made the atlas (so files made from it may be made too). */
    public bool $built = false;
    /** The atlas pixels, rows of RGBA, when built this run. */
    private array $rows = [];

    public function __construct(public readonly string $file, array $models)
    {
        [$row, $width] = [0, 0];
        foreach ($models as $name => $m) {
            [$w, $d, $h] = $m['size'];
            $layers = [];
            for ($z = 0; $z < $h; $z++) $layers[] = [[$z * 2 * $w, $row, $w, $d], [($z * 2 + 1) * $w, $row, $w, $d]];
            $this->models[$name] = ['w' => $w, 'd' => $d, 'h' => $h, 'unit' => (float) ($m['unit'] ?? 1), 'layers' => $layers];
            $width = max($width, $h * 2 * $w);
            $row += $d;
        }
        if (is_file($file)) return;
        $this->rows = array_fill(0, $row, str_repeat("\0", $width * 4));
        foreach ($models as $name => $m) $this->slice($name, $m['voxel'], (float) ($m['shade'] ?? 0.55));
        @mkdir(dirname($file), 0777, true);
        Renderer::savePng($file, $width, $row, implode('', $this->rows));
        $this->built = true;
    }

    /** The top image of a layer as RGBA bytes (for an icon), when the atlas was built this run. */
    public function layer(string $name, int $z): ?string
    {
        if (!$this->rows) return null;
        [$x, $y, $w, $d] = $this->models[$name]['layers'][$z][0];
        $rgba = '';
        for ($i = 0; $i < $d; $i++) $rgba .= substr($this->rows[$y + $i], $x * 4, $w * 4);
        return $rgba;
    }

    private function slice(string $name, callable $voxel, float $shade): void
    {
        ['w' => $w, 'd' => $d, 'h' => $h, 'layers' => $layers] = $this->models[$name];
        for ($z = 0; $z < $h; $z++) {
            [$top, $side] = ['', ''];
            $k = $shade + (0.85 - $shade) * $z / max(1, $h - 1);
            for ($y = 0; $y < $d; $y++) for ($x = 0; $x < $w; $x++) {
                $c = $voxel($x, $y, $z);
                if ($c === null) { $top .= "\0\0\0\0"; $side .= "\0\0\0\0"; continue; }
                [$r, $g, $b] = [hexdec(substr($c, 1, 2)), hexdec(substr($c, 3, 2)), hexdec(substr($c, 5, 2))];
                $top .= pack('C4', $r, $g, $b, 255);
                $side .= pack('C4', (int) ($r * $k), (int) ($g * $k), (int) ($b * $k), 255);
            }
            foreach ([$top, $side] as $which => $rgba) {
                [$ax, $ay] = $layers[$z][$which];
                for ($y = 0; $y < $d; $y++) {
                    $this->rows[$ay + $y] = substr_replace($this->rows[$ay + $y], substr($rgba, $y * $w * 4, $w * 4), $ax * 4, $w * 4);
                }
            }
        }
    }
}

/**
 * A 3D camera over a canvas: world x right, y down the map (south), z up. It looks
 * toward $yaw (0 is -y, north; turning right is positive), tilted down $pitch radians,
 * rolled $roll radians (clockwise on screen), with focal length $focal pixels and the
 * view's center at ($cx, $cy) on the canvas.
 * It projects points, draws a ground plane (Canvas::drawGround) and sprite stacks
 * (Voxels) in perspective. With $wrap set, the world repeats every $wrap units.
 */
final class Camera3D
{
    private float $fx = 0, $fy = -1, $cp = 1, $sp = 0, $rc = 1, $rs = 0;

    public function __construct(
        public float $x = 0, public float $y = 0, public float $z = 10,
        public float $yaw = 0, public float $pitch = 0, public float $focal = 400,
        public float $cx = 0, public float $cy = 0, public float $wrap = 0, public float $roll = 0,
    ) {
        $this->aim();
    }

    /** Recomputes the view after changing $yaw, $pitch or $roll. */
    public function aim(): self
    {
        [$this->fx, $this->fy] = [sin($this->yaw), -cos($this->yaw)];
        [$this->cp, $this->sp] = [cos($this->pitch), sin($this->pitch)];
        [$this->rc, $this->rs] = [cos($this->roll), sin($this->roll)];
        return $this;
    }

    /** Where the ground meets the sky in canvas y, at the view's center (may be above the canvas). */
    public function horizon(): float
    {
        return $this->cy - $this->focal * $this->sp / $this->cp;
    }

    /** In a world that wraps, the copy of ($x, $y) nearest the camera. */
    public function near(float $x, float $y): array
    {
        if ($this->wrap <= 0) return [$x, $y];
        return [$this->x + ($d = $x - $this->x) - $this->wrap * round($d / $this->wrap), $this->y + ($d = $y - $this->y) - $this->wrap * round($d / $this->wrap)];
    }

    /** A world point on the canvas: [x, y, pixels a unit there, depth], or null behind the camera. */
    public function project(float $x, float $y, float $z): ?array
    {
        [$x, $y] = $this->near($x, $y);
        return $this->onScreen($x, $y, $z);
    }

    /** project(), for the copy of the point at ($x, $y) itself. */
    private function onScreen(float $x, float $y, float $z): ?array
    {
        [$dx, $dy] = [$x - $this->x, $y - $this->y];
        $ahead = $dx * $this->fx + $dy * $this->fy;
        $side = $dx * -$this->fy + $dy * $this->fx;
        $dz = $z - $this->z;
        $depth = $ahead * $this->cp - $dz * $this->sp;
        if ($depth < 1) return null;
        $s = $this->focal / $depth;
        [$x, $y] = [$side * $s, -($ahead * $this->sp + $dz * $this->cp) * $s];
        return [$this->cx + $this->rc * $x - $this->rs * $y, $this->cy + $this->rs * $x + $this->rc * $y, $s, $depth];
    }

    /** The world point at height $z under canvas point ($sx, $sy), or null when the ray misses it. */
    public function unproject(float $sx, float $sy, float $z = 0): ?array
    {
        [$x, $y] = [$sx - $this->cx, $sy - $this->cy];
        [$sx, $up] = [$this->cx + $this->rc * $x + $this->rs * $y, -(-$this->rs * $x + $this->rc * $y)];
        // The ray: ahead (horizontal), sideways and up, per unit along the view.
        $ahead = $this->focal * $this->cp - $up * -$this->sp;
        $rise = -$this->focal * $this->sp + $up * $this->cp;
        if (abs($rise) < 1e-9) return null;
        $t = ($z - $this->z) / $rise;
        if ($t <= 0) return null;
        [$a, $s] = [$ahead * $t, ($sx - $this->cx) * $t];
        return [$this->x + $a * $this->fx - $s * $this->fy, $this->y + $a * $this->fy + $s * $this->fx];
    }

    /** The ground plane: $image repeating every $size world units, the camera $this->z above it. */
    public function ground(Canvas $c, string $image, float $size, float $pixel = 1, string $fog = 'transparent', float $fogDistance = 0): void
    {
        $c->drawGround($image, $this->x, $this->y, $this->yaw, $this->z, $this->horizon(), $this->focal, $pixel, $fog, $fogDistance, $size, $this->pitch, $this->roll);
    }

    /**
     * Model $name of $voxels standing at ($x, $y, $z), its nose turned to $yaw and banked
     * $roll radians (positive dips its right side, as a plane turning right). Returns
     * false when it is out of sight.
     */
    public function stack(Canvas $c, Voxels $voxels, string $name, float $x, float $y, float $z, float $yaw = 0,
        float $alpha = 1, float $roll = 0, int $maxSteps = 8): bool
    {
        [$x, $y] = $this->near($x, $y);
        $m = $voxels->models[$name];
        $u = $m['unit'];
        $tall = $m['h'] * $u;
        $base = $this->onScreen($x, $y, $z);
        $top = $this->onScreen($x, $y, $z + $tall);
        if (!$base || !$top) return false;
        $reach = max($m['w'], $m['d']) * $u * $base[2];
        if ($base[0] < -$reach || $base[0] > $c->width + $reach || max($base[1], $top[1]) < -$reach || min($base[1], $top[1]) > $c->height + $reach) return false;
        // How the model's image axes land on screen, at its foot and at its top.
        [$ux, $uy, $vx, $vy] = [cos($yaw) * $u, sin($yaw) * $u, -sin($yaw) * $u, cos($yaw) * $u];
        $axes = function (array $p, float $h) use ($x, $y, $ux, $uy, $vx, $vy): array {
            $e = 0.5;
            $px = $this->onScreen($x + $e, $y, $h);
            $py = $this->onScreen($x, $y + $e, $h);
            if (!$px || !$py) return [0, 0, 0, 0];
            [$jxx, $jxy, $jyx, $jyy] = [($px[0] - $p[0]) / $e, ($px[1] - $p[1]) / $e, ($py[0] - $p[0]) / $e, ($py[1] - $p[1]) / $e];
            return [$ux * $jxx + $uy * $jyx, $ux * $jxy + $uy * $jyy, $vx * $jxx + $vy * $jyx, $vx * $jxy + $vy * $jyy];
        };
        $foot = $axes($base, $z);
        $head = $axes($top, $z + $tall);
        $layers = $m['h'];
        // A layer up on screen; banked, the model turns about its nose-to-tail line through
        // its middle: the stacking leans sideways and the wings narrow (one rising).
        [$zx, $zy] = [($top[0] - $base[0]) / $layers, ($top[1] - $base[1]) / $layers];
        [$bc, $bs] = [cos(-$roll), sin(-$roll)];
        $mid = $layers / 2;
        [$lean, $foot, $head] = $roll == 0 ? [[$zx, $zy], $foot, $head] : [[$zx * $bc - $foot[0] * $bs, $zy * $bc - $foot[1] * $bs],
            [$foot[0] * $bc + $zx * $bs, $foot[1] * $bc + $zy * $bs, $foot[2], $foot[3]],
            [$head[0] * $bc + $zx * $bs, $head[1] * $bc + $zy * $bs, $head[2], $head[3]]];
        $steps = (int) max(1, min($maxSteps, ceil(hypot($lean[0], $lean[1]))));
        // From below (clouds overhead), the layers go top first, all shaded.
        $below = $this->z < $z + $tall / 2;
        $c->globalAlpha = $alpha;
        for ($n = 0; $n < $layers; $n++) {
            $layer = $below ? $layers - 1 - $n : $n;
            [$topImage, $sideImage] = $m['layers'][$layer];
            for ($i = 0; $i < $steps; $i++) {
                $l = $layer + ($below ? $steps - 1 - $i : $i) / $steps;
                $f = $l / $layers;
                $e = $base[0] + $zx * $mid + $lean[0] * ($l - $mid);
                $g = $base[1] + $zy * $mid + $lean[1] * ($l - $mid);
                $c->setTransform($foot[0] + ($head[0] - $foot[0]) * $f, $foot[1] + ($head[1] - $foot[1]) * $f,
                    $foot[2] + ($head[2] - $foot[2]) * $f, $foot[3] + ($head[3] - $foot[3]) * $f, $e, $g);
                $c->drawImage($voxels->file, ...[...(!$below && $i === $steps - 1 ? $topImage : $sideImage), -$m['w'] / 2, -$m['d'] / 2, $m['w'], $m['d']]);
            }
        }
        $c->setTransform(1, 0, 0, 1, 0, 0);
        $c->globalAlpha = 1;
        return true;
    }

    /** A soft round shadow of $radius units on the ground (height $z) at ($x, $y). */
    public function shadow(Canvas $c, float $x, float $y, float $radius, float $alpha = 0.25, float $z = 0): void
    {
        [$x, $y] = $this->near($x, $y);
        $p = $this->onScreen($x, $y, $z);
        $px = $this->onScreen($x + 1, $y, $z);
        $py = $this->onScreen($x, $y + 1, $z);
        if (!$p || !$px || !$py) return;
        $c->setTransform($px[0] - $p[0], $px[1] - $p[1], $py[0] - $p[0], $py[1] - $p[1], $p[0], $p[1]);
        $c->globalAlpha = $alpha;
        $c->fillStyle = '#000000';
        $c->beginPath();
        $c->arc(0, 0, $radius, 0, 2 * M_PI);
        $c->fill();
        $c->setTransform(1, 0, 0, 1, 0, 0);
        $c->globalAlpha = 1;
    }
}
