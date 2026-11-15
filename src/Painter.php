<?php

declare(strict_types=1);

namespace PhpUi;

use GL\VectorGraphics\SVGImage;
use GL\VectorGraphics\VGAlign;
use GL\VectorGraphics\VGColor;
use GL\VectorGraphics\VGContext;

/**
 * Draws a laid-out tree. Three renderers, for comparison:
 *
 * - 'nanovg':      everything through NanoVG; shadows are shapes with a hole (stencil fill).
 * - 'nanovg-fast': NanoVG, but shadows are plain convex fills (no stencil) and
 *                  state is only saved where an element needs it.
 * - 'quads':       boxes, borders and shadows as instanced quads drawn by our own
 *                  shader (QuadRenderer), one draw call per layer; text, icons and
 *                  canvases through NanoVG on top.
 *
 * With quads, each layer (the tree, then each overlay) draws its boxes first and
 * its text after, as GPUI does: within a layer, text is never covered by a box.
 */
final class Painter
{
    public static ?self $shared = null;
    public VGContext $vg;
    public string $mode;
    private ?QuadRenderer $quads = null;
    private array $colors = [];
    private array $floats = [];
    private array $icons = [];
    /** @var array<string, \GL\VectorGraphics\VGImage> Icons rasterized once, by name, color and pixel size. */
    private array $iconImages = [];
    private int $iconFbo = 0;
    private int $iconFboSize = 0;
    /** The framebuffer the frame draws to (0 for the window, or a screenshot's). */
    public int $target = 0;
    /** @var Element[] Absolute elements, drawn after the layer that holds them. */
    private array $overlays = [];
    private string $iconDir;
    private bool $caretOn = true;
    private float $ratio = 1.0;
    /** Current clip rectangle and opacity, for the quad pass. */
    private array $clip = [];
    private float $alpha = 1.0;

    /** Tailwind shadows as [offsetY, blur, spread, alpha] layers. */
    private const SHADOWS = [
        'xs' => [[1, 2, 0, 0.05]],
        'sm' => [[1, 3, 0, 0.1], [1, 2, -1, 0.1]],
        'md' => [[4, 6, -1, 0.1], [2, 4, -2, 0.1]],
        'lg' => [[10, 15, -3, 0.1], [4, 6, -4, 0.1]],
    ];
    private const NO_CLIP = [-1e6, -1e6, 2e6, 2e6];

    public function __construct(?string $mode = null)
    {
        $this->mode = $mode ?? (getenv('PHPUI_RENDERER') ?: 'quads');
        $this->vg = new VGContext(VGContext::ANTIALIAS);
        $assets = dirname(__DIR__) . '/assets';
        $this->iconDir = "$assets/icons";
        foreach ([
            'regular' => 'Geist-Regular', 'medium' => 'Geist-Medium', 'semibold' => 'Geist-SemiBold',
            'bold' => 'Geist-Bold', 'mono' => 'GeistMono-Regular', 'mono-semibold' => 'GeistMono-SemiBold',
        ] as $name => $file) {
            if ($this->vg->createFont($name, realFile("$assets/fonts/$file.ttf")) < 0) {
                throw new \RuntimeException("Cannot load font $file");
            }
        }
        self::$shared = $this;
    }

    public function textWidth(string $text, string $font, float $size, float $tracking = 0): float
    {
        if ($text === '') return 0;
        $this->vg->fontFace($font);
        $this->vg->fontSize($size);
        $this->vg->textLetterSpacing($tracking);
        return $this->vg->textBounds(0, 0, $text);
    }

    /** Wrap at spaces and explicit newlines; split oversized words by Unicode character. */
    public function wrap(string $text, float $width, string $font = 'regular', float $size = 14): array
    {
        if ($width <= 0) throw new \InvalidArgumentException('Wrap width must be positive');
        $lines = [];
        foreach (explode("\n", str_replace(["\r\n", "\r"], "\n", $text)) as $paragraph) {
            $line = '';
            foreach (preg_split('/[\t ]+/u', trim($paragraph), -1, PREG_SPLIT_NO_EMPTY) as $word) {
                $next = $line === '' ? $word : "$line $word";
                if ($this->textWidth($next, $font, $size) <= $width) { $line = $next; continue; }
                if ($line !== '') { $lines[] = $line; $line = ''; }
                foreach (mb_str_split($word) as $char) {
                    if ($line !== '' && $this->textWidth($line . $char, $font, $size) > $width) {
                        $lines[] = $line;
                        $line = '';
                    }
                    $line .= $char;
                }
            }
            $lines[] = $line;
        }
        return $lines;
    }

