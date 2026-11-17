<?php

declare(strict_types=1);

namespace PhpUi;

/**
 * Drawing in a canvas() element, with the HTML canvas 2D API: the same properties
 * (fillStyle, lineWidth, font, globalAlpha, shadowBlur, ...) and methods (beginPath,
 * arc, fill, stroke, fillText, drawImage, save, rotate, clip, ...), in the element's
 * own pixels (0, 0 is its top left). Text is the toolkit's: shaped by HarfBuzz, with
 * fallback fonts. Not supported: clearRect() (a canvas draws over the UI, there is
 * nothing to clear to), getImageData()/putImageData(), filter, and composite
 * operations other than source-over.
 */
final class Canvas
{
    public string|CanvasGradient|CanvasPattern $fillStyle = '#000000';
    public string|CanvasGradient|CanvasPattern $strokeStyle = '#000000';
    public float $lineWidth = 1.0;
    /** 'butt', 'round' or 'square'. */
    public string $lineCap = 'butt';
    /** 'miter', 'round' or 'bevel'. */
    public string $lineJoin = 'miter';
    public float $miterLimit = 10.0;
    public float $lineDashOffset = 0.0;
    public float $globalAlpha = 1.0;
    /** Only 'source-over' draws as named; others draw as it. */
    public string $globalCompositeOperation = 'source-over';
    /** CSS font shorthand: '[style] [weight] size[/line-height] family', e.g. 'bold 14px sans-serif'. */
    public string $font = '10px sans-serif';
    /** 'start', 'end', 'left', 'right' or 'center'. */
    public string $textAlign = 'start';
    /** 'alphabetic', 'top', 'hanging', 'middle', 'ideographic' or 'bottom'. */
    public string $textBaseline = 'alphabetic';
    /** 'ltr' or 'rtl': which side start and end are. */
    public string $direction = 'ltr';
    /** CSS length, e.g. '1px'. */
    public string $letterSpacing = '0px';
    public float $shadowBlur = 0.0;
    public string $shadowColor = 'rgba(0, 0, 0, 0)';
    public float $shadowOffsetX = 0.0;
    public float $shadowOffsetY = 0.0;
    public bool $imageSmoothingEnabled = true;
    public string $imageSmoothingQuality = 'low';

    public readonly float $width;
    public readonly float $height;

    private \PhpSdl\Path $path;
    /** @var float[] User units to the element's pixels. */
    private array $ctm = [1, 0, 0, 1, 0, 0];
    /** @var float[] */
    private array $lineDash = [];
    /** @var list<array{\PhpSdl\Path, int}> Clip paths, in the element's pixels. */
    private array $clips = [];
    private array $stack = [];
    /** @var float[] The element's pixels to the frame's. */
    private readonly array $base;

    private const JOINS = ['miter' => 0, 'round' => 1, 'bevel' => 2];
    private const CAPS = ['butt' => 0, 'round' => 1, 'square' => 2];

    /**
     * @param float[] $clip The frame's clip [x, y, w, h], or null.
     */
    public function __construct(
        private readonly \PhpSdl\Renderer $renderer,
        float $x, float $y, float $width, float $height,
        private readonly ?array $clip = null,
        private readonly float $opacity = 1.0,
    ) {
        $this->width = $width;
        $this->height = $height;
        $this->base = [1, 0, 0, 1, $x, $y];
        $this->path = new \PhpSdl\Path();
    }

    // State ----------------------------------------------------------------

    public function save(): void
    {
        $this->stack[] = [$this->state(), $this->ctm, $this->lineDash, $this->clips];
    }

    public function restore(): void
    {
        if (!$this->stack) return;
        [$state, $this->ctm, $this->lineDash, $this->clips] = array_pop($this->stack);
        foreach ($state as $k => $v) $this->$k = $v;
        $this->path->setTransform($this->ctm);
    }

    /** Back to a new canvas's state, with an empty path. */
    public function reset(): void
    {
        $fresh = new \ReflectionClass(self::class);
        foreach ($fresh->getDefaultProperties() as $k => $v) {
            if (array_key_exists($k, $this->state())) $this->$k = $v;
        }
        [$this->ctm, $this->lineDash, $this->clips, $this->stack] = [[1, 0, 0, 1, 0, 0], [], [], []];
        $this->path = new \PhpSdl\Path();
    }

    private function state(): array
    {
        return [
            'fillStyle' => $this->fillStyle, 'strokeStyle' => $this->strokeStyle, 'lineWidth' => $this->lineWidth,
            'lineCap' => $this->lineCap, 'lineJoin' => $this->lineJoin, 'miterLimit' => $this->miterLimit,
            'lineDashOffset' => $this->lineDashOffset, 'globalAlpha' => $this->globalAlpha,
            'globalCompositeOperation' => $this->globalCompositeOperation, 'font' => $this->font,
            'textAlign' => $this->textAlign, 'textBaseline' => $this->textBaseline, 'direction' => $this->direction,
            'letterSpacing' => $this->letterSpacing, 'shadowBlur' => $this->shadowBlur, 'shadowColor' => $this->shadowColor,
            'shadowOffsetX' => $this->shadowOffsetX, 'shadowOffsetY' => $this->shadowOffsetY,
            'imageSmoothingEnabled' => $this->imageSmoothingEnabled, 'imageSmoothingQuality' => $this->imageSmoothingQuality,
        ];
    }

    // Transforms -------------------------------------------------------------

    public function translate(float $x, float $y): void { $this->transform(1, 0, 0, 1, $x, $y); }
    public function scale(float $x, float $y): void { $this->transform($x, 0, 0, $y, 0, 0); }

