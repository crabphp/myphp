<?php

declare(strict_types=1);

namespace PhpUi;

/**
 * A small flexbox: rows and columns, gap, padding, fixed or percent sizes,
 * basis, grow, shrink, align, align-self and justify; absolute children
 * placed by insets; vertical scroll containers. Two passes: measure()
 * bottom-up for content sizes, place() top-down for boxes.
 */
final class Layout
{
    /** @param array<string, float> $scroll Scroll offsets by element id, clamped here. */
    public function __construct(private Painter $painter, public array $scroll = []) {}

    /** @var Element[] Wrapping text elements found by the last measure(). */
    private array $wraps = [];

    public function compute(Element $root, float $width, float $height): void
    {
        // Wrapped text is as tall as its width allows: lay out, wrap at the widths
        // found, and lay out again until the widths stop changing.
        for ($pass = 0; $pass < 4; $pass++) {
            $this->wraps = [];
            $this->measure($root, ['color' => t('foreground'), 'size' => 14, 'font' => 'regular', 'tracking' => 0.0, 'selectable' => false]);
            $this->place($root, 0, 0, $width, $height);
            $changed = false;
            foreach ($this->wraps as $e) {
                $inner = max(1.0, $e->w - $e->get('pl', 0) - $e->get('pr', 0));
                // An image's width decides its height, like wrapped text.
                if ($e->kind === 'image') $inner = $e->w;
                if ($e->wrapAt !== $inner) [$e->wrapAt, $changed] = [$inner, true];
            }
            if (!$changed) return;
        }
    }

    /** Content size of each element (ignoring percent sizes), and inherited text style. */
    private function measure(Element $e, array $inherited): void
    {
        $e->color = $e->get('color', $inherited['color']);
        $e->size = $e->get('size', $inherited['size']);
        $e->font = $e->get('font', $inherited['font']);
        $tracking = $e->get('tracking', $inherited['tracking']);
        $e->style['tracking'] = $tracking;
        $e->selectable = (bool) $e->get('selectable', $inherited['selectable']);
        $text = ['color' => $e->color, 'size' => $e->size, 'font' => $e->font, 'tracking' => $tracking, 'selectable' => $e->selectable];

        if ($e->kind === 'text' && $e->get('wrap')) {
            $this->wraps[] = $e;
            // Ranges keep every character, so selection maps back to the text; lines are what is drawn.
            $e->ranges = $this->painter->breakLines($e->text, $e->wrapAt ?? 0, $e->font, $e->size, $tracking);
            $e->lines = array_map(fn ($r) => rtrim(mb_substr($e->text, $r[0], $r[1]), ' '), $e->ranges);
            $e->mw = ceil(max(array_map(fn ($line) => $this->painter->textWidth($line, $e->font, $e->size, $tracking), $e->lines ?: [''])));
            $e->mh = $this->painter->lineHeight($e->size) * max(1, count($e->lines));
        } elseif ($e->kind === 'image') {
            [$iw, $ih] = $e->imageSize;
            $ratio = $iw > 0 ? $ih / $iw : 1;
            [$w, $h] = [$e->get('w'), $e->get('h')];
            if ($h === null && $w !== null) {
                // Height follows the width the layout gives it (a percent width too).
                $this->wraps[] = $e;
                $e->mw = is_string($w) ? $iw : (float) $w;
                $e->mh = ($e->wrapAt ?? $e->mw) * $ratio;
            } elseif ($w === null && $h !== null && !is_string($h)) {
                [$e->mw, $e->mh] = [(float) $h / $ratio, (float) $h];
            } else {
                [$e->mw, $e->mh] = [$iw, $ih];
            }
        } elseif ($e->kind === 'text') {
            $e->mw = ceil($this->painter->textWidth($e->text, $e->font, $e->size, $tracking));
            $e->mh = $this->painter->lineHeight($e->size);
        } elseif ($e->kind === 'input') {
            $e->mw = 200;
            $e->mh = $this->painter->lineHeight($e->size) * $e->get('multiline', 1);
        } elseif ($e->get('grid')) {
            $this->measureGrid($e, $text);
        } elseif ($e->get('flexWrap')) {
            $this->measureFlow($e, $text);
        } else {
            $row = $e->get('dir') === 'row';
            $main = $cross = 0.0;
            $n = 0;
            foreach ($e->children as $c) {
                $this->measure($c, $text);
                if ($c->isAbsolute()) continue;
                $n++;
                [$cw, $ch] = [$this->fixed($c, 'w') ?? $c->mw, $this->fixed($c, 'h') ?? $c->mh];
                // A basis replaces the content size on the main axis, in rows and columns alike.
                $main += $c->get('basis') ?? ($row ? $cw : $ch);
                $cross = max($cross, $row ? $ch : $cw);
            }
            $main += $e->get('gap', 0) * max(0, $n - 1);
            [$e->mw, $e->mh] = $row ? [$main, $cross] : [$cross, $main];
        }

        $e->mw += $e->get('pl', 0) + $e->get('pr', 0);
        $e->mh += $e->get('pt', 0) + $e->get('pb', 0);
    }