    public function lineHeight(float $size): float
    {
        return round($size * 1.43);
    }

    /** Draw a whole frame of $w×$h logical pixels. */
    public function render(Element $root, float $w, float $h, float $ratio, bool $caretOn): void
    {
        $this->caretOn = $caretOn;
        $this->overlays = [];
        $this->ratio = $ratio;
        if ($this->mode !== 'nanovg') $this->rasterizeIcons($root, $ratio, $w, $h);

        if ($this->mode !== 'quads') {
            $this->vg->beginFrame($w, $h, $ratio);
            $this->walk($root, 'all');
            // Overlays may hold overlays of their own: draw until none are left.
            for ($i = 0; $i < count($this->overlays); $i++) {
                $this->vg->save();
                $this->vg->resetScissor();
                $this->walk($this->overlays[$i], 'all', true);
                $this->vg->restore();
            }
            $this->vg->endFrame();
            return;
        }

        $this->quads ??= new QuadRenderer();
        $layers = [$root];
        for ($i = 0; $i < count($layers); $i++) {
            $this->overlays = [];
            $this->clip = self::NO_CLIP;
            $this->alpha = 1.0;
            $this->quads->begin();
            $this->walk($layers[$i], 'quads', $i > 0);
            $this->quads->flush($w, $h, $ratio);
            array_push($layers, ...$this->overlays);

            $this->vg->beginFrame($w, $h, $ratio);
            $this->alpha = 1.0;
            $this->walk($layers[$i], 'vg', $i > 0);
            $this->vg->endFrame();
        }
    }

    /** Pass 'all' draws everything with NanoVG; 'quads' only boxes; 'vg' only text, icons and canvases. */
    private function walk(Element $e, string $pass, bool $asOverlay = false): void
    {
        if ($e->isAbsolute() && !$asOverlay) {
            if ($pass !== 'vg' && empty($e->style['hidden'])) $this->overlays[] = $e;
            return;
        }
        if (!empty($e->style['hidden'])) return;

        $vg = $this->vg;
        $s = $e->paintStyle();
        $clips = !empty($s['clip']);
        $save = $this->mode === 'nanovg' || isset($s['opacity']) || $clips;
        [$clip, $alpha] = [$this->clip, $this->alpha];
        if (isset($s['opacity'])) $this->alpha *= $s['opacity'];
        if ($save && $pass !== 'quads') {
            $vg->save();
            if (isset($s['opacity'])) $vg->globalAlpha($this->alpha);
        }

        if ($pass === 'quads') {
            $this->boxQuads($e, $s);
        } else {
            if ($pass === 'all') $this->boxVg($e, $s);
            if (isset($s['icon'])) $this->icon($e, ...$s['icon']);
            if ($e->kind === 'text') {
                $this->text($e, $e->text, $e->x, $e->w, $s['color'] ?? $e->color, $s);
            } elseif ($e->kind === 'input') {
                $this->input($e, $s);
            } elseif ($e->kind === 'canvas' && $e->draw) {
                $vg->save();
                ($e->draw)($vg, $e, $this);
                $vg->restore();
            }
        }

        if ($clips) {
            if ($pass === 'quads') $this->clip = $this->intersect($this->clip, [$e->x, $e->y, $e->w, $e->h]);
            else $vg->intersectScissor($e->x, $e->y, $e->w, $e->h);
        }
        foreach ($e->children as $c) $this->walk($c, $pass);
        if (!empty($s['scroll']) && $e->contentH > $e->h && $pass !== 'vg') $this->scrollbar($e, $pass);

        if ($save && $pass !== 'quads') $vg->restore();
        [$this->clip, $this->alpha] = [$clip, $alpha];
    }

    // Boxes as quads -----------------------------------------------------------