    public function rotate(float $angle): void
    {
        [$c, $s] = [cos($angle), sin($angle)];
        $this->transform($c, $s, -$s, $c, 0, 0);
    }

    /** Multiply the current transform by [a c e; b d f]. */
    public function transform(float $a, float $b, float $c, float $d, float $e, float $f): void
    {
        foreach ([$a, $b, $c, $d, $e, $f] as $v) if (!is_finite($v)) return;
        $this->ctm = self::multiply($this->ctm, [$a, $b, $c, $d, $e, $f]);
        $this->path->setTransform($this->ctm);
    }

    /** setTransform(a, b, c, d, e, f) or setTransform([a, b, c, d, e, f]). */
    public function setTransform(float|array $a = 1, float $b = 0, float $c = 0, float $d = 1, float $e = 0, float $f = 0): void
    {
        $m = is_array($a) ? array_map('floatval', array_values($a)) + [1, 0, 0, 1, 0, 0] : [$a, $b, $c, $d, $e, $f];
        foreach ($m as $v) if (!is_finite($v)) return;
        $this->ctm = array_slice($m, 0, 6);
        $this->path->setTransform($this->ctm);
    }

    public function resetTransform(): void { $this->setTransform(); }

    /** @return float[] [a, b, c, d, e, f] */
    public function getTransform(): array { return $this->ctm; }

    /** $a after $b. */
    private static function multiply(array $a, array $b): array
    {
        return [
            $a[0] * $b[0] + $a[2] * $b[1], $a[1] * $b[0] + $a[3] * $b[1],
            $a[0] * $b[2] + $a[2] * $b[3], $a[1] * $b[2] + $a[3] * $b[3],
            $a[0] * $b[4] + $a[2] * $b[5] + $a[4], $a[1] * $b[4] + $a[3] * $b[5] + $a[5],
        ];
    }

    private static function invert(array $m): ?array
    {
        $det = $m[0] * $m[3] - $m[1] * $m[2];
        if (abs($det) < 1e-12) return null;
        $a = $m[3] / $det; $b = -$m[1] / $det; $c = -$m[2] / $det; $d = $m[0] / $det;
        return [$a, $b, $c, $d, -($a * $m[4] + $c * $m[5]), -($b * $m[4] + $d * $m[5])];
    }

    // Paths ------------------------------------------------------------------

    public function beginPath(): void
    {
        $this->path = new \PhpSdl\Path();
        $this->path->setTransform($this->ctm);
    }

    public function closePath(): void { $this->path->closePath(); }
    public function moveTo(float $x, float $y): void { $this->path->moveTo($x, $y); }
    public function lineTo(float $x, float $y): void { $this->path->lineTo($x, $y); }
    public function quadraticCurveTo(float $cpx, float $cpy, float $x, float $y): void { $this->path->quadraticCurveTo($cpx, $cpy, $x, $y); }

    public function bezierCurveTo(float $cp1x, float $cp1y, float $cp2x, float $cp2y, float $x, float $y): void
    {
        $this->path->bezierCurveTo($cp1x, $cp1y, $cp2x, $cp2y, $x, $y);
    }

    public function arc(float $x, float $y, float $radius, float $startAngle, float $endAngle, bool $counterclockwise = false): void
    {
        $this->path->arc($x, $y, $radius, $startAngle, $endAngle, $counterclockwise);
    }

    public function arcTo(float $x1, float $y1, float $x2, float $y2, float $radius): void { $this->path->arcTo($x1, $y1, $x2, $y2, $radius); }

    public function ellipse(float $x, float $y, float $radiusX, float $radiusY, float $rotation, float $startAngle, float $endAngle, bool $counterclockwise = false): void
    {
        $this->path->ellipse($x, $y, $radiusX, $radiusY, $rotation, $startAngle, $endAngle, $counterclockwise);
    }

    public function rect(float $x, float $y, float $w, float $h): void { $this->path->rect($x, $y, $w, $h); }

    /** @param float|float[] $radii One radius, or 1 to 4 as canvas takes them. */
    public function roundRect(float $x, float $y, float $w, float $h, float|array $radii = 0): void
    {
        $this->path->roundRect($x, $y, $w, $h, $radii);
    }

    // Drawing ----------------------------------------------------------------

    /** fill(), fill('evenodd'), fill($path2d) or fill($path2d, 'evenodd'). */
    public function fill(string|Path2D $path = 'nonzero', string $fillRule = 'nonzero'): void
    {
        [$p, $rule] = $this->target($path, $fillRule);
        $this->draw($p, $this->paint($this->fillStyle), ['rule' => $rule]);
    }

    public function stroke(?Path2D $path = null): void
    {
        $p = $path ? $this->placed($path) : $this->path;
        $this->draw($p, $this->paint($this->strokeStyle), ['stroke' => $this->strokeSpec()]);
    }

    /** clip(), clip('evenodd'), clip($path2d) or clip($path2d, 'evenodd'): later drawing stays inside. */
    public function clip(string|Path2D $path = 'nonzero', string $fillRule = 'nonzero'): void
    {
        [$p, $rule] = $this->target($path, $fillRule);
        $this->clips[] = [clone $p, $rule];
    }

    public function fillRect(float $x, float $y, float $w, float $h): void
    {
        $p = $this->scratch();
        $p->rect($x, $y, $w, $h);
        $this->draw($p, $this->paint($this->fillStyle), []);
    }

    public function strokeRect(float $x, float $y, float $w, float $h): void
    {
        $p = $this->scratch();
        $p->rect($x, $y, $w, $h);
        $this->draw($p, $this->paint($this->strokeStyle), ['stroke' => $this->strokeSpec()]);
    }