    private function place(Element $e, float $x, float $y, float $w, float $h): void
    {
        [$e->x, $e->y, $e->w, $e->h] = [$x, $y, $w, $h];
        if (!$e->children) return;
        if ($e->get('grid') || $e->get('flexWrap')) {
            $this->placeLines($e, $x, $y, $w, $h);
            return;
        }

        $row = $e->get('dir') === 'row';
        $ix = $x + $e->get('pl', 0);
        $iy = $y + $e->get('pt', 0);
        $iw = max(0, $w - $e->get('pl', 0) - $e->get('pr', 0));
        $ih = max(0, $h - $e->get('pt', 0) - $e->get('pb', 0));
        $scrolls = $e->get('scroll', false) && !$row;
        [$mainKey, $crossKey] = $row ? ['w', 'h'] : ['h', 'w'];
        $gap = $e->get('gap', 0);

        $flow = array_filter($e->children, fn (Element $c) => !$c->isAbsolute());
        $n = count($flow);

        // A scroll container lays its children out at their content height, then offsets them.
        if ($scrolls) {
            $content = 0.0;
            foreach ($flow as $c) $content += $this->size($c, 'h', $ih) ?? $c->mh;
            $content += $gap * max(0, $n - 1);
            $mainSpace = max($ih, $content);
            $e->contentH = $content + $e->get('pt', 0) + $e->get('pb', 0);
            $max = max(0, $e->contentH - $h);
            $e->scrollY = $this->scroll[$e->id] = min($max, max(0, $this->scroll[$e->id] ?? 0));
            $iy -= $e->scrollY;
        } else {
            $mainSpace = $row ? $iw : $ih;
        }
        $crossSpace = $row ? $ih : $iw;

        // Main axis: base sizes, then share free space among growing children (or take it from shrinking ones).
        $sizes = [];
        $grow = $shrink = 0.0;
        foreach ($flow as $i => $c) {
            $sizes[$i] = $this->size($c, $mainKey, $mainSpace) ?? $c->get('basis') ?? ($row ? $c->mw : $c->mh);
            $grow += $c->get('grow', 0);
            $shrink += $c->get('shrink', 0) * $sizes[$i];
        }
        $free = $mainSpace - array_sum($sizes) - $gap * max(0, $n - 1);
        $offset = 0.0;
        $between = $gap;
        if ($free > 0 && $grow > 0) {
            foreach ($flow as $i => $c) $sizes[$i] += $free * $c->get('grow', 0) / $grow;
        } elseif ($free < 0 && $shrink > 0) {
            foreach ($flow as $i => $c) $sizes[$i] = max(0, $sizes[$i] + $free * $c->get('shrink', 0) * $sizes[$i] / $shrink);
        } elseif ($free > 0) {
            match ($e->get('justify', 'start')) {
                'center' => $offset = $free / 2,
                'end' => $offset = $free,
                'between' => $between = $n > 1 ? $gap + $free / ($n - 1) : $gap,
                default => null,
            };
        }

        // Cross axis: fixed, percent, stretch or content size, then align.
        $pos = $offset;
        foreach ($flow as $i => $c) {
            $align = $c->get('alignSelf', $e->get('align', 'stretch'));
            $cs = $this->size($c, $crossKey, $crossSpace)
                ?? ($align === 'stretch' ? $crossSpace : min($crossSpace, $row ? $c->mh : $c->mw));
            $co = match ($align) {
                'center' => ($crossSpace - $cs) / 2,
                'end' => $crossSpace - $cs,
                default => 0,
            };
            $row
                ? $this->place($c, round($ix + $pos), round($iy + $co), round($sizes[$i]), round($cs))
                : $this->place($c, round($ix + $co), round($iy + $pos), round($cs), round($sizes[$i]));
            $pos += $sizes[$i] + $between;
        }

        // Absolute children: insets against this element's border box.
        foreach ($e->children as $c) {
            if ($c->isAbsolute()) $this->placeAbsolute($c, $x, $y, $w, $h);
        }
    }

    private function placeAbsolute(Element $c, float $x, float $y, float $w, float $h): void
    {
        [$l, $r, $t, $b] = [$c->get('left'), $c->get('right'), $c->get('top'), $c->get('bottom')];
        $cw = $this->size($c, 'w', $w) ?? ($l !== null && $r !== null ? $w - $l - $r : $c->mw);
        $ch = $this->size($c, 'h', $h) ?? ($t !== null && $b !== null ? $h - $t - $b : $c->mh);
        $cx = $c->get('centerX') ? $x + ($w - $cw) / 2 : ($l !== null ? $x + $l : ($r !== null ? $x + $w - $r - $cw : $x));
        $cy = $t !== null ? $y + $t : ($b !== null ? $y + $h - $b - $ch : $y);
        $this->place($c, round($cx), round($cy), round($cw), round($ch));
    }

    // Grids and wrapping rows ----------------------------------------------

    /** Child sizes for line layout: width (fixed or content) and height. */
    private function childBox(Element $c, float $space): array
    {
        return [$this->size($c, 'w', $space) ?? $c->mw, $this->size($c, 'h', 0) ?? $c->mh];
    }

