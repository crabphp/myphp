<?php

declare(strict_types=1);

namespace PhpUi;

/**
 * One node of the UI tree: a box (div), a line of text, a text input or a
 * canvas. Built fresh on every render with a chainable, GPUI-like API.
 */
final class Element
{
    /** @var Element[] */
    public array $children = [];
    public array $style = [];
    public array $hoverStyle = [];
    public array $focusStyle = [];
    public ?\Closure $onClick = null;
    public ?\Closure $onClickOutside = null;
    public ?\Closure $onInput = null;
    public ?\Closure $onSubmit = null;
    public ?\Closure $onDrop = null;
    public ?\Closure $onDrag = null;
    public ?\Closure $onRightClick = null;
    public ?\Closure $draw = null;
    public ?string $id = null;
    public string $placeholder = '';

    // Filled in by Layout.
    public float $x = 0, $y = 0, $w = 0, $h = 0;
    public float $mw = 0, $mh = 0;
    public float $scrollY = 0, $contentH = 0;
    public string $color = '#000000';
    public float $size = 14;
    public string $font = 'regular';
    /** Wrapped text lines, and for multi-line inputs [start, length] character ranges. */
    public array $lines = [];
    public ?float $wrapAt = null;
    /** Character ranges [start, length] of wrapped lines, and the selected range of selectable text. */
    public array $ranges = [];
    public ?array $selection = null;
    public bool $selectable = false;
    public float $scrollX = 0;
    /** Pixel size of an image element's file. */
    public array $imageSize = [0.0, 0.0];

    // Filled in by App.
    public ?Element $parent = null;
    public bool $hovered = false;
    public bool $focused = false;
    /** Paint style for the current frame, animations applied (set by Painter). */
    public ?array $painted = null;
    /** Input editing: caret and selection anchor as character offsets. */
    public int $caret = 0, $anchor = 0;

    public function __construct(public readonly string $kind = 'div', public string $text = '') {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->style[$key] ?? $default;
    }

    /** Style for painting: base, then hover, then focus. Layout only reads the base. */
    public function paintStyle(): array
    {
        $s = $this->style;
        if ($this->hovered) $s = $this->hoverStyle + $s;
        if ($this->focused) $s = $this->focusStyle + $s;
        return $s;
    }

    public function isAbsolute(): bool
    {
        return isset($this->style['absolute']);
    }

    // Tree --------------------------------------------------------------

    public function child(Element|string|null $child): self
    {
        if ($child === null) return $this;
        $this->children[] = is_string($child) ? new Element('text', $child) : $child;
        return $this;
    }

    public function children(iterable $children): self
    {
        foreach ($children as $c) $this->child($c);
        return $this;
    }

    public function when(bool $condition, callable $then, ?callable $else = null): self
    {
        return $condition ? $then($this) : ($else ? $else($this) : $this);
    }

    public function id(string $id): self { $this->id = $id; return $this; }

    // Events ------------------------------------------------------------

    /** $fn receives ($x, $y, $element): the pointer and the element clicked. */
    public function onClick(callable $fn): self { $this->onClick = $fn(...); return $this->cursor('pointer'); }
    /** Called when a press lands outside this element, e.g. to close a menu. */
    public function onClickOutside(callable $fn): self { $this->onClickOutside = $fn(...); return $this; }
    /** Clicks inside stop here instead of reaching ancestors (a dialog's panel over its backdrop). */
    public function stopClicks(): self { return $this->set('stop', true); }
    public function onInput(callable $fn): self { $this->onInput = $fn(...); return $this; }
    public function onSubmit(callable $fn): self { $this->onSubmit = $fn(...); return $this; }
    /** $fn($x, $y, $element) on press and on every pointer move until release: sliders, splitters. */
    public function onDrag(callable $fn): self { $this->onDrag = $fn(...); return $this->cursor('pointer'); }
    /** $fn($x, $y, $element) on a right click, e.g. to open a context menu at the pointer. */
    public function onRightClick(callable $fn): self { $this->onRightClick = $fn(...); return $this; }
    /** $fn receives the paths dropped on this element or one of its children. */
    public function onDrop(callable $fn): self { $this->onDrop = $fn(...); return $this; }