    /** A canvas draws over the UI, so there is nothing to clear to: this draws nothing. */
    public function clearRect(float $x, float $y, float $w, float $h): void {}

    /** Whether ($x, $y), in the element's pixels, is inside the path. Same arguments as canvas. */
    public function isPointInPath(float|Path2D $a, float $b, float|string $c = 'nonzero', string $fillRule = 'nonzero'): bool
    {
        if ($a instanceof Path2D) return $this->placed($a)->contains($b, (float) $c, self::rule($fillRule));
        return $this->path->contains($a, $b, self::rule((string) $c));
    }

    public function isPointInStroke(float|Path2D $a, float $b, ?float $c = null): bool
    {
        [$p, $x, $y] = $a instanceof Path2D ? [$this->placed($a), $b, (float) $c] : [$this->path, $a, $b];
        // The stroke is in user units: test there.
        $inv = self::invert($this->ctm);
        if (!$inv) return false;
        $user = new \PhpSdl\Path();
        $user->addPath($p, $inv);
        return $user->containsStroke($inv[0] * $x + $inv[2] * $y + $inv[4], $inv[1] * $x + $inv[3] * $y + $inv[5], $this->strokeSpec());
    }

    public function setLineDash(array $segments): void
    {
        foreach ($segments as $s) if (!is_numeric($s) || $s < 0 || !is_finite((float) $s)) return;
        $segments = array_map('floatval', array_values($segments));
        $this->lineDash = count($segments) % 2 ? [...$segments, ...$segments] : $segments;
    }

    /** @return float[] */
    public function getLineDash(): array { return $this->lineDash; }

    // Paints -----------------------------------------------------------------

    public function createLinearGradient(float $x0, float $y0, float $x1, float $y1): CanvasGradient
    {
        return new CanvasGradient('linear', [$x0, $y0, $x1, $y1]);
    }

    public function createRadialGradient(float $x0, float $y0, float $r0, float $x1, float $y1, float $r1): CanvasGradient
    {
        if ($r0 < 0 || $r1 < 0) throw new \ValueError('A radius is negative');
        return new CanvasGradient('radial', [$x0, $y0, $r0, $x1, $y1, $r1]);
    }

    public function createConicGradient(float $startAngle, float $x, float $y): CanvasGradient
    {
        return new CanvasGradient('conic', [$startAngle, $x, $y]);
    }

    /** $image: an image file. $repetition: 'repeat', 'repeat-x', 'repeat-y' or 'no-repeat'. */
    public function createPattern(string $image, ?string $repetition = 'repeat'): CanvasPattern
    {
        return new CanvasPattern(realFile($image), $repetition === null || $repetition === '' ? 'repeat' : $repetition);
    }

    // Text -------------------------------------------------------------------

    public function fillText(string $text, float $x, float $y, ?float $maxWidth = null): void
    {
        $this->text($text, $x, $y, $maxWidth, false);
    }

    public function strokeText(string $text, float $x, float $y, ?float $maxWidth = null): void
    {
        $this->text($text, $x, $y, $maxWidth, true);
    }

    public function measureText(string $text): TextMetrics
    {
        [$font, $size] = $this->fontSpec();
        $m = \PhpSdl\Text::measure($text, $font, $size, $this->tracking($size));
        [$ascender, $descender] = \PhpSdl\Text::metrics($font, $size);
        $shift = $this->alignShift($m['width']);
        $base = $this->baselineShift($ascender, $descender);
        return new TextMetrics(
            width: $m['width'],
            actualBoundingBoxLeft: $m['left'] - $shift,
            actualBoundingBoxRight: $m['right'] + $shift,
            // Distances from the textBaseline line, which is $base above the alphabetic one.
            actualBoundingBoxAscent: $m['ascent'] - $base,
            actualBoundingBoxDescent: $m['descent'] + $base,
            fontBoundingBoxAscent: $ascender - $base,
            fontBoundingBoxDescent: -$descender + $base,
            emHeightAscent: $ascender - $base,
            emHeightDescent: -$descender + $base,
            hangingBaseline: $ascender * 0.8 - $base,
            alphabeticBaseline: -$base,
            ideographicBaseline: $descender - $base,
        );
    }

    private function text(string $text, float $x, float $y, ?float $maxWidth, bool $outline): void
    {
        if ($text === '' || ($maxWidth !== null && $maxWidth <= 0)) return;
        // Canvas draws text on one line: other whitespace becomes spaces.
        $text = preg_replace('/[\t\n\f\r]/', ' ', $text);
        [$font, $size] = $this->fontSpec();
        $tracking = $this->tracking($size);
        $width = \PhpSdl\Text::width($text, $font, $size, $tracking);
        [$ascender, $descender] = \PhpSdl\Text::metrics($font, $size);
        // Squeezed to $maxWidth, around the alignment point.
        $squeeze = $maxWidth !== null && $width > $maxWidth ? $maxWidth / $width : 1.0;
        $x0 = $x + $this->alignShift($width) * $squeeze;
        $baseline = $y + $this->baselineShift($ascender, $descender);
        $m = $this->ctm;
        $paint = $this->paint($outline ? $this->strokeStyle : $this->fillStyle);
        $plain = !$outline && $squeeze === 1.0 && !$this->clips && !$this->shadows() && count($paint) === 4 && !is_string($paint[0] ?? null)
            && $m[1] == 0 && $m[2] == 0 && $m[0] > 0 && abs($m[0] - $m[3]) < 1e-9;
        if ($plain) {
            // Text in a color, unrotated: the toolkit's glyphs, as all its text is drawn.
            $k = $m[0];
            [$fx, $fy] = [$this->base[4] + $m[0] * $x0 + $m[4], $this->base[5] + $m[3] * $baseline + $m[5]];
            $color = [$paint[0], $paint[1], $paint[2], $paint[3] * $this->globalAlpha * $this->opacity];
            $this->renderer->text($text, $font, $size * $k, $fx, $fy, $color, $tracking * $k, $this->clip);
            return;
        }
        // Anything else draws the glyphs' outlines: rotated, clipped, stroked, in any paint.
        $p = new \PhpSdl\Path();
        $p->setTransform($squeeze === 1.0 ? $m : self::multiply($m, [$squeeze, 0, 0, 1, $x0 * (1 - $squeeze), 0]));
        $p->text($text, $font, $size, $x0, $baseline, $tracking);
        $this->draw($p, $paint, $outline ? ['stroke' => $this->strokeSpec()] : []);
    }