    /** Lines of children: each [indexes, height], from the width they get. */
    private function lines(Element $e, float $inner): array
    {
        $flow = array_values(array_filter($e->children, fn (Element $c) => !$c->isAbsolute()));
        $gap = $e->get('gap', 0);
        $lines = [];
        if ($cols = $e->get('grid')) {
            $cw = max(0, ($inner - $gap * ($cols - 1)) / $cols);
            foreach (array_chunk($flow, $cols) as $chunk) {
                $lines[] = [$chunk, max(array_map(fn (Element $c) => $this->size($c, 'h', 0) ?? $c->mh, $chunk)), $cw];
            }
            return $lines;
        }
        [$line, $used, $height] = [[], 0.0, 0.0];
        foreach ($flow as $c) {
            [$cw, $ch] = $this->childBox($c, $inner);
            if ($line && $used + $gap + $cw > $inner + 0.5) {
                $lines[] = [$line, $height, null];
                [$line, $used, $height] = [[], 0.0, 0.0];
            }
            $used += ($line ? $gap : 0) + $cw;
            $line[] = $c;
            $height = max($height, $ch);
        }
        if ($line) $lines[] = [$line, $height, null];
        return $lines;
    }

    private function measureGrid(Element $e, array $text): void
    {
        $cols = $e->get('grid');
        $gap = $e->get('gap', 0);
        $widest = 0.0;
        foreach ($e->children as $c) {
            $this->measure($c, $text);
            if (!$c->isAbsolute()) $widest = max($widest, $this->fixed($c, 'w') ?? $c->mw);
        }
        // Rows depend on the column width: measure with the width found by the last layout pass.
        $this->wraps[] = $e;
        $inner = $e->wrapAt ?? ($widest * $cols + $gap * ($cols - 1));
        $lines = $this->lines($e, $inner);
        $e->mw = $widest * $cols + $gap * ($cols - 1);
        $e->mh = array_sum(array_column($lines, 1)) + $gap * max(0, count($lines) - 1);
    }

    private function measureFlow(Element $e, array $text): void
    {
        $gap = $e->get('gap', 0);
        $sum = 0.0;
        $n = 0;
        foreach ($e->children as $c) {
            $this->measure($c, $text);
            if ($c->isAbsolute()) continue;
            $sum += ($this->fixed($c, 'w') ?? $c->mw) + ($n++ ? $gap : 0);
        }
        $this->wraps[] = $e;
        $lines = $this->lines($e, $e->wrapAt ?? $sum);
        $e->mw = $sum;
        $e->mh = array_sum(array_column($lines, 1)) + $gap * max(0, count($lines) - 1);
    }

    /** Place a grid or wrapping row: lines top to bottom, children left to right. */
    private function placeLines(Element $e, float $x, float $y, float $w, float $h): void
    {
        $ix = $x + $e->get('pl', 0);
        $iy = $y + $e->get('pt', 0);
        $iw = max(0, $w - $e->get('pl', 0) - $e->get('pr', 0));
        $gap = $e->get('gap', 0);
        $justify = $e->get('justify', 'start');
        foreach ($this->lines($e, $iw) as [$line, $height, $cellW]) {
            $boxes = array_map(fn (Element $c) => $cellW !== null
                ? [$this->size($c, 'w', $cellW) ?? $cellW, $this->size($c, 'h', $height) ?? $height]
                : $this->childBox($c, $iw), $line);
            $n = count($line);
            $used = $cellW !== null ? $iw : array_sum(array_column($boxes, 0)) + $gap * ($n - 1);
            $free = max(0, $iw - $used);
            [$pos, $between] = $cellW !== null ? [0.0, $gap] : match ($justify) {
                'center' => [$free / 2, $gap],
                'end' => [$free, $gap],
                'between' => [0.0, $n > 1 ? $gap + $free / ($n - 1) : $gap],
                default => [0.0, $gap],
            };
            foreach ($line as $i => $c) {
                [$cw, $ch] = $boxes[$i];
                $slot = $cellW ?? $cw;
                $align = $c->get('alignSelf', $e->get('align', $cellW !== null ? 'stretch' : 'start'));
                if ($align === 'stretch' && $this->size($c, 'h', $height) === null) $ch = $height;
                $cy = match ($align) { 'center' => ($height - $ch) / 2, 'end' => $height - $ch, default => 0 };
                $this->place($c, round($ix + $pos), round($iy + $cy), round(min($cw, $slot)), round($ch));
                $pos += $slot + $between;
            }
            $iy += $height + $gap;
        }
        foreach ($e->children as $c) {
            if ($c->isAbsolute()) $this->placeAbsolute($c, $x, $y, $w, $h);
        }
    }

    /** An explicit size in px, or a percent of the available space; null when unset. */
    private function size(Element $e, string $key, float $space): ?float
    {
        $v = $e->get($key);
        if (is_string($v) && str_ends_with($v, '%')) return $space * (float) $v / 100;
        return $v === null ? null : (float) $v;
    }

    private function fixed(Element $e, string $key): ?float
    {
        $v = $e->get($key);
        return is_string($v) ? null : $v;
    }
}