    /** Styles applied while the pointer is over this element: ->hover(fn($s) => $s->bg('#333')). */
    public function hover(callable $fn): self
    {
        $this->hoverStyle = $fn(new Element())->style + $this->hoverStyle;
        return $this;
    }

    /** Styles applied while this input has keyboard focus. */
    public function focus(callable $fn): self
    {
        $this->focusStyle = $fn(new Element())->style + $this->focusStyle;
        return $this;
    }

    // Layout ------------------------------------------------------------

    public function row(): self { return $this->set('dir', 'row'); }
    public function col(): self { return $this->set('dir', 'col'); }
    public function gap(float $px): self { return $this->set('gap', $px); }
    /** Lay children out in $columns equal columns, rows as tall as their tallest cell. */
    public function grid(int $columns): self { return $this->set('grid', max(1, $columns)); }
    /** Let a row's children continue on the next line when they do not fit. */
    public function flexWrap(): self { return $this->row()->set('flexWrap', true); }

    public function w(float|string $v): self { return $this->set('w', $v); }
    public function h(float|string $v): self { return $this->set('h', $v); }
    public function size(float|string $v): self { return $this->w($v)->h($v); }
    public function full(): self { return $this->size('100%'); }
    public function grow(float $factor = 1): self { return $this->set('grow', $factor); }
    /** Shrink below content size when space runs out. */
    public function shrink(float $factor = 1): self { return $this->set('shrink', $factor); }
    /** Main-axis size before growing, instead of the content size. */
    public function basis(float $px): self { return $this->set('basis', $px); }
    /** Take an equal share of the free space: flex: 1 1 0. */
    public function flex1(): self { return $this->basis(0)->grow()->shrink(); }

    public function p(float $v): self { return $this->px($v)->py($v); }
    public function px(float $v): self { return $this->set('pl', $v)->set('pr', $v); }
    public function py(float $v): self { return $this->set('pt', $v)->set('pb', $v); }
    public function pt(float $v): self { return $this->set('pt', $v); }
    public function pr(float $v): self { return $this->set('pr', $v); }
    public function pb(float $v): self { return $this->set('pb', $v); }
    public function pl(float $v): self { return $this->set('pl', $v); }

    /** Cross-axis alignment of children: start, center, end, stretch (default). */
    public function items(string $align): self { return $this->set('align', $align); }
    public function itemsCenter(): self { return $this->items('center'); }
    public function itemsStart(): self { return $this->items('start'); }
    /** Cross-axis alignment of this element alone. */
    public function self(string $align): self { return $this->set('alignSelf', $align); }
    /** Main-axis distribution of children: start (default), center, end, between. */
    public function justify(string $justify): self { return $this->set('justify', $justify); }
    public function justifyCenter(): self { return $this->justify('center'); }
    public function justifyBetween(): self { return $this->justify('between'); }
    public function justifyEnd(): self { return $this->justify('end'); }
    public function center(): self { return $this->itemsCenter()->justifyCenter(); }
    /** Clip children to this box. */
    public function clip(): self { return $this->set('clip', true); }
    /** Scroll children vertically; the offset is kept by App under this id. */
    public function scroll(string $id): self { return $this->id($id)->set('scroll', true)->clip(); }

    /** Take this element out of the flow, placed by insets against its parent and drawn on top. */
    public function absolute(): self { return $this->set('absolute', true); }
    public function top(float $v): self { return $this->set('top', $v); }
    public function right(float $v): self { return $this->set('right', $v); }
    public function bottom(float $v): self { return $this->set('bottom', $v); }
    public function left(float $v): self { return $this->set('left', $v); }
    public function inset(float $v): self { return $this->top($v)->right($v)->bottom($v)->left($v); }
    /** Center an absolute element horizontally on its parent. */
    public function centerX(): self { return $this->set('centerX', true); }
    /** Show only while the parent is hovered (tooltips). */
    public function hoverOnly(): self { return $this->set('hoverOnly', true); }

    // Look --------------------------------------------------------------