    private function boxQuads(Element $e, array $s): void
    {
        $r = $s['radius'] ?? 0;
        $radii = is_array($r) ? $r : [$r, $r, $r, $r];
        $none = [0, 0, 0, 0];

        if (isset($s['shadow'])) {
            $rr = max($radii);
            $strength = Theme::dark() ? 3.0 : 1.0;
            foreach (self::SHADOWS[$s['shadow']] ?? [] as [$oy, $blur, $spread, $a]) {
                $color = [0, 0, 0, min(1, $a * $strength) * $this->alpha];
                $rect = [$e->x - $spread, $e->y + $oy - $spread, $e->w + 2 * $spread, $e->h + 2 * $spread];
                $this->quads->add($rect, array_fill(0, 4, $rr + $spread), $color, $color, $none, 0, $blur, $this->clip);
            }
        }

        $hasFill = isset($s['bg']) || isset($s['gradient']);
        $bw = $s['borderW'] ?? 0;
        if ($hasFill || $bw > 0) {
            [$top, $bottom] = isset($s['gradient'])
                ? [$this->rgba($s['gradient'][0]), $this->rgba($s['gradient'][1])]
                : (isset($s['bg']) ? array_fill(0, 2, $this->rgba($s['bg'])) : [$none, $none]);
            $border = $bw > 0 ? $this->rgba($s['borderColor'] ?? '#00000020') : $none;
            $this->quads->add([$e->x, $e->y, $e->w, $e->h], $radii, $top, $bottom, $border, $bw, 0, $this->clip);
        }

        foreach ($s['sides'] ?? [] as $side => [$sw, $color]) {
            $rect = match ($side) {
                't' => [$e->x, $e->y, $e->w, $sw],
                'b' => [$e->x, $e->y + $e->h - $sw, $e->w, $sw],
                'l' => [$e->x, $e->y, $sw, $e->h],
                'r' => [$e->x + $e->w - $sw, $e->y, $sw, $e->h],
            };
            $c = $this->rgba($color);
            $this->quads->add($rect, $none, $c, $c, $none, 0, 0, $this->clip);
        }

        if (isset($s['ring'])) {
            [$rw, $rc] = $s['ring'];
            $this->quads->add([$e->x - $rw, $e->y - $rw, $e->w + 2 * $rw, $e->h + 2 * $rw],
                array_map(fn ($v) => $v + $rw, $radii), $none, $none, $this->rgba($rc), $rw, 0, $this->clip);
        }
    }

    // Boxes through NanoVG ------------------------------------------------------

    private function boxVg(Element $e, array $s): void
    {
        $vg = $this->vg;
        $r = $s['radius'] ?? 0;

        if (isset($s['shadow'])) $this->shadow($e, $r, $s['shadow']);
        if (isset($s['bg']) || isset($s['gradient'])) {
            $this->shape($e->x, $e->y, $e->w, $e->h, $r);
            if (isset($s['gradient'])) {
                $vg->fillPaint($vg->linearGradient($e->x, $e->y, $e->x, $e->y + $e->h, $this->color($s['gradient'][0]), $this->color($s['gradient'][1])));
            } else {
                $vg->fillColor($this->color($s['bg']));
            }
            $vg->fill();
        }
        if (isset($s['borderW']) && $s['borderW'] > 0) {
            $bw = $s['borderW'];
            $this->shape($e->x + $bw / 2, $e->y + $bw / 2, $e->w - $bw, $e->h - $bw, $this->inset($r, $bw / 2));
            $vg->strokeWidth($bw);
            $vg->strokeColor($this->color($s['borderColor'] ?? '#00000020'));
            $vg->stroke();
        }
        foreach ($s['sides'] ?? [] as $side => [$bw, $color]) {
            $vg->beginPath();
            match ($side) {
                't' => $vg->rect($e->x, $e->y, $e->w, $bw),
                'b' => $vg->rect($e->x, $e->y + $e->h - $bw, $e->w, $bw),
                'l' => $vg->rect($e->x, $e->y, $bw, $e->h),
                'r' => $vg->rect($e->x + $e->w - $bw, $e->y, $bw, $e->h),
            };
            $vg->fillColor($this->color($color));
            $vg->fill();
        }
        if (isset($s['ring'])) {
            [$rw, $rc] = $s['ring'];
            $this->shape($e->x - $rw / 2, $e->y - $rw / 2, $e->w + $rw, $e->h + $rw, $this->inset($r, -$rw / 2));
            $vg->strokeWidth($rw);
            $vg->strokeColor($this->color($rc));
            $vg->stroke();
        }
    }

