<?php

declare(strict_types=1);

namespace PhpUi;

use GL\VectorGraphics\VGAlign;
use GL\VectorGraphics\VGContext;

/*
 * Charts in the style of shadcn's (Recharts): dashed horizontal grid lines,
 * muted axis labels, smooth areas filled with a fading gradient.
 */

/**
 * A stacked area chart. $series: [name => [color, values[]]], drawn in order
 * (first at the bottom). $labels: one per value, a few shown on the x axis.
 */
function areaChart(array $labels, array $series, int $ticks = 6): Element
{
    return canvas(function (VGContext $vg, Element $e, Painter $p) use ($labels, $series, $ticks) {
        $n = count($labels);
        if ($n < 2) return;
        $axisH = 24;
        [$x0, $y0, $w, $h] = [$e->x, $e->y + 4, $e->w, $e->h - $axisH - 4];

        // Stack the series and find the top.
        $stack = [];
        $base = array_fill(0, $n, 0.0);
        foreach ($series as $name => [$color, $values]) {
            $top = array_map(fn ($b, $v) => $b + $v, $base, $values);
            $stack[$name] = [$color, $base, $top];
            $base = $top;
        }
        $max = max($base) * 1.1 ?: 1;
        $px = fn (int $i) => $x0 + $w * $i / ($n - 1);
        $py = fn (float $v) => $y0 + $h - $h * $v / $max;

        // Grid: dashed horizontal lines.
        $vg->strokeWidth(1);
        $vg->strokeColor($p->color(t('border')));
        for ($g = 0; $g <= 3; $g++) {
            $gy = round($y0 + $h * $g / 3) + 0.5;
            $vg->beginPath();
            for ($dx = $x0; $dx < $x0 + $w; $dx += 6) {
                $vg->moveTo($dx, $gy);
                $vg->lineTo(min($dx + 3, $x0 + $w), $gy);
            }
            $vg->stroke();
        }

        // Areas, top series last so its line sits above.
        foreach ($stack as [$color, $bottom, $top]) {
            $vg->beginPath();
            smoothPath($vg, array_map(fn ($i) => [$px($i), $py($top[$i])], range(0, $n - 1)), true);
            for ($i = $n - 1; $i >= 0; $i--) $vg->lineTo($px($i), $py($bottom[$i]));
            $vg->closePath();
            $vg->fillPaint($vg->linearGradient(0, $y0, 0, $y0 + $h,
                $p->color(Theme::alpha($color, 0.8)), $p->color(Theme::alpha($color, 0.1))));
            $vg->fill();

            $vg->beginPath();
            smoothPath($vg, array_map(fn ($i) => [$px($i), $py($top[$i])], range(0, $n - 1)), true);
            $vg->strokeWidth(1);
            $vg->strokeColor($p->color($color));
            $vg->stroke();
        }

        // X axis labels.
        $vg->fontFace('regular');
        $vg->fontSize(12);
        $vg->textLetterSpacing(0);
        $vg->fillColor($p->color(t('muted-foreground')));
        $vg->textAlign(VGAlign::CENTER | VGAlign::MIDDLE);
        for ($k = 0; $k < $ticks; $k++) {
            $i = (int) round(($n - 1) * ($k + 0.5) / $ticks);
            $vg->text($px($i), $y0 + $h + $axisH / 2 + 4, $labels[$i]);
        }
    });
}