    public function bg(string $color): self { return $this->set('bg', $color); }
    /** A vertical gradient background, top to bottom. */
    public function gradient(string $top, string $bottom): self { return $this->set('gradient', [$top, $bottom]); }
    public function rounded(float $radius): self { return $this->set('radius', $radius); }
    /** Radius per corner: top-left, top-right, bottom-right, bottom-left. */
    public function corners(float $tl, float $tr, float $br, float $bl): self { return $this->set('radius', [$tl, $tr, $br, $bl]); }
    public function border(float $width, string $color): self { return $this->set('borderW', $width)->set('borderColor', $color); }
    public function borderColor(string $color): self { return $this->set('borderColor', $color); }
    public function borderT(float $width, string $color): self { return $this->side('t', $width, $color); }
    public function borderR(float $width, string $color): self { return $this->side('r', $width, $color); }
    public function borderB(float $width, string $color): self { return $this->side('b', $width, $color); }
    public function borderL(float $width, string $color): self { return $this->side('l', $width, $color); }
    /** Tailwind shadows: xs, sm, md, lg. */
    public function shadow(string $size = 'sm'): self { return $this->set('shadow', $size); }
    /** A focus ring drawn outside the box. */
    public function ring(float $width, string $color): self { return $this->set('ring', [$width, $color]); }
    public function opacity(float $alpha): self { return $this->set('opacity', $alpha); }
    public function cursor(string $cursor): self { return $this->set('cursor', $cursor); }

    // Text (inherited by children) ----------------------------------------

    public function textColor(string $color): self { return $this->set('color', $color); }
    public function textSize(float $px): self { return $this->set('size', $px); }
    public function font(string $name): self { return $this->set('font', $name); }
    public function medium(): self { return $this->font('medium'); }
    public function semibold(): self { return $this->font('semibold'); }
    public function bold(): self { return $this->font('bold'); }
    public function mono(): self { return $this->font('mono'); }
    /** Letter spacing in px, e.g. -0.5 for tracking-tight headings. */
    public function tracking(float $px): self { return $this->set('tracking', $px); }
    public function textCenter(): self { return $this->set('textAlign', 'center'); }
    public function textRight(): self { return $this->set('textAlign', 'right'); }
    /** Let the user select this text (or all text inside) with the mouse and copy it. */
    public function selectable(bool $on = true): self { return $this->set('selectable', $on); }
    /** Wrap text onto as many lines as its width needs; it may also shrink in a row. */
    public function wrap(): self { return $this->set('wrap', true)->shrink(); }
    /** A multi-line input showing $rows lines; Enter adds a line, Ctrl/Cmd+Enter submits. */
    public function multiline(int $rows = 4): self { return $this->set('multiline', max(1, $rows)); }
    /** How an image fills its box: 'contain' (default), 'cover' (crops) or 'fill' (stretches). */
    public function fit(string $mode): self
    {
        if (!in_array($mode, ['contain', 'cover', 'fill'], true)) throw new \InvalidArgumentException("Unknown fit $mode");
        return $this->set('fit', $mode);
    }
    /**
     * Fade background, text and border color, opacity and focus ring between states
     * (hover, focus, or a changed style) over $ms milliseconds. Needs a stable id().
     */
    public function transition(float $ms = 150): self { return $this->set('transition', max(1, $ms)); }
    /** Fade in over $ms milliseconds each time the element appears, e.g. a menu or dialog. */
    public function animateIn(float $ms = 150): self { return $this->set('enter', max(1, $ms)); }
    /** Cut text that does not fit with an ellipsis. */
    public function truncate(): self { return $this->set('truncate', true)->shrink(); }

    /** Draw a Lucide icon (assets/icons) centered in this box. */
    public function icon(string $name, string $color, float $size = 16): self
    {
        return $this->set('icon', [$name, $color, $size]);
    }

    private function side(string $side, float $width, string $color): self
    {
        $sides = $this->style['sides'] ?? [];
        $sides[$side] = [$width, $color];
        return $this->set('sides', $sides);
    }

    private function set(string $key, mixed $value): self
    {
        $this->style[$key] = $value;
        return $this;
    }
}