    private function shadow(Element $e, float|array $r, string $size): void
    {
        $vg = $this->vg;
        $rr = is_array($r) ? max($r) : $r;
        $strength = Theme::dark() ? 3.0 : 1.0; // dark surfaces need a stronger shadow to show at all
        foreach (self::SHADOWS[$size] ?? [] as [$oy, $blur, $spread, $alpha]) {
            [$x, $y, $w, $h] = [$e->x - $spread, $e->y + $oy - $spread, $e->w + 2 * $spread, $e->h + 2 * $spread];
            $paint = $vg->boxGradient($x, $y + $blur / 4, $w, $h, $rr + $blur / 2, $blur,
                VGColor::rgba(0, 0, 0, min(1, $alpha * $strength)), VGColor::rgba(0, 0, 0, 0));
            $vg->beginPath();
            $vg->rect($x - $blur * 2, $y - $blur * 2, $w + $blur * 4, $h + $blur * 4);
            if ($this->mode === 'nanovg') {
                // A hole under the box makes two paths: NanoVG fills them through the stencil buffer.
                $vg->roundedRect($e->x, $e->y, $e->w, $e->h, $rr);
                $vg->pathWinding(VGContext::HOLE);
            }
            $vg->fillPaint($paint);
            $vg->fill();
        }
    }

    private function scrollbar(Element $e, string $pass): void
    {
        $track = $e->h - 8;
        $thumb = max(24, $track * $e->h / $e->contentH);
        $y = $e->y + 4 + ($track - $thumb) * $e->scrollY / max(1, $e->contentH - $e->h);
        if ($pass === 'quads') {
            $c = $this->rgba(t('border'));
            $this->quads->add([$e->x + $e->w - 7, $y, 4, $thumb], [2, 2, 2, 2], $c, $c, [0, 0, 0, 0], 0, 0, $this->clip);
            return;
        }
        $this->vg->beginPath();
        $this->vg->roundedRect($e->x + $e->w - 7, $y, 4, $thumb, 2);
        $this->vg->fillColor($this->color(t('border')));
        $this->vg->fill();
    }

    // Text and icons ------------------------------------------------------------

    private function input(Element $e, array $s): void
    {
        $vg = $this->vg;
        $ix = $e->x + ($s['pl'] ?? 0);
        $iw = $e->w - ($s['pl'] ?? 0) - ($s['pr'] ?? 0);
        $vg->save();
        $vg->intersectScissor($e->x, $e->y, $e->w, $e->h);
        if ($e->text === '' && $e->placeholder !== '') {
            $this->text($e, $e->placeholder, $ix, $iw, $s['placeholder'] ?? t('muted-foreground'), $s);
        } else {
            $this->text($e, $e->text, $ix, $iw, $s['color'] ?? $e->color, ['truncate' => false] + $s);
        }
        if ($e->focused && $this->caretOn) {
            $cx = $ix + $this->textWidth($e->text, $e->font, $e->size) + 1;
            $ch = round($e->size * 1.15);
            $vg->beginPath();
            $vg->rect($cx, $e->y + ($e->h - $ch) / 2, 1, $ch);
            $vg->fillColor($this->color($s['color'] ?? $e->color));
            $vg->fill();
        }
        $vg->restore();
    }

    /** Draw one line of text, vertically centered in the element. */
    public function text(Element $e, string $text, float $x, float $w, string $color, array $s = []): void
    {
        $vg = $this->vg;
        $tracking = $s['tracking'] ?? 0;
        if (!empty($s['truncate']) && $this->textWidth($text, $e->font, $e->size, $tracking) > $w + 0.5) {
            $text = $this->ellipsize($text, $e->font, $e->size, $tracking, $w);
        }
        $vg->fontFace($e->font);
        $vg->fontSize($e->size);
        $vg->textLetterSpacing($tracking);
        $vg->fillColor($this->color($color));
        $align = $s['textAlign'] ?? 'left';
        $vg->textAlign(match ($align) { 'center' => VGAlign::CENTER, 'right' => VGAlign::RIGHT, default => VGAlign::LEFT } | VGAlign::MIDDLE);
        $tx = match ($align) { 'center' => $x + $w / 2, 'right' => $x + $w, default => $x };
        $vg->text($tx, $e->y + $e->h / 2, $text);
    }