    /** [font name, size in pixels] from the CSS font shorthand. */
    private function fontSpec(): array
    {
        static $cache = [];
        return $cache[$this->font] ??= self::parseFont($this->font);
    }

    private static function parseFont(string $css): array
    {
        $size = 10.0;
        $weight = 400;
        $family = 'sans-serif';
        if (preg_match('/^\s*((?:(?:normal|italic|oblique|small-caps|bold|bolder|lighter|[1-9]00|ultra-condensed|extra-condensed|condensed|semi-condensed|semi-expanded|expanded|extra-expanded|ultra-expanded)\s+)*)([\d.]+)(px|pt|em|rem)?(?:\s*\/\s*\S+)?\s+(.+)$/i', $css, $m)) {
            $size = (float) $m[2] * match (strtolower($m[3] ?? 'px')) { 'pt' => 4 / 3, 'em', 'rem' => 16, default => 1 };
            foreach (preg_split('/\s+/', strtolower(trim($m[1]))) as $word) {
                if ($word === 'bold' || $word === 'bolder') $weight = 700;
                elseif ($word === 'lighter') $weight = 300;
                elseif (ctype_digit($word)) $weight = (int) $word;
            }
            $family = strtolower(trim($m[4], " \"'"));
        }
        // The toolkit's fonts by name, else Geist by weight, monospace for mono families.
        $names = ['regular', 'medium', 'semibold', 'bold', 'mono', 'mono-semibold'];
        foreach (array_map('trim', explode(',', $family)) as $f) {
            $f = trim($f, "\"' ");
            if (in_array($f, $names, true)) return [$f, $size];
        }
        if (str_contains($family, 'mono') || str_contains($family, 'courier')) return [$weight >= 600 ? 'mono-semibold' : 'mono', $size];
        return [match (true) { $weight >= 700 => 'bold', $weight >= 600 => 'semibold', $weight >= 500 => 'medium', default => 'regular' }, $size];
    }

    private function tracking(float $size): float
    {
        if (!preg_match('/^\s*(-?[\d.]+)\s*(px|em)?\s*$/', $this->letterSpacing, $m)) return 0.0;
        return (float) $m[1] * (($m[2] ?? 'px') === 'em' ? $size : 1);
    }

    /** Where the text starts relative to x, for its width. */
    private function alignShift(float $width): float
    {
        $rtl = $this->direction === 'rtl';
        return match ($this->textAlign) {
            'center' => -$width / 2,
            'right' => -$width,
            'end' => $rtl ? 0.0 : -$width,
            'start' => $rtl ? -$width : 0.0,
            default => 0.0,
        };
    }

    /** Where the alphabetic baseline is relative to y. */
    private function baselineShift(float $ascender, float $descender): float
    {
        return match ($this->textBaseline) {
            'top' => $ascender,
            'hanging' => $ascender * 0.8,
            'middle' => ($ascender + $descender) / 2,
            'ideographic', 'bottom' => $descender,
            default => 0.0,
        };
    }

    // Images -----------------------------------------------------------------

    /**
     * drawImage($file, dx, dy), drawImage($file, dx, dy, dw, dh) or
     * drawImage($file, sx, sy, sw, sh, dx, dy, dw, dh): an image file, or part of it.
     */
    public function drawImage(string $image, float ...$args): void
    {
        static $sizes = [];
        $file = realFile($image);
        // Read once: games draw the same images every frame.
        [$iw, $ih] = $sizes[$file] ??= \PhpSdl\Renderer::imageSize($file);
        if ($iw <= 0 || $ih <= 0) return;
        [$sx, $sy, $sw, $sh, $dx, $dy, $dw, $dh] = match (count($args)) {
            2 => [0, 0, $iw, $ih, $args[0], $args[1], $iw, $ih],
            4 => [0, 0, $iw, $ih, ...$args],
            8 => $args,
            default => throw new \ArgumentCountError('drawImage() takes 2, 4 or 8 numbers after the image'),
        };
        if ($sw == 0 || $sh == 0 || $dw == 0 || $dh == 0) return;
        $m = $this->ctm;
        if (!$this->clips && !$this->shadows() && $m[1] == 0 && $m[2] == 0 && $m[0] > 0 && $m[3] > 0 && $dw > 0 && $dh > 0 && $sw > 0 && $sh > 0) {
            $x = $this->base[4] + $m[0] * $dx + $m[4];
            $y = $this->base[5] + $m[3] * $dy + $m[5];
            [$w, $h] = [$m[0] * $dw, $m[3] * $dh];
            $box = [$x, $y, $w, $h];
            $this->renderer->image($file, $x, $y, $w, $h, $box, [0, 0, 0, 0], $this->globalAlpha * $this->opacity, $this->clip, [$sx, $sy, $sw, $sh], $this->imageSmoothingEnabled);
            return;
        }
        // Rotated, flipped, clipped or shadowed: the destination filled with the image as a pattern.
        $p = $this->scratch();
        $p->rect($dx, $dy, $dw, $dh);
        $matrix = [$dw / $sw, 0, 0, $dh / $sh, $dx - $sx * $dw / $sw, $dy - $sy * $dh / $sh];
        $this->draw($p, ['pattern', $file, 'no-repeat', $matrix], []);
    }

