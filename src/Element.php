<?php

declare(strict_types=1);

namespace PhpUi;

/**
 * One node of the UI tree: a box (div), a line of text, a text input or a
 * canvas. Built fresh on every render with a chainable, GPUI-like API.
 */
class Element
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
    public ?\Closure $onKeyDown = null;
    public ?\Closure $onEscape = null;
    /** Accessible name, e.g. for an icon-only button. */
    public ?string $label = null;
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
    /** Focused from the keyboard: show the focus ring. */
    public bool $focusVisible = false;
    /** Paint style for the current frame, animations applied (set by Painter). */
    public ?array $painted = null;
    /** Input editing: caret and selection anchor as character offsets. */
    public int $caret = 0, $anchor = 0;
    /** Text an input method is composing at the caret, not yet typed, and the caret within it. */
    public string $composing = '';
    public int $composingCaret = 0;

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

    public function child(Element|string|null $child): static
    {
        if ($child === null) return $this;
        $this->children[] = is_string($child) ? new Element('text', $child) : $child;
        return $this;
    }

    /** Children as a list, Filament style: grid(3)->schema([card(), card()]). Same as children(). */
    public function schema(iterable $schema): static
    {
        return $this->children($schema);
    }

    public function children(iterable $children): static
    {
        foreach ($children as $c) $this->child($c);
        return $this;
    }

    public function when(bool $condition, callable $then, ?callable $else = null): static
    {
        return $condition ? $then($this) : ($else ? $else($this) : $this);
    }

    public function id(string $id): static { $this->id = $id; return $this; }

    // Events ------------------------------------------------------------

    /** $fn receives ($x, $y, $element): the pointer and the element clicked. */
    public function onClick(callable $fn): static { $this->onClick = $fn(...); return $this->cursor('pointer'); }
    /** Called when a press lands outside this element, e.g. to close a menu. */
    public function onClickOutside(callable $fn): static { $this->onClickOutside = $fn(...); return $this; }
    /** Clicks inside stop here instead of reaching ancestors (a dialog's panel over its backdrop). */
    public function stopClicks(): static { return $this->set('stop', true); }
    public function onInput(callable $fn): static { $this->onInput = $fn(...); return $this; }
    public function onSubmit(callable $fn): static { $this->onSubmit = $fn(...); return $this; }
    /** $fn($x, $y, $element) on press and on every pointer move until release: sliders, splitters. */
    public function onDrag(callable $fn): static { $this->onDrag = $fn(...); return $this->cursor('pointer'); }
    /** $fn($x, $y, $element) on a right click, e.g. to open a context menu at the pointer. */
    public function onRightClick(callable $fn): static { $this->onRightClick = $fn(...); return $this; }
    /** $fn($key, $mods, $element) while this element has keyboard focus; return true when it used the key. */
    public function onKeyDown(callable $fn): static { $this->onKeyDown = $fn(...); return $this; }
    /** Escape anywhere calls the newest open element's handler: menus and dialogs close themselves. */
    public function onEscape(callable $fn): static { $this->onEscape = $fn(...); return $this; }
    /** Whether Tab stops here. Clickable elements and inputs do by default. */
    public function focusable(bool $on = true): static { return $this->set('focusable', $on); }
    /** false: Tab skips this control but arrow keys still reach it, as in a tab bar (one stop per group). */
    public function tabStop(bool $on = true): static { return $this->set('tabStop', $on); }
    /** Keep Tab inside this element while it is shown, e.g. a dialog. */
    public function trapFocus(): static { return $this->set('trapFocus', true); }
    /** What this element is for assistive technology: 'button', 'checkbox', 'slider', ... and its state. */
    public function role(string $role, array $state = []): static { return $this->set('role', $role)->set('roleState', $state); }
    /** The accessible name, e.g. 'Play' for an icon-only button. */
    public function label(string $name): static { $this->label = $name; return $this; }
    /** $fn receives the paths dropped on this element or one of its children. */
    public function onDrop(callable $fn): static { $this->onDrop = $fn(...); return $this; }

    /** Styles applied while the pointer is over this element: ->hover(fn($s) => $s->bg('#333')). */
    public function hover(callable $fn): static
    {
        $this->hoverStyle = $fn(new Element())->style + $this->hoverStyle;
        return $this;
    }

    /** Styles applied while this input has keyboard focus. */
    public function focus(callable $fn): static
    {
        $this->focusStyle = $fn(new Element())->style + $this->focusStyle;
        return $this;
    }

    // Layout ------------------------------------------------------------

    public function row(): static { return $this->set('dir', 'row'); }
    public function col(): static { return $this->set('dir', 'col'); }
    public function gap(float $px): static { return $this->set('gap', $px); }
    /** Lay out children in $count equal columns: col()->columns(2)->schema([...]). Same as grid(). */
    public function columns(int $count): static { return $this->grid($count); }
    /** Lay children out in $columns equal columns, rows as tall as their tallest cell. */
    public function grid(int $columns): static { return $this->set('grid', max(1, $columns)); }
    /** Let a row's children continue on the next line when they do not fit. */
    public function flexWrap(): static { return $this->row()->set('flexWrap', true); }

    public function w(float|string $v): static { return $this->set('w', $v); }
    public function h(float|string $v): static { return $this->set('h', $v); }
    public function size(float|string $v): static { return $this->w($v)->h($v); }
    public function full(): static { return $this->size('100%'); }
    public function grow(float $factor = 1): static { return $this->set('grow', $factor); }
    /** Shrink below content size when space runs out. */
    public function shrink(float $factor = 1): static { return $this->set('shrink', $factor); }
    /** Main-axis size before growing, instead of the content size. */
    public function basis(float $px): static { return $this->set('basis', $px); }
    /** Take an equal share of the free space: flex: 1 1 0. */
    public function flex1(): static { return $this->basis(0)->grow()->shrink(); }

    public function p(float $v): static { return $this->px($v)->py($v); }
    public function px(float $v): static { return $this->set('pl', $v)->set('pr', $v); }
    public function py(float $v): static { return $this->set('pt', $v)->set('pb', $v); }
    public function pt(float $v): static { return $this->set('pt', $v); }
    public function pr(float $v): static { return $this->set('pr', $v); }
    public function pb(float $v): static { return $this->set('pb', $v); }
    public function pl(float $v): static { return $this->set('pl', $v); }

    /** Cross-axis alignment of children: start, center, end, stretch (default). */
    public function items(string $align): static { return $this->set('align', $align); }
    public function itemsCenter(): static { return $this->items('center'); }
    public function itemsStart(): static { return $this->items('start'); }
    /** Cross-axis alignment of this element alone. */
    public function self(string $align): static { return $this->set('alignSelf', $align); }
    /** Main-axis distribution of children: start (default), center, end, between. */
    public function justify(string $justify): static { return $this->set('justify', $justify); }
    public function justifyCenter(): static { return $this->justify('center'); }
    public function justifyBetween(): static { return $this->justify('between'); }
    public function justifyEnd(): static { return $this->justify('end'); }
    public function center(): static { return $this->itemsCenter()->justifyCenter(); }
    /** Clip children to this box. */
    public function clip(): static { return $this->set('clip', true); }
    /** Scroll children vertically; the offset is kept by App under this id. */
    public function scroll(string $id): static { return $this->id($id)->set('scroll', true)->clip(); }

    /** Take this element out of the flow, placed by insets against its parent and drawn on top. */
    public function absolute(): static { return $this->set('absolute', true); }
    public function top(float $v): static { return $this->set('top', $v); }
    public function right(float $v): static { return $this->set('right', $v); }
    public function bottom(float $v): static { return $this->set('bottom', $v); }
    public function left(float $v): static { return $this->set('left', $v); }
    public function inset(float $v): static { return $this->top($v)->right($v)->bottom($v)->left($v); }
    /** Center an absolute element horizontally on its parent. */
    public function centerX(): static { return $this->set('centerX', true); }
    /** Show only while the parent is hovered (tooltips). */
    public function hoverOnly(): static { return $this->set('hoverOnly', true); }

    // Look --------------------------------------------------------------

    public function bg(string $color): static { return $this->set('bg', $color); }
    /** A vertical gradient background, top to bottom. */
    public function gradient(string $top, string $bottom): static { return $this->set('gradient', [$top, $bottom]); }
    public function rounded(float $radius): static { return $this->set('radius', $radius); }
    /** Radius per corner: top-left, top-right, bottom-right, bottom-left. */
    public function corners(float $tl, float $tr, float $br, float $bl): static { return $this->set('radius', [$tl, $tr, $br, $bl]); }
    public function border(float $width, string $color): static { return $this->set('borderW', $width)->set('borderColor', $color); }
    public function borderColor(string $color): static { return $this->set('borderColor', $color); }
    public function borderT(float $width, string $color): static { return $this->side('t', $width, $color); }
    public function borderR(float $width, string $color): static { return $this->side('r', $width, $color); }
    public function borderB(float $width, string $color): static { return $this->side('b', $width, $color); }
    public function borderL(float $width, string $color): static { return $this->side('l', $width, $color); }
    /** Tailwind shadows: xs, sm, md, lg. */
    public function shadow(string $size = 'sm'): static { return $this->set('shadow', $size); }
    /** A focus ring drawn outside the box. */
    public function ring(float $width, string $color): static { return $this->set('ring', [$width, $color]); }
    public function opacity(float $alpha): static { return $this->set('opacity', $alpha); }
    public function cursor(string $cursor): static { return $this->set('cursor', $cursor); }

    // Text (inherited by children) ----------------------------------------

    public function textColor(string $color): static { return $this->set('color', $color); }
    public function textSize(float $px): static { return $this->set('size', $px); }
    public function font(string $name): static { return $this->set('font', $name); }
    public function medium(): static { return $this->font('medium'); }
    public function semibold(): static { return $this->font('semibold'); }
    public function bold(): static { return $this->font('bold'); }
    public function mono(): static { return $this->font('mono'); }
    /** Letter spacing in px, e.g. -0.5 for tracking-tight headings. */
    public function tracking(float $px): static { return $this->set('tracking', $px); }
    public function textCenter(): static { return $this->set('textAlign', 'center'); }
    public function textRight(): static { return $this->set('textAlign', 'right'); }
    /** Let the user select this text (or all text inside) with the mouse and copy it. */
    public function selectable(bool $on = true): static { return $this->set('selectable', $on); }
    /** Wrap text onto as many lines as its width needs; it may also shrink in a row. */
    public function wrap(): static { return $this->set('wrap', true)->shrink(); }
    /** A multi-line input showing $rows lines; Enter adds a line, Ctrl/Cmd+Enter submits. */
    public function multiline(int $rows = 4): static { return $this->set('multiline', max(1, $rows)); }
    /** How an image fills its box: 'contain' (default), 'cover' (crops) or 'fill' (stretches). */
    public function fit(string $mode): static
    {
        if (!in_array($mode, ['contain', 'cover', 'fill'], true)) throw new \InvalidArgumentException("Unknown fit $mode");
        return $this->set('fit', $mode);
    }
    /**
     * Fade background, text and border color, opacity and focus ring between states
     * (hover, focus, or a changed style) over $ms milliseconds. Needs a stable id().
     */
    public function transition(float $ms = 150): static { return $this->set('transition', max(1, $ms)); }
    /** Fade in over $ms milliseconds each time the element appears, e.g. a menu or dialog. */
    public function animateIn(float $ms = 150): static { return $this->set('enter', max(1, $ms)); }
    /** Cut text that does not fit with an ellipsis. */
    public function truncate(): static { return $this->set('truncate', true)->shrink(); }

    /** Draw a Lucide icon (assets/icons) centered in this box. */
    public function icon(string $name, string $color, float $size = 16): static
    {
        return $this->set('icon', [$name, $color, $size]);
    }

    protected function side(string $side, float $width, string $color): static
    {
        $sides = $this->style['sides'] ?? [];
        $sides[$side] = [$width, $color];
        return $this->set('sides', $sides);
    }

    protected function set(string $key, mixed $value): static
    {
        $this->style[$key] = $value;
        return $this;
    }
}