    private function ellipsize(string $text, string $font, float $size, float $tracking, float $w): string
    {
        [$lo, $hi] = [0, mb_strlen($text)];
        while ($lo < $hi) {
            $mid = intdiv($lo + $hi + 1, 2);
            $this->textWidth(rtrim(mb_substr($text, 0, $mid)) . '…', $font, $size, $tracking) <= $w ? $lo = $mid : $hi = $mid - 1;
        }
        return rtrim(mb_substr($text, 0, $lo)) . '…';
    }

    /** A Lucide icon, recolored, scaled to $size and centered in the element. */
    private function icon(Element $e, string $name, string $color, float $size): void
    {
        $key = "$name|$color";
        $this->svg($name, $color);
        $vg = $this->vg;
        $x = $e->x + ($e->w - $size) / 2;
        $y = $e->y + ($e->h - $size) / 2;
        $image = $this->mode === 'nanovg' ? null : ($this->iconImages[$this->iconKey($name, $color, $size, $this->ratio)] ?? null);
        if ($image !== null) {
            // A cached texture: one textured quad, no stencil work.
            $vg->beginPath();
            $vg->rect($x, $y, $size, $size);
            $vg->fillPaint($image->makePaint($x, $y, $size, $size));
            $vg->fill();
            return;
        }
        // drawSVG saves and restores NanoVG's clip around every call: two full-screen stencil passes each.
        $vg->lineCap(VGContext::LINECAP_ROUND);
        $vg->lineJoin(VGContext::LINECAP_ROUND); // NanoVG's ROUND join shares this value
        $vg->drawSVG($this->icons[$key], $x, $y, $size, $size);
    }

    private function iconKey(string $name, string $color, float $size, float $ratio): string
    {
        return "$name|$color|" . (int) round($size * $ratio);
    }

    /** Rasterize icons the tree uses and the cache lacks, each once, into textures. */
    private function rasterizeIcons(Element $root, float $ratio, float $w, float $h): void
    {
        $missing = [];
        $collect = function (Element $e) use (&$collect, &$missing, $ratio) {
            foreach ([$e->style['icon'] ?? null, $e->hoverStyle['icon'] ?? null] as $icon) {
                if ($icon && !isset($this->iconImages[$this->iconKey($icon[0], $icon[1], $icon[2] ?? 16, $ratio)])) {
                    $missing[$this->iconKey($icon[0], $icon[1], $icon[2] ?? 16, $ratio)] = [$icon[0], $icon[1], $icon[2] ?? 16];
                }
            }
            foreach ($e->children as $c) $collect($c);
        };
        $collect($root);
        if (!$missing) return;

        $vg = $this->vg;
        foreach ($missing as $key => [$name, $color, $size]) {
            $px = max(1, (int) round($size * $ratio));
            $this->bindIconFbo($px);
            glViewport(0, 0, $px, $px);
            // Clear to the icon's color at zero alpha: blending then leaves straight (not premultiplied) color, as NanoVG images expect.
            [$r, $g, $b] = array_map(fn ($v) => $v / 255, array_slice($this->bytes($color), 0, 3));
            glClearColor($r, $g, $b, 0);
            glClear(GL_COLOR_BUFFER_BIT | GL_STENCIL_BUFFER_BIT);
            $vg->beginFrame($px, $px, 1);
            // Draw upside down: glReadPixels returns rows bottom first, so the texture comes out upright.
            $vg->translate(0, $px);
            $vg->scale(1, -1);
            $this->svg($name, $color);
            $vg->lineCap(VGContext::LINECAP_ROUND);
            $vg->lineJoin(VGContext::LINECAP_ROUND);
            $vg->drawSVG($this->icons["$name|$color"], 0, 0, $px, $px);
            $vg->endFrame();
            $pixels = new \GL\Buffer\UByteBuffer();
            glReadPixels(0, 0, $px, $px, GL_RGBA, GL_UNSIGNED_BYTE, $pixels);
            $texture = \GL\Texture\Texture2D::fromBuffer($px, $px, $pixels, \GL\Texture\Texture2D::CHANNEL_RGBA);
            $this->iconImages[$key] = $vg->imageFromTexture($texture);
        }
        glBindFramebuffer(GL_FRAMEBUFFER, $this->target);
        glViewport(0, 0, (int) round($w * $ratio), (int) round($h * $ratio));
    }