    // Internals --------------------------------------------------------------

    /** A path to draw in a single call, under the current transform. */
    private function scratch(): \PhpSdl\Path
    {
        $p = new \PhpSdl\Path();
        $p->setTransform($this->ctm);
        return $p;
    }

    /** A Path2D under the current transform, in the element's pixels. */
    private function placed(Path2D $path): \PhpSdl\Path
    {
        $p = new \PhpSdl\Path();
        $p->addPath($path->path, $this->ctm);
        return $p;
    }

    private function target(string|Path2D $path, string $rule): array
    {
        return $path instanceof Path2D ? [$this->placed($path), self::rule($rule)] : [$this->path, self::rule($path)];
    }

    private static function rule(string $rule): int
    {
        return $rule === 'evenodd' ? 1 : 0;
    }

    private function strokeSpec(): array
    {
        return [$this->lineWidth, self::JOINS[$this->lineJoin] ?? 0, self::CAPS[$this->lineCap] ?? 0, $this->miterLimit, $this->lineDash, $this->lineDashOffset];
    }

    private function shadows(): bool
    {
        return ($this->shadowBlur > 0 || $this->shadowOffsetX != 0 || $this->shadowOffsetY != 0) && CssColor::parse($this->shadowColor)[3] > 0;
    }

    /** A style for PhpSdl\Renderer::path(). */
    private function paint(string|CanvasGradient|CanvasPattern $style): array
    {
        if (is_string($style)) return CssColor::parse($style);
        return $style->spec();
    }

    private function draw(\PhpSdl\Path $path, array $paint, array $style): void
    {
        $alpha = $this->globalAlpha * $this->opacity;
        if ($alpha <= 0) return;
        $style += [
            'transform' => $this->base, 'ctm' => $this->ctm, 'paint' => $paint, 'alpha' => $alpha,
            'smooth' => $this->imageSmoothingEnabled,
        ];
        if ($this->clip !== null) $style['clip'] = $this->clip;
        if ($this->clips) $style['clipPaths'] = array_map(fn ($c) => [$c[0], $this->base, $c[1]], $this->clips);
        if ($this->shadows()) $style['shadow'] = [CssColor::parse($this->shadowColor), $this->shadowBlur, $this->shadowOffsetX, $this->shadowOffsetY];
        $this->renderer->path($path, $style);
    }
}

/** A gradient from createLinearGradient(), createRadialGradient() or createConicGradient(). */
final class CanvasGradient
{
    private array $stops = [];

    /** @internal */
    public function __construct(private readonly string $type, private readonly array $geometry) {}

    public function addColorStop(float $offset, string $color): void
    {
        if ($offset < 0 || $offset > 1 || !is_finite($offset)) throw new \ValueError('A color stop offset is outside 0 to 1');
        $this->stops[] = [$offset, CssColor::parse($color)];
    }

    /** @internal */
    public function spec(): array
    {
        return [$this->type, ...$this->geometry, $this->stops];
    }
}

/** An image repeated as a paint, from createPattern(). */
final class CanvasPattern
{
    private array $matrix = [1, 0, 0, 1, 0, 0];

    /** @internal */
    public function __construct(private readonly string $file, private readonly string $repetition) {}

    /** The pattern's own transform: [a, b, c, d, e, f], or null for none. */
    public function setTransform(?array $matrix = null): void
    {
        $this->matrix = $matrix === null ? [1, 0, 0, 1, 0, 0] : array_slice(array_map('floatval', array_values($matrix)) + [1, 0, 0, 1, 0, 0], 0, 6);
    }

    /** @internal */
    public function spec(): array
    {
        return ['pattern', $this->file, $this->repetition, $this->matrix];
    }
}

/** A path to fill, stroke or clip with, as canvas's Path2D; new Path2D('M 10 10 h 80 ...') takes SVG path data. */
final class Path2D
{
    /** @internal */
    public readonly \PhpSdl\Path $path;

    public function __construct(Path2D|string|null $path = null)
    {
        $this->path = new \PhpSdl\Path();
        if ($path instanceof Path2D) $this->path->addPath($path->path);
        elseif (is_string($path)) SvgPath::parse($path, $this->path);
    }

    public function __clone()
    {
        $copy = new \PhpSdl\Path();
        $copy->addPath($this->path);
        $this->path = $copy;
    }

    /** $path under [a, b, c, d, e, f]. */
    public function addPath(Path2D $path, ?array $transform = null): void { $this->path->addPath($path->path, $transform); }
    public function closePath(): void { $this->path->closePath(); }
    public function moveTo(float $x, float $y): void { $this->path->moveTo($x, $y); }
    public function lineTo(float $x, float $y): void { $this->path->lineTo($x, $y); }
    public function quadraticCurveTo(float $cpx, float $cpy, float $x, float $y): void { $this->path->quadraticCurveTo($cpx, $cpy, $x, $y); }

    public function bezierCurveTo(float $cp1x, float $cp1y, float $cp2x, float $cp2y, float $x, float $y): void
    {
        $this->path->bezierCurveTo($cp1x, $cp1y, $cp2x, $cp2y, $x, $y);
    }

