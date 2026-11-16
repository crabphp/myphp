<?php

declare(strict_types=1);

namespace PhpUi;

/*
 * Charts in the style of shadcn's (Recharts): dashed horizontal grid lines,
 * muted axis labels, smooth areas filled with a fading gradient. Drawn on a
 * Canvas, in the chart's own pixels.
 */

/**
 * A stacked area chart. $series: [name => [color, values[]]], drawn in order
 * (first at the bottom). $labels: one per value, a few shown on the x axis.
 */
function areaChart(array $labels, array $series, int $ticks = 6): Element
{
    return canvas(function (Canvas $c) use ($labels, $series, $ticks) {
        $n = count($labels);
        if ($n < 2) return;
        $axisH = 24;
        [$x0, $y0, $w, $h] = [0, 4, $c->width, $c->height - $axisH - 4];

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

        gridLines($c, $x0, $y0, $w, $h);

        // Areas, top series last so its line sits above.
        foreach ($stack as [$color, $bottom, $top]) {
            $line = array_map(fn ($i) => [$px($i), $py($top[$i])], range(0, $n - 1));
            $c->beginPath();
            smoothPath($c, $line, true);
            for ($i = $n - 1; $i >= 0; $i--) $c->lineTo($px($i), $py($bottom[$i]));
            $c->closePath();
            $fade = $c->createLinearGradient(0, $y0, 0, $y0 + $h);
            $fade->addColorStop(0, Theme::alpha($color, 0.8));
            $fade->addColorStop(1, Theme::alpha($color, 0.1));
            $c->fillStyle = $fade;
            $c->fill();

            $c->beginPath();
            smoothPath($c, $line, true);
            $c->lineWidth = 1;
            $c->strokeStyle = $color;
            $c->stroke();
        }

        // X axis labels.
        $c->font = '12px regular';
        $c->fillStyle = t('muted-foreground');
        $c->textAlign = 'center';
        $c->textBaseline = 'middle';
        for ($k = 0; $k < $ticks; $k++) {
            $i = (int) round(($n - 1) * ($k + 0.5) / $ticks);
            $c->fillText($labels[$i], $px($i), $y0 + $h + $axisH / 2 + 4);
        }
    });
}

/** A bar chart with rounded tops. $series: [name => [color, values[]]] grouped side by side. */
function barChart(array $labels, array $series): Element
{
    return canvas(function (Canvas $c) use ($labels, $series) {
        $n = count($labels);
        if (!$n) return;
        $axisH = 24;
        [$x0, $y0, $w, $h] = [0, 4, $c->width, $c->height - $axisH - 4];
        $max = max(array_map(fn ($s) => max($s[1]), $series)) * 1.1 ?: 1;

        gridLines($c, $x0, $y0, $w, $h);

        $slot = $w / $n;
        $k = count($series);
        $bw = min(28, ($slot * 0.7) / $k);
        $c->font = '12px regular';
        $c->textAlign = 'center';
        $c->textBaseline = 'middle';
        foreach ($labels as $i => $label) {
            $gx = $x0 + $slot * $i + ($slot - $bw * $k - 4 * ($k - 1)) / 2;
            $j = 0;
            foreach ($series as [$color, $values]) {
                $bh = $h * $values[$i] / $max;
                $c->beginPath();
                $c->roundRect($gx + $j * ($bw + 4), $y0 + $h - $bh, $bw, $bh, [4, 4, 0, 0]);
                $c->fillStyle = $color;
                $c->fill();
                $j++;
            }
            $c->fillStyle = t('muted-foreground');
            $c->fillText($label, $x0 + $slot * ($i + 0.5), $y0 + $h + $axisH / 2 + 4);
        }
    });
}

/** A tiny line for stat cards. */
function sparkline(array $values, string $color): Element
{
    return canvas(function (Canvas $c) use ($values, $color) {
        $n = count($values);
        if ($n < 2) return;
        [$min, $max] = [min($values), max($values)];
        $span = $max - $min ?: 1;
        $pts = array_map(fn ($i) => [$c->width * $i / ($n - 1), 2 + ($c->height - 4) * (1 - ($values[$i] - $min) / $span)], range(0, $n - 1));
        $c->beginPath();
        smoothPath($c, $pts, true);
        $c->lineTo($c->width, $c->height);
        $c->lineTo(0, $c->height);
        $c->closePath();
        $fade = $c->createLinearGradient(0, 0, 0, $c->height);
        $fade->addColorStop(0, Theme::alpha($color, 0.25));
        $fade->addColorStop(1, Theme::alpha($color, 0));
        $c->fillStyle = $fade;
        $c->fill();
        $c->beginPath();
        smoothPath($c, $pts, true);
        $c->lineWidth = 1.5;
        $c->strokeStyle = $color;
        $c->stroke();
    });
}

/** Dashed horizontal grid lines, on whole pixels so they stay crisp. */
function gridLines(Canvas $c, float $x0, float $y0, float $w, float $h): void
{
    $c->save();
    $c->lineWidth = 1;
    $c->strokeStyle = t('border');
    $c->setLineDash([3, 3]);
    $c->beginPath();
    for ($g = 0; $g <= 3; $g++) {
        $gy = round($y0 + $h * $g / 3) + 0.5;
        $c->moveTo($x0, $gy);
        $c->lineTo($x0 + $w, $gy);
    }
    $c->stroke();
    $c->restore();
}

/** A monotone-ish curve through points (Catmull-Rom as Béziers, clamped vertically). */
function smoothPath(Canvas $c, array $pts, bool $move): void
{
    $n = count($pts);
    $move ? $c->moveTo(...$pts[0]) : $c->lineTo(...$pts[0]);
    for ($i = 0; $i < $n - 1; $i++) {
        [$p0, $p1, $p2, $p3] = [$pts[max(0, $i - 1)], $pts[$i], $pts[$i + 1], $pts[min($n - 1, $i + 2)]];
        $c1 = [$p1[0] + ($p2[0] - $p0[0]) / 6, $p1[1] + ($p2[1] - $p0[1]) / 6];
        $c2 = [$p2[0] - ($p3[0] - $p1[0]) / 6, $p2[1] - ($p3[1] - $p1[1]) / 6];
        [$lo, $hi] = [min($p1[1], $p2[1]), max($p1[1], $p2[1])];
        $c1[1] = max($lo, min($hi, $c1[1]));
        $c2[1] = max($lo, min($hi, $c2[1]));
        $c->bezierCurveTo($c1[0], $c1[1], $c2[0], $c2[1], $p2[0], $p2[1]);
    }
}