    private function bindIconFbo(int $px): void
    {
        if ($px > $this->iconFboSize) {
            if ($this->iconFbo) glDeleteFramebuffers(1, $this->iconFbo);
            $size = max(64, $px);
            glGenFramebuffers(1, $fbo);
            glGenRenderbuffers(2, $color, $stencil);
            glBindRenderbuffer(GL_RENDERBUFFER, $color);
            glRenderbufferStorage(GL_RENDERBUFFER, GL_RGBA8, $size, $size);
            glBindRenderbuffer(GL_RENDERBUFFER, $stencil);
            glRenderbufferStorage(GL_RENDERBUFFER, GL_DEPTH24_STENCIL8, $size, $size);
            glBindFramebuffer(GL_FRAMEBUFFER, $fbo);
            glFramebufferRenderbuffer(GL_FRAMEBUFFER, GL_COLOR_ATTACHMENT0, GL_RENDERBUFFER, $color);
            glFramebufferRenderbuffer(GL_FRAMEBUFFER, GL_DEPTH_STENCIL_ATTACHMENT, GL_RENDERBUFFER, $stencil);
            [$this->iconFbo, $this->iconFboSize] = [$fbo, $size];
        }
        glBindFramebuffer(GL_FRAMEBUFFER, $this->iconFbo);
    }

    /** Parse an icon's SVG, recolored, once. */
    private function svg(string $name, string $color): void
    {
        $key = "$name|$color";
        if (isset($this->icons[$key])) return;
        $svg = @file_get_contents("{$this->iconDir}/$name.svg");
        if ($svg === false) throw new \InvalidArgumentException("No icon named $name in assets/icons");
        [$hex, $alpha] = $this->splitAlpha($color);
        $svg = str_replace('stroke="currentColor"', "stroke=\"$hex\" stroke-opacity=\"$alpha\"", $svg);
        $svg = str_replace('fill="currentColor"', "fill=\"$hex\" fill-opacity=\"$alpha\"", $svg);
        $this->icons[$key] = SVGImage::fromString($svg);
    }

    // Helpers -------------------------------------------------------------------

    /** Begin a path for a box with one radius or four (tl, tr, br, bl). */
    public function shape(float $x, float $y, float $w, float $h, float|array $r): void
    {
        $this->vg->beginPath();
        is_array($r)
            ? $this->vg->roundedRectVarying($x, $y, $w, $h, ...$r)
            : $this->vg->roundedRect($x, $y, $w, $h, $r);
    }

    private function inset(float|array $r, float $by): float|array
    {
        return is_array($r) ? array_map(fn ($v) => max(0, $v - $by), $r) : max(0, $r - $by);
    }

    private function intersect(array $a, array $b): array
    {
        $x = max($a[0], $b[0]);
        $y = max($a[1], $b[1]);
        return [$x, $y, max(0, min($a[0] + $a[2], $b[0] + $b[2]) - $x), max(0, min($a[1] + $a[3], $b[1] + $b[3]) - $y)];
    }

    /** Split '#rrggbbaa' into '#rrggbb' and an opacity, for SVG. */
    private function splitAlpha(string $hex): array
    {
        $h = ltrim($hex, '#');
        if (strlen($h) === 3) $h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
        return ['#' . substr($h, 0, 6), strlen($h) === 8 ? round(hexdec(substr($h, 6)) / 255, 3) : 1];
    }

    /** A color as [r, g, b, a] floats, with the current opacity applied. */
    private function rgba(string $hex): array
    {
        $c = $this->floats[$hex] ??= array_map(fn ($v) => $v / 255, $this->bytes($hex));
        if ($this->alpha < 1) $c[3] *= $this->alpha;
        return $c;
    }

    private function bytes(string $hex): array
    {
        $h = ltrim($hex, '#');
        if (strlen($h) === 3) $h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
        if (strlen($h) === 6) $h .= 'ff';
        return array_map('hexdec', str_split($h, 2));
    }

    /** '#rgb', '#rrggbb' or '#rrggbbaa'. */
    public function color(string $hex): VGColor
    {
        return $this->colors[$hex] ??= VGColor::irgba(...$this->bytes($hex));
    }
}