    public function arc(float $x, float $y, float $radius, float $startAngle, float $endAngle, bool $counterclockwise = false): void
    {
        $this->path->arc($x, $y, $radius, $startAngle, $endAngle, $counterclockwise);
    }

    public function arcTo(float $x1, float $y1, float $x2, float $y2, float $radius): void { $this->path->arcTo($x1, $y1, $x2, $y2, $radius); }

    public function ellipse(float $x, float $y, float $radiusX, float $radiusY, float $rotation, float $startAngle, float $endAngle, bool $counterclockwise = false): void
    {
        $this->path->ellipse($x, $y, $radiusX, $radiusY, $rotation, $startAngle, $endAngle, $counterclockwise);
    }

    public function rect(float $x, float $y, float $w, float $h): void { $this->path->rect($x, $y, $w, $h); }
    public function roundRect(float $x, float $y, float $w, float $h, float|array $radii = 0): void { $this->path->roundRect($x, $y, $w, $h, $radii); }
}

/** measureText()'s result, as canvas's TextMetrics. */
final class TextMetrics
{
    public function __construct(
        public readonly float $width,
        public readonly float $actualBoundingBoxLeft,
        public readonly float $actualBoundingBoxRight,
        public readonly float $actualBoundingBoxAscent,
        public readonly float $actualBoundingBoxDescent,
        public readonly float $fontBoundingBoxAscent,
        public readonly float $fontBoundingBoxDescent,
        public readonly float $emHeightAscent,
        public readonly float $emHeightDescent,
        public readonly float $hangingBaseline,
        public readonly float $alphabeticBaseline,
        public readonly float $ideographicBaseline,
    ) {}
}

/** @internal SVG path data (the d attribute) into a path. */
final class SvgPath
{
    public static function parse(string $d, \PhpSdl\Path $p): void
    {
        preg_match_all('/[MmLlHhVvCcSsQqTtAaZz]|[-+]?(?:\d+\.?\d*|\.\d+)(?:[eE][-+]?\d+)?/', $d, $m);
        $tokens = $m[0];
        $i = 0;
        $n = count($tokens);
        [$x, $y, $sx, $sy] = [0.0, 0.0, 0.0, 0.0];
        [$cx, $cy] = [null, null];   // last cubic control point, for S
        [$qx, $qy] = [null, null];   // last quadratic control point, for T
        $cmd = '';
        $num = function () use (&$i, $tokens, $n): ?float {
            if ($i < $n && !ctype_alpha($tokens[$i])) return (float) $tokens[$i++];
            return null;
        };
        while ($i < $n) {
            if (ctype_alpha($tokens[$i])) $cmd = $tokens[$i++];
            elseif ($cmd === '') return;
            $rel = ctype_lower($cmd);
            [$ox, $oy] = $rel ? [$x, $y] : [0.0, 0.0];
            $C = strtoupper($cmd);
            if ($C === 'Z') {
                $p->closePath();
                [$x, $y] = [$sx, $sy];
                [$cx, $cy, $qx, $qy] = [null, null, null, null];
                if ($i < $n && !ctype_alpha($tokens[$i])) $cmd = $rel ? 'l' : 'L';
                continue;
            }
            $args = ['M' => 2, 'L' => 2, 'H' => 1, 'V' => 1, 'C' => 6, 'S' => 4, 'Q' => 4, 'T' => 2, 'A' => 7][$C] ?? 0;
            $v = [];
            for ($k = 0; $k < $args; $k++) {
                $a = $num();
                if ($a === null) return; // malformed: stop, as browsers do
                $v[] = $a;
            }
            [$lastC, $lastQ] = [false, false];
            switch ($C) {
                case 'M':
                    [$x, $y] = [$ox + $v[0], $oy + $v[1]];
                    [$sx, $sy] = [$x, $y];
                    $p->moveTo($x, $y);
                    $cmd = $rel ? 'l' : 'L'; // further pairs are lines
                    break;
                case 'L': [$x, $y] = [$ox + $v[0], $oy + $v[1]]; $p->lineTo($x, $y); break;
                case 'H': $x = ($rel ? $x : 0) + $v[0]; $p->lineTo($x, $y); break;
                case 'V': $y = ($rel ? $y : 0) + $v[0]; $p->lineTo($x, $y); break;
                case 'C':
                    $p->bezierCurveTo($ox + $v[0], $oy + $v[1], $ox + $v[2], $oy + $v[3], $ox + $v[4], $oy + $v[5]);
                    [$cx, $cy, $x, $y] = [$ox + $v[2], $oy + $v[3], $ox + $v[4], $oy + $v[5]];
                    $lastC = true;
                    break;
                case 'S':
                    [$c1x, $c1y] = $cx !== null ? [2 * $x - $cx, 2 * $y - $cy] : [$x, $y];
                    $p->bezierCurveTo($c1x, $c1y, $ox + $v[0], $oy + $v[1], $ox + $v[2], $oy + $v[3]);
                    [$cx, $cy, $x, $y] = [$ox + $v[0], $oy + $v[1], $ox + $v[2], $oy + $v[3]];
                    $lastC = true;
                    break;
                case 'Q':
                    $p->quadraticCurveTo($ox + $v[0], $oy + $v[1], $ox + $v[2], $oy + $v[3]);
                    [$qx, $qy, $x, $y] = [$ox + $v[0], $oy + $v[1], $ox + $v[2], $oy + $v[3]];
                    $lastQ = true;
                    break;
                case 'T':
                    [$qx, $qy] = $qx !== null ? [2 * $x - $qx, 2 * $y - $qy] : [$x, $y];
                    [$x, $y] = [$ox + $v[0], $oy + $v[1]];
                    $p->quadraticCurveTo($qx, $qy, $x, $y);
                    $lastQ = true;
                    break;
                case 'A':
                    [$ex, $ey] = [$ox + $v[5], $oy + $v[6]];
                    self::arc($p, $x, $y, $v[0], $v[1], $v[2], (bool) $v[3], (bool) $v[4], $ex, $ey);
                    [$x, $y] = [$ex, $ey];
                    break;
            }
            if (!$lastC) [$cx, $cy] = [null, null];
            if (!$lastQ) [$qx, $qy] = [null, null];
        }
    }

