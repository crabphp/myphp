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
    /** Time in seconds for animations; tests can replace it. */
    public ?\Closure $clock = null;
    /** Skip animations and show final states, e.g. for screenshots. */
    public bool $instant = false;
    /** The user prefers less motion: no transitions or fade-ins. */
    public bool $reduceMotion = false;
    /** True while the last frame had an animation in progress. */
    public bool $animating = false;
    /** @var array<string, array<string, array>> Running transitions by element id and property. */
    private array $anim = [];
    private array $seen = [], $seenNow = [];
    private float $now = 0;
    private array $lineCache = [];
    private float $ratio = 1.0;
    /** Current clip rectangle and opacity, for the quad pass. */
    private array $clip = [];
    private float $alpha = 1.0;
    /** The focused input's caret in the last frame, [x, y, w, h] in layout units: input methods compose there. */
    public ?array $caretRect = null;

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

    /**
     * Break text into lines that fit $width, as [start, length] character ranges of
     * the whole text. Unlike wrap(), nothing is dropped: spaces stay at the end of
     * their line and each "\n" sits between two ranges. For editing.
     */
    public function breakLines(string $text, float $width, string $font = 'regular', float $size = 14, float $tracking = 0): array
    {
        $key = "$width\0$font\0$size\0$tracking\0$text";
        if (isset($this->lineCache[$key])) return $this->lineCache[$key];
        $fits = fn (string $s) => $width <= 0 || $this->textWidth($s, $font, $size, $tracking) <= $width;
        $lines = [];
        $start = 0;
        foreach (explode("\n", $text) as $para) {
            $len = mb_strlen($para);
            preg_match_all('/\S+\s*|\s+/u', $para, $m);
            [$from, $count, $pos] = [0, 0, 0];
            foreach ($m[0] as $token) {
                $n = mb_strlen($token);
                $word = rtrim($token);
                if ($count > 0 && !$fits(mb_substr($para, $from, $count) . $word)) {
                    $lines[] = [$start + $from, $count];
                    [$from, $count] = [$pos, 0];
                }
                if ($count === 0 && $word !== '' && !$fits($word)) {
                    // A word wider than the line: split it by character.
                    $chars = mb_str_split($word);
                    $piece = 0;
                    foreach ($chars as $i => $char) {
                        if ($piece > 0 && !$fits(mb_substr($para, $from, $piece) . $char)) {
                            $lines[] = [$start + $from, $piece];
                            [$from, $piece] = [$pos + $i, 0];
                        }
                        $piece++;
                    }
                    $count = $piece + $n - mb_strlen($word);
                } else {
                    $count += $n;
                }
                $pos += $n;
            }
            $lines[] = [$start + $from, $count];
            $start += $len + 1;
        }
        if (count($this->lineCache) > 64) $this->lineCache = [];
        return $this->lineCache[$key] = $lines;
    }

    /** The line (index into breakLines()) that shows the caret at $offset. */
    public static function lineOf(array $lines, int $offset): int
    {
        foreach ($lines as $i => [$start, $len]) {
            if ($offset < $start) continue;
            if ($offset < $start + $len) return $i;
            // At the end of a soft-wrapped line the caret belongs to the next one.
            if ($offset === $start + $len && !(isset($lines[$i + 1]) && $lines[$i + 1][0] === $offset)) return $i;
        }
        return max(0, count($lines) - 1);
    }

    /** The character offset in $line nearest to $x pixels from its start. */
    public function offsetAt(string $line, float $x, string $font, float $size, float $tracking = 0): int
    {
        $n = mb_strlen($line);
        if ($x <= 0 || $n === 0) return 0;
        $width = fn (int $k) => $this->textWidth(mb_substr($line, 0, $k), $font, $size, $tracking);
        [$lo, $hi] = [0, $n];
        while ($lo < $hi) {
            $mid = intdiv($lo + $hi + 1, 2);
            $width($mid) <= $x ? $lo = $mid : $hi = $mid - 1;
        }
        if ($lo < $n && $x - $width($lo) > $width($lo + 1) - $x) $lo++;
        return $lo;
    }

    /** Lines of a multi-line input at its current width. */
    public function inputLines(Element $e): array
    {
        $iw = $e->w - $e->get('pl', 0) - $e->get('pr', 0);
        return $e->lines = $this->breakLines($e->text, max(1, $iw - 2), $e->font, $e->size, $e->get('tracking', 0));
    }

    public function lineHeight(float $size): float
    {
        return round($size * 1.43);
    }

    /** Draw a whole frame of $w×$h logical pixels. */
    public function render(Element $root, float $w, float $h, float $ratio, bool $caretOn): void
    {
        $this->caretOn = $caretOn;
        $this->now = $this->clock ? ($this->clock)() : (App::current()?->window->time() ?? microtime(true));
        $this->animating = false;
        // Elements painted in the last frame; one missing there is appearing now.
        $this->seen = $this->seenNow;
        $this->seenNow = [];
        $this->anim = array_intersect_key($this->anim, $this->seen);
        $this->overlays = [];
        $this->caretRect = null;
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
    /** $color is the parent's painted text color: hover and animated colors reach child text. */
    private function walk(Element $e, string $pass, bool $asOverlay = false, ?string $color = null): void
    {
        if ($e->isAbsolute() && !$asOverlay) {
            if ($pass !== 'vg' && empty($e->style['hidden'])) $this->overlays[] = $e;
            return;
        }
        if (!empty($e->style['hidden'])) return;

        $vg = $this->vg;
        $s = $e->painted ??= $this->focusRing($e, $this->animate($e, $this->inherit($e->paintStyle(), $e, $color)));
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
            if ($e->kind === 'text' && $e->selection) $this->textSelection($e, $s);
            if ($e->kind === 'text' && !empty($s['wrap'])) {
                $this->lines($e, $s);
            } elseif ($e->kind === 'text') {
                $this->text($e, $e->text, $e->x, $e->w, $s['color'] ?? $e->color, $s);
            } elseif ($e->kind === 'input') {
                $this->input($e, $s);
            } elseif ($e->kind === 'image') {
                $this->image($e, $s);
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
        foreach ($e->children as $c) $this->walk($c, $pass, false, $s['color'] ?? null);
        if (!empty($s['scroll']) && $e->contentH > $e->h && $pass !== 'vg') $this->scrollbar($e, $pass);

        if ($save && $pass !== 'quads') $vg->restore();
        [$this->clip, $this->alpha] = [$clip, $alpha];
    }

    /** Keyboard focus shows a ring around the control, as inputs show while editing. */
    private function focusRing(Element $e, array $s): array
    {
        // Solid ring color: at least 3:1 against the background in both themes.
        if ($e->focusVisible) $s['ring'] = [2, t('ring')];
        return $s;
    }

    /** An element without its own text color paints with its parent's painted one. */
    private function inherit(array $s, Element $e, ?string $color): array
    {
        if (!isset($s['color'])) $s['color'] = $color ?? $e->color;
        return $s;
    }

    // Animation ----------------------------------------------------------------

    /** Paint style with transitions and fade-in applied for this frame. */
    private function animate(Element $e, array $s): array
    {
        $ms = $s['transition'] ?? null;
        $enter = $s['enter'] ?? null;
        if (($ms === null && $enter === null) || $e->id === null) return $s;
        $id = $e->id;
        $this->seenNow[$id] = true;
        $appearing = !isset($this->seen[$id]);
        if ($appearing) unset($this->anim[$id]);
        if ($this->instant || $this->reduceMotion) return $s;

        if ($ms !== null) {
            foreach (['bg', 'color', 'borderColor', 'opacity', 'ring'] as $key) {
                $value = match ($key) {
                    'color' => $this->floatsOf($s['color'] ?? $e->color),
                    'opacity' => [(float) ($s['opacity'] ?? 1)],
                    'ring' => isset($s['ring']) ? [$s['ring'][0], ...$this->floatsOf($s['ring'][1])] : null,
                    default => isset($s[$key]) ? $this->floatsOf($s[$key]) : null,
                };
                $current = $this->tween($id, $key, $value, $ms / 1000);
                if ($current === null) { unset($s[$key]); continue; }
                $s[$key] = match ($key) {
                    'opacity' => $current[0],
                    'ring' => [$current[0], $this->hexOf(array_slice($current, 1))],
                    default => $this->hexOf($current),
                };
            }
            if (($s['opacity'] ?? 1) >= 1) unset($s['opacity']);
        }
        if ($enter !== null) {
            if ($appearing) $this->anim[$id]['enter'] = ['from' => [0.0], 'to' => [1.0], 'start' => $this->now, 'time' => $enter / 1000, 'gone' => false];
            $fade = $this->tween($id, 'enter', [1.0], $enter / 1000);
            if ($fade !== null && $fade[0] < 1) $s['opacity'] = ($s['opacity'] ?? 1) * $fade[0];
        }
        return $s;
    }

    /**
     * The value to show for a property moving to $target ([floats], or null when unset).
     * A new target starts from what is on screen; an unset color fades to transparent.
     */
    private function tween(string $id, string $key, ?array $target, float $time): ?array
    {
        $state = $this->anim[$id][$key] ?? null;
        if ($state === null) {
            if ($target === null) return null;
            $state = ['from' => $target, 'to' => $target, 'start' => -INF, 'time' => $time, 'gone' => false];
        }
        $goal = $target ?? $this->transparent($state['to']);
        if ($goal !== $state['to']) {
            $state = ['from' => $this->progress($state), 'to' => $goal, 'start' => $this->now, 'time' => $time, 'gone' => $target === null];
            if ($state['from'] !== null && count($state['from']) !== count($goal)) $state['from'] = $goal;
        }
        if ($state['from'] === null) $state['from'] = $this->transparent($goal);
        $value = $this->progress($state);
        $done = $this->now - $state['start'] >= $state['time'];
        if (!$done) $this->animating = true;
        if ($done && $state['gone']) { unset($this->anim[$id][$key]); return null; }
        $this->anim[$id][$key] = $state;
        return $value;
    }

    private function progress(array $state): array
    {
        $t = $state['time'] > 0 ? min(1, max(0, ($this->now - $state['start']) / $state['time'])) : 1;
        $t = 1 - (1 - $t) ** 3; // ease out
        return array_map(fn ($a, $b) => $a + ($b - $a) * $t, $state['from'], $state['to']);
    }

    /** The same color (or ring) with zero alpha; opacity stays as it is. */
    private function transparent(array $v): array
    {
        if (count($v) >= 4) $v[count($v) - 1] = 0.0;
        return $v;
    }

    private function floatsOf(string $hex): array
    {
        return array_map(fn ($v) => $v / 255, $this->bytes($hex));
    }

    private function hexOf(array $c): string
    {
        return vsprintf('#%02x%02x%02x%02x', array_map(fn ($v) => (int) round(max(0, min(1, $v)) * 255), $c));
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
                $this->quads->add($rect, array_fill(0, 4, $rr + $spread), $color, $color, $none, 0, $blur, $this->clip, $spread, $oy);
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

    /** @var array<string, \GL\VectorGraphics\VGImage> Images by file. */
    private array $images = [];

    private function image(Element $e, array $s): void
    {
        $vg = $this->vg;
        $image = $this->images[$e->text] ??= $vg->imageFromTexture(
            \GL\Texture\Texture2D::fromDisk(realFile($e->text), 4), \GL\VectorGraphics\VGImage::REPEAT_NONE, \GL\VectorGraphics\VGImage::FILTER_LINEAR);
        [$iw, $ih] = $e->imageSize;
        [$x, $y, $w, $h] = [$e->x, $e->y, $e->w, $e->h];
        if ($w <= 0 || $h <= 0 || $iw <= 0 || $ih <= 0) return;
        $fit = $s['fit'] ?? 'contain';
        if ($fit === 'fill') {
            [$dw, $dh] = [$w, $h];
        } else {
            $scale = $fit === 'cover' ? max($w / $iw, $h / $ih) : min($w / $iw, $h / $ih);
            [$dw, $dh] = [$iw * $scale, $ih * $scale];
        }
        [$dx, $dy] = [$x + ($w - $dw) / 2, $y + ($h - $dh) / 2];
        // Contain draws only the picture's own rectangle; cover and fill clip to the box and its corners.
        $fit === 'contain'
            ? $this->shape($dx, $dy, $dw, $dh, $s['radius'] ?? 0)
            : $this->shape($x, $y, $w, $h, $s['radius'] ?? 0);
        $vg->fillPaint($image->makePaint($dx, $dy, $dw, $dh, 0, 1));
        $vg->fill();
    }

    /** Where a line of text starts, given its box and alignment. */
    public function lineStart(Element $e, string $line, float $x, float $w, ?string $align = null): float
    {
        $align ??= $e->get('textAlign', 'left');
        if ($align === 'left') return $x;
        $width = $this->textWidth($line, $e->font, $e->size, $e->get('tracking', 0));
        return $align === 'center' ? $x + ($w - $width) / 2 : $x + $w - $width;
    }

    /** The selection of selectable text: one highlight per line it covers. */
    private function textSelection(Element $e, array $s): void
    {
        [$from, $to] = $e->selection;
        $wrapped = !empty($s['wrap']);
        $ranges = $wrapped ? $e->ranges : [[0, mb_strlen($e->text)]];
        [$pl, $pr, $pt] = $wrapped ? [$s['pl'] ?? 0, $s['pr'] ?? 0, $s['pt'] ?? 0] : [0, 0, 0];
        $lh = $this->lineHeight($e->size);
        $width = fn (string $t) => $this->textWidth($t, $e->font, $e->size, $s['tracking'] ?? 0);
        foreach ($ranges as $i => [$start, $len]) {
            if ($to <= $start || $from > $start + $len) continue;
            $line = mb_substr($e->text, $start, $len);
            $x = $this->lineStart($e, $wrapped ? rtrim($line, ' ') : $line, $e->x + $pl, $e->w - $pl - $pr, $s['textAlign'] ?? 'left');
            $a = max($from, $start) - $start;
            $b = min($to, $start + $len) - $start;
            $x1 = $width(mb_substr($line, 0, $a));
            $cy = $wrapped ? $e->y + $pt + $i * $lh + $lh / 2 : $e->y + $e->h / 2;
            $this->vg->beginPath();
            $this->vg->rect($x + $x1, $cy - $lh / 2, max(2, $width(mb_substr($line, 0, $b)) - $x1 + ($to > $start + $len ? 4 : 0)), $lh);
            $this->vg->fillColor($this->color(t('ring', 0.45)));
            $this->vg->fill();
        }
    }

    /** Wrapped text: the lines Layout measured, from the top of the content box. */
    private function lines(Element $e, array $s): void
    {
        [$pl, $pr, $pt] = [$s['pl'] ?? 0, $s['pr'] ?? 0, $s['pt'] ?? 0];
        $lh = $this->lineHeight($e->size);
        foreach ($e->lines as $i => $line) {
            $this->text($e, $line, $e->x + $pl, $e->w - $pl - $pr, $s['color'] ?? $e->color, ['truncate' => false] + $s, $e->y + $pt + $i * $lh + $lh / 2);
        }
    }

    private function input(Element $e, array $s): void
    {
        // An input method's composition shows in the text at the caret, underlined.
        if ($e->composing === '') { $this->inputText($e, $s); return; }
        [$text, $caret, $anchor] = [$e->text, $e->caret, $e->anchor];
        $at = min($e->caret, $e->anchor);
        $e->text = mb_substr($text, 0, $at) . $e->composing . mb_substr($text, max($e->caret, $e->anchor));
        $e->caret = $e->anchor = $at + $e->composingCaret;
        try {
            $this->inputText($e, $s, [$at, $at + mb_strlen($e->composing)]);
        } finally {
            [$e->text, $e->caret, $e->anchor] = [$text, $caret, $anchor];
        }
    }

    private function inputText(Element $e, array $s, ?array $composing = null): void
    {
        $vg = $this->vg;
        [$pl, $pr, $pt] = [$s['pl'] ?? 0, $s['pr'] ?? 0, $s['pt'] ?? 0];
        $ix = $e->x + $pl;
        $iw = $e->w - $pl - $pr;
        $lh = $this->lineHeight($e->size);
        $ch = round($e->size * 1.15);
        $color = $s['color'] ?? $e->color;
        $tracking = $s['tracking'] ?? 0;
        $multi = !empty($s['multiline']);
        $width = fn (string $t) => $this->textWidth($t, $e->font, $e->size, $tracking);
        $plain = ['truncate' => false, 'textAlign' => 'left'] + $s;
        $selection = $e->focused && $e->caret !== $e->anchor ? [min($e->caret, $e->anchor), max($e->caret, $e->anchor)] : null;
        $highlight = function (float $x, float $cy, float $w) use ($vg, $ch) {
            $vg->beginPath();
            $vg->rect($x, $cy - $ch / 2 - 1, max(2, $w), $ch + 2);
            $vg->fillColor($this->color(t('ring', 0.45)));
            $vg->fill();
        };
        $underline = function (float $x, float $cy, float $w) use ($vg, $ch, $color) {
            $vg->beginPath();
            $vg->rect($x, $cy + $ch / 2, max(1, $w), 1);
            $vg->fillColor($this->color($color));
            $vg->fill();
        };
        $caret = function (float $x, float $cy) use ($vg, $ch, $color) {
            $this->caretRect = [round($x), $cy - $ch / 2, 1, $ch];
            if (!$this->caretOn) return;
            $vg->beginPath();
            $vg->rect(round($x), $cy - $ch / 2, 1, $ch);
            $vg->fillColor($this->color($color));
            $vg->fill();
        };

        $vg->save();
        $vg->intersectScissor($e->x, $e->y, $e->w, $e->h);
        if (!$multi) {
            $cy = $e->y + $e->h / 2;
            $tx = $ix - $e->scrollX;
            if ($e->text === '' && $e->placeholder !== '') {
                $this->text($e, $e->placeholder, $ix, $iw, $s['placeholder'] ?? t('muted-foreground'), $plain);
            } else {
                if ($selection) {
                    $x1 = $width(mb_substr($e->text, 0, $selection[0]));
                    $highlight($tx + $x1, $cy, $width(mb_substr($e->text, 0, $selection[1])) - $x1);
                }
                $this->text($e, $e->text, $tx, $iw, $color, $plain);
            }
            if ($composing) {
                $x1 = $width(mb_substr($e->text, 0, $composing[0]));
                $underline($tx + $x1, $cy, $width(mb_substr($e->text, 0, $composing[1])) - $x1);
            }
            if ($e->focused) $caret($tx + $width(mb_substr($e->text, 0, $e->caret)), $cy);
        } else {
            $top = $e->y + $pt - $e->scrollY;
            if ($e->text === '' && $e->placeholder !== '') {
                $this->text($e, $e->placeholder, $ix, $iw, $s['placeholder'] ?? t('muted-foreground'), $plain, $top + $lh / 2);
            }
            $lines = $this->inputLines($e);
            foreach ($lines as $i => [$start, $len]) {
                $cy = $top + $i * $lh + $lh / 2;
                if ($cy + $lh < $e->y || $cy - $lh > $e->y + $e->h) continue;
                $line = mb_substr($e->text, $start, $len);
                if ($selection && $selection[0] <= $start + $len && $selection[1] > $start) {
                    $from = max($selection[0], $start) - $start;
                    $to = min($selection[1], $start + $len) - $start;
                    $x1 = $width(mb_substr($line, 0, $from));
                    // A selected line break shows as a small block at the end of the line.
                    $extra = $selection[1] > $start + $len ? 6 : 0;
                    $highlight($ix + $x1, $cy, $width(mb_substr($line, 0, $to)) - $x1 + $extra);
                }
                if ($line !== '') $this->text($e, $line, $ix, $iw, $color, $plain, $cy);
            }
            if ($composing) {
                foreach ($lines as $i => [$start, $len]) {
                    $from = max($composing[0], $start);
                    $to = min($composing[1], $start + $len);
                    if ($from >= $to) continue;
                    $x1 = $width(mb_substr($e->text, $start, $from - $start));
                    $underline($ix + $x1, $top + $i * $lh + $lh / 2, $width(mb_substr($e->text, $start, $to - $start)) - $x1);
                }
            }
            if ($e->focused) {
                $i = self::lineOf($lines, $e->caret);
                [$start] = $lines[$i];
                $caret($ix + $width(mb_substr($e->text, $start, $e->caret - $start)), $top + $i * $lh + $lh / 2);
            }
        }
        $vg->restore();
    }

    /** Draw one line of text, vertically centered in the element or on $cy. */
    public function text(Element $e, string $text, float $x, float $w, string $color, array $s = [], ?float $cy = null): void
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
        $vg->text($tx, $cy ?? $e->y + $e->h / 2, $text);
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