/** A bar chart with rounded tops. $series: [name => [color, values[]]] grouped side by side. */
function barChart(array $labels, array $series): Element
{
    return canvas(function (VGContext $vg, Element $e, Painter $p) use ($labels, $series) {
        $n = count($labels);
        if (!$n) return;
        $axisH = 24;
        [$x0, $y0, $w, $h] = [$e->x, $e->y + 4, $e->w, $e->h - $axisH - 4];
        $max = max(array_map(fn ($s) => max($s[1]), $series)) * 1.1 ?: 1;

        $vg->strokeWidth(1);
        $vg->strokeColor($p->color(t('border')));
        for ($g = 0; $g <= 3; $g++) {
            $gy = round($y0 + $h * $g / 3) + 0.5;
            $vg->beginPath();
            for ($dx = $x0; $dx < $x0 + $w; $dx += 6) {
                $vg->moveTo($dx, $gy);
                $vg->lineTo(min($dx + 3, $x0 + $w), $gy);
            }
            $vg->stroke();
        }

        $slot = $w / $n;
        $k = count($series);
        $bw = min(28, ($slot * 0.7) / $k);
        $vg->fontFace('regular');
        $vg->fontSize(12);
        $vg->textLetterSpacing(0);
        foreach ($labels as $i => $label) {
            $gx = $x0 + $slot * $i + ($slot - $bw * $k - 4 * ($k - 1)) / 2;
            $j = 0;
            foreach ($series as [$color, $values]) {
                $bh = $h * $values[$i] / $max;
                $p->shape($gx + $j * ($bw + 4), $y0 + $h - $bh, $bw, $bh, [4, 4, 0, 0]);
                $vg->fillColor($p->color($color));
                $vg->fill();
                $j++;
            }
            $vg->fillColor($p->color(t('muted-foreground')));
            $vg->textAlign(VGAlign::CENTER | VGAlign::MIDDLE);
            $vg->text($x0 + $slot * ($i + 0.5), $y0 + $h + $axisH / 2 + 4, $label);
        }
    });
}

/** A tiny line for stat cards. */
function sparkline(array $values, string $color): Element
{
    return canvas(function (VGContext $vg, Element $e, Painter $p) use ($values, $color) {
        $n = count($values);
        [$min, $max] = [min($values), max($values)];
        $span = $max - $min ?: 1;
        $pts = array_map(fn ($i) => [$e->x + $e->w * $i / ($n - 1), $e->y + 2 + ($e->h - 4) * (1 - ($values[$i] - $min) / $span)], range(0, $n - 1));
        $vg->beginPath();
        smoothPath($vg, $pts, true);
        $vg->lineTo($e->x + $e->w, $e->y + $e->h);
        $vg->lineTo($e->x, $e->y + $e->h);
        $vg->closePath();
        $vg->fillPaint($vg->linearGradient(0, $e->y, 0, $e->y + $e->h, $p->color(Theme::alpha($color, 0.25)), $p->color(Theme::alpha($color, 0))));
        $vg->fill();
        $vg->beginPath();
        smoothPath($vg, $pts, true);
        $vg->strokeWidth(1.5);
        $vg->strokeColor($p->color($color));
        $vg->stroke();
    });
}

/** A monotone-ish curve through points (Catmull-Rom as Béziers, clamped vertically). */
function smoothPath(VGContext $vg, array $pts, bool $move): void
{
    $n = count($pts);
    $move ? $vg->moveTo(...$pts[0]) : $vg->lineTo(...$pts[0]);
    for ($i = 0; $i < $n - 1; $i++) {
        [$p0, $p1, $p2, $p3] = [$pts[max(0, $i - 1)], $pts[$i], $pts[$i + 1], $pts[min($n - 1, $i + 2)]];
        $c1 = [$p1[0] + ($p2[0] - $p0[0]) / 6, $p1[1] + ($p2[1] - $p0[1]) / 6];
        $c2 = [$p2[0] - ($p3[0] - $p1[0]) / 6, $p2[1] - ($p3[1] - $p1[1]) / 6];
        [$lo, $hi] = [min($p1[1], $p2[1]), max($p1[1], $p2[1])];
        $c1[1] = max($lo, min($hi, $c1[1]));
        $c2[1] = max($lo, min($hi, $c2[1]));
        $vg->bezierTo($c1[0], $c1[1], $c2[0], $c2[1], $p2[0], $p2[1]);
    }
}