    /** SVG's endpoint arc as a canvas ellipse (W3C SVG implementation notes, F.6.5). */
    private static function arc(\PhpSdl\Path $p, float $x1, float $y1, float $rx, float $ry, float $deg, bool $large, bool $sweep, float $x2, float $y2): void
    {
        if ($x1 == $x2 && $y1 == $y2) return;
        [$rx, $ry] = [abs($rx), abs($ry)];
        if ($rx == 0 || $ry == 0) { $p->lineTo($x2, $y2); return; }
        $phi = deg2rad(fmod($deg, 360));
        [$c, $s] = [cos($phi), sin($phi)];
        $dx = ($x1 - $x2) / 2;
        $dy = ($y1 - $y2) / 2;
        $x1p = $c * $dx + $s * $dy;
        $y1p = -$s * $dx + $c * $dy;
        $lambda = ($x1p * $x1p) / ($rx * $rx) + ($y1p * $y1p) / ($ry * $ry);
        if ($lambda > 1) { $rx *= sqrt($lambda); $ry *= sqrt($lambda); }
        $num = $rx * $rx * $ry * $ry - $rx * $rx * $y1p * $y1p - $ry * $ry * $x1p * $x1p;
        $den = $rx * $rx * $y1p * $y1p + $ry * $ry * $x1p * $x1p;
        $k = $den > 0 ? sqrt(max(0, $num / $den)) : 0;
        if ($large === $sweep) $k = -$k;
        $cxp = $k * $rx * $y1p / $ry;
        $cyp = -$k * $ry * $x1p / $rx;
        $cx = $c * $cxp - $s * $cyp + ($x1 + $x2) / 2;
        $cy = $s * $cxp + $c * $cyp + ($y1 + $y2) / 2;
        $a1 = atan2(($y1p - $cyp) / $ry, ($x1p - $cxp) / $rx);
        $a2 = atan2((-$y1p - $cyp) / $ry, (-$x1p - $cxp) / $rx);
        $delta = $a2 - $a1;
        if ($sweep && $delta < 0) $delta += 2 * M_PI;
        if (!$sweep && $delta > 0) $delta -= 2 * M_PI;
        $p->ellipse($cx, $cy, $rx, $ry, $phi, $a1, $a1 + $delta, !$sweep);
    }
}

/** @internal CSS colors as canvas takes them: hex, rgb(), hsl(), names, 'transparent'. */
final class CssColor
{
    private static array $cache = [];

    /** @return float[] [r, g, b, a], 0 to 1, straight alpha; unparseable colors are black. */
    public static function parse(string $css): array
    {
        return self::$cache[$css] ??= self::read(strtolower(trim($css))) ?? [0.0, 0.0, 0.0, 1.0];
    }

    private static function read(string $c): ?array
    {
        if ($c === 'transparent') return [0.0, 0.0, 0.0, 0.0];
        if ($c !== '' && $c[0] === '#') {
            $h = substr($c, 1);
            if (!ctype_xdigit($h)) return null;
            if (strlen($h) === 3 || strlen($h) === 4) $h = implode('', array_map(fn ($ch) => $ch . $ch, str_split($h)));
            if (strlen($h) === 6) $h .= 'ff';
            if (strlen($h) !== 8) return null;
            return array_map(fn ($b) => hexdec($b) / 255.0, str_split($h, 2));
        }
        if (preg_match('/^(rgba?|hsla?)\((.*)\)$/', $c, $m)) {
            $parts = preg_split('/\s*[,\/]\s*|\s+/', trim($m[2]));
            if (count($parts) < 3) return null;
            $alpha = isset($parts[3]) ? self::number($parts[3], 1) : 1.0;
            if (str_starts_with($m[1], 'rgb')) {
                return [self::number($parts[0], 255), self::number($parts[1], 255), self::number($parts[2], 255), $alpha];
            }
            $h = fmod(fmod((float) $parts[0], 360) + 360, 360) / 360;
            [$s, $l] = [self::number($parts[1], 100), self::number($parts[2], 100)];
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $f = function (float $t) use ($p, $q): float {
                $t = $t < 0 ? $t + 1 : ($t > 1 ? $t - 1 : $t);
                return match (true) { $t < 1 / 6 => $p + ($q - $p) * 6 * $t, $t < 1 / 2 => $q, $t < 2 / 3 => $p + ($q - $p) * (2 / 3 - $t) * 6, default => $p };
            };
            return [$f($h + 1 / 3), $f($h), $f($h - 1 / 3), $alpha];
        }
        $named = self::NAMES[$c] ?? null;
        return $named === null ? null : self::read('#' . $named);
    }

    /** '50%' or a number out of $scale, clamped to 0..1. */
    private static function number(string $v, float $scale): float
    {
        $n = str_ends_with($v, '%') ? (float) $v / 100 : (float) $v / $scale;
        return max(0.0, min(1.0, $n));
    }

