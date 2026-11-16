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
            $this->measure($root, ['color' => t('foreground'), 'size' => 14, 'font' => 'regular', 'tracking' => 0.0]);
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
        $text = ['color' => $e->color, 'size' => $e->size, 'font' => $e->font, 'tracking' => $tracking];

        if ($e->kind === 'text' && $e->get('wrap')) {
            $this->wraps[] = $e;
            $e->lines = $e->wrapAt === null ? [$e->text] : $this->painter->wrap($e->text, $e->wrapAt, $e->font, $e->size);
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
        } else {
            $row = $e->get('dir') === 'row';
            $main = $cross = 0.0;
            $n = 0;
            foreach ($e->children as $c) {
                $this->measure($c, $text);
                if ($c->isAbsolute()) continue;
                $n++;
                [$cw, $ch] = [$this->fixed($c, 'w') ?? $c->mw, $this->fixed($c, 'h') ?? $c->mh];
                $main += $row ? ($c->get('basis') ?? $cw) : $ch;
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
            if (!$c->isAbsolute()) continue;
            [$l, $r, $t, $b] = [$c->get('left'), $c->get('right'), $c->get('top'), $c->get('bottom')];
            $cw = $this->size($c, 'w', $w) ?? ($l !== null && $r !== null ? $w - $l - $r : $c->mw);
            $ch = $this->size($c, 'h', $h) ?? ($t !== null && $b !== null ? $h - $t - $b : $c->mh);
            $cx = $c->get('centerX') ? $x + ($w - $cw) / 2 : ($l !== null ? $x + $l : ($r !== null ? $x + $w - $r - $cw : $x));
            $cy = $t !== null ? $y + $t : ($b !== null ? $y + $h - $b - $ch : $y);
            $this->place($c, round($cx), round($cy), round($cw), round($ch));
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