    private const NAMES = [
        'aliceblue' => 'f0f8ff', 'antiquewhite' => 'faebd7', 'aqua' => '00ffff', 'aquamarine' => '7fffd4', 'azure' => 'f0ffff',
        'beige' => 'f5f5dc', 'bisque' => 'ffe4c4', 'black' => '000000', 'blanchedalmond' => 'ffebcd', 'blue' => '0000ff',
        'blueviolet' => '8a2be2', 'brown' => 'a52a2a', 'burlywood' => 'deb887', 'cadetblue' => '5f9ea0', 'chartreuse' => '7fff00',
        'chocolate' => 'd2691e', 'coral' => 'ff7f50', 'cornflowerblue' => '6495ed', 'cornsilk' => 'fff8dc', 'crimson' => 'dc143c',
        'cyan' => '00ffff', 'darkblue' => '00008b', 'darkcyan' => '008b8b', 'darkgoldenrod' => 'b8860b', 'darkgray' => 'a9a9a9',
        'darkgreen' => '006400', 'darkgrey' => 'a9a9a9', 'darkkhaki' => 'bdb76b', 'darkmagenta' => '8b008b', 'darkolivegreen' => '556b2f',
        'darkorange' => 'ff8c00', 'darkorchid' => '9932cc', 'darkred' => '8b0000', 'darksalmon' => 'e9967a', 'darkseagreen' => '8fbc8f',
        'darkslateblue' => '483d8b', 'darkslategray' => '2f4f4f', 'darkslategrey' => '2f4f4f', 'darkturquoise' => '00ced1',
        'darkviolet' => '9400d3', 'deeppink' => 'ff1493', 'deepskyblue' => '00bfff', 'dimgray' => '696969', 'dimgrey' => '696969',
        'dodgerblue' => '1e90ff', 'firebrick' => 'b22222', 'floralwhite' => 'fffaf0', 'forestgreen' => '228b22', 'fuchsia' => 'ff00ff',
        'gainsboro' => 'dcdcdc', 'ghostwhite' => 'f8f8ff', 'gold' => 'ffd700', 'goldenrod' => 'daa520', 'gray' => '808080',
        'green' => '008000', 'greenyellow' => 'adff2f', 'grey' => '808080', 'honeydew' => 'f0fff0', 'hotpink' => 'ff69b4',
        'indianred' => 'cd5c5c', 'indigo' => '4b0082', 'ivory' => 'fffff0', 'khaki' => 'f0e68c', 'lavender' => 'e6e6fa',
        'lavenderblush' => 'fff0f5', 'lawngreen' => '7cfc00', 'lemonchiffon' => 'fffacd', 'lightblue' => 'add8e6', 'lightcoral' => 'f08080',
        'lightcyan' => 'e0ffff', 'lightgoldenrodyellow' => 'fafad2', 'lightgray' => 'd3d3d3', 'lightgreen' => '90ee90', 'lightgrey' => 'd3d3d3',
        'lightpink' => 'ffb6c1', 'lightsalmon' => 'ffa07a', 'lightseagreen' => '20b2aa', 'lightskyblue' => '87cefa', 'lightslategray' => '778899',
        'lightslategrey' => '778899', 'lightsteelblue' => 'b0c4de', 'lightyellow' => 'ffffe0', 'lime' => '00ff00', 'limegreen' => '32cd32',
        'linen' => 'faf0e6', 'magenta' => 'ff00ff', 'maroon' => '800000', 'mediumaquamarine' => '66cdaa', 'mediumblue' => '0000cd',
        'mediumorchid' => 'ba55d3', 'mediumpurple' => '9370db', 'mediumseagreen' => '3cb371', 'mediumslateblue' => '7b68ee',
        'mediumspringgreen' => '00fa9a', 'mediumturquoise' => '48d1cc', 'mediumvioletred' => 'c71585', 'midnightblue' => '191970',
        'mintcream' => 'f5fffa', 'mistyrose' => 'ffe4e1', 'moccasin' => 'ffe4b5', 'navajowhite' => 'ffdead', 'navy' => '000080',
        'oldlace' => 'fdf5e6', 'olive' => '808000', 'olivedrab' => '6b8e23', 'orange' => 'ffa500', 'orangered' => 'ff4500',
        'orchid' => 'da70d6', 'palegoldenrod' => 'eee8aa', 'palegreen' => '98fb98', 'paleturquoise' => 'afeeee', 'palevioletred' => 'db7093',
        'papayawhip' => 'ffefd5', 'peachpuff' => 'ffdab9', 'peru' => 'cd853f', 'pink' => 'ffc0cb', 'plum' => 'dda0dd',
        'powderblue' => 'b0e0e6', 'purple' => '800080', 'rebeccapurple' => '663399', 'red' => 'ff0000', 'rosybrown' => 'bc8f8f',
        'royalblue' => '4169e1', 'saddlebrown' => '8b4513', 'salmon' => 'fa8072', 'sandybrown' => 'f4a460', 'seagreen' => '2e8b57',
        'seashell' => 'fff5ee', 'sienna' => 'a0522d', 'silver' => 'c0c0c0', 'skyblue' => '87ceeb', 'slateblue' => '6a5acd',
        'slategray' => '708090', 'slategrey' => '708090', 'snow' => 'fffafa', 'springgreen' => '00ff7f', 'steelblue' => '4682b4',
        'tan' => 'd2b48c', 'teal' => '008080', 'thistle' => 'd8bfd8', 'tomato' => 'ff6347', 'turquoise' => '40e0d0',
        'violet' => 'ee82ee', 'wheat' => 'f5deb3', 'white' => 'ffffff', 'whitesmoke' => 'f5f5f5', 'yellow' => 'ffff00',
        'yellowgreen' => '9acd32',
    ];
}
