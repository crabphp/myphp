<?php

declare(strict_types=1);

namespace PhpUi;

/*
 * shadcn/ui's default components (new-york, neutral), rebuilt from the
 * primitives. Sizes, radii and colors follow the Tailwind classes of each
 * component in the shadcn registry; the base radius is 10px (0.625rem).
 */

const RADIUS = 10;

// Button --------------------------------------------------------------------

/**
 * Variants: default, secondary, outline, ghost, destructive, link.
 * Sizes: default (h-9), sm (h-8), lg (h-10), icon (size-9), icon-sm (size-8).
 */
function button(string $label, ?callable $onClick = null, string $variant = 'default', string $size = 'default', ?string $icon = null): Element
{
    [$bg, $hover, $fg, $border] = match ($variant) {
        'secondary' => [t('secondary'), t('secondary', 0.8), t('secondary-foreground'), null],
        'outline' => [Theme::dark() ? t('input', 0.3) : t('background'), t('accent'), t('foreground'), t('input')],
        'ghost' => [null, t('accent'), t('accent-foreground'), null],
        'destructive' => [t('destructive'), t('destructive', 0.9), '#ffffff', null],
        'link' => [null, null, t('primary'), null],
        default => [t('primary'), t('primary', 0.9), t('primary-foreground'), null],
    };
    [$h, $px] = match ($size) {
        'sm' => [32, 12], 'lg' => [40, 24], 'icon' => [36, 0], 'icon-sm' => [32, 0], default => [36, 16],
    };
    $iconOnly = str_starts_with($size, 'icon');
    return row()->center()->gap(8)->h($h)->when($iconOnly, fn ($e) => $e->w($h), fn ($e) => $e->px($icon && $label ? $px - 4 : $px))
        ->rounded(8)->textColor($fg)->medium()->textSize(14)
        ->when($bg !== null, fn ($e) => $e->bg($bg))
        ->when($hover !== null, fn ($e) => $e->hover(fn ($s) => $s->bg($hover)))
        ->when($border !== null, fn ($e) => $e->border(1, $border))
        ->when(in_array($variant, ['default', 'outline', 'destructive', 'secondary'], true), fn ($e) => $e->shadow('xs'))
        ->when($icon !== null, fn ($e) => $e->child(icon($icon, 16, $fg)))
        ->when($label !== '', fn ($e) => $e->child($label))
        ->when($onClick !== null, fn ($e) => $e->onClick($onClick), fn ($e) => $e->cursor('pointer'));
}

// Badge ---------------------------------------------------------------------

/** Variants: default, secondary, destructive, outline. */
function badge(string $label, string $variant = 'default', ?string $icon = null): Element
{
    [$bg, $fg, $border] = match ($variant) {
        'secondary' => [t('secondary'), t('secondary-foreground'), null],
        'destructive' => [t('destructive'), '#ffffff', null],
        'outline' => [null, t('foreground'), t('border')],
        default => [t('primary'), t('primary-foreground'), null],
    };
    return row()->itemsCenter()->gap(4)->h(22)->px(8)->rounded(6)->self('start')
        ->textColor($fg)->textSize(12)->medium()
        ->when($bg !== null, fn ($e) => $e->bg($bg))
        ->border(1, $border ?? '#00000000')
        ->when($icon !== null, fn ($e) => $e->child(icon($icon, 12, $fg)))
        ->child($label);
}

// Card ----------------------------------------------------------------------

/** bg-card, rounded-xl, border, py-6 gap-6, shadow-sm. Children are card sections. */
function card(Element ...$sections): Element
{
    return col()->gap(24)->py(24)->rounded(14)->bg(t('card'))->textColor(t('card-foreground'))
        ->border(1, t('border'))->shadow('sm')->children($sections);
}

/** Title, optional description, and an optional action on the right. */
function cardHeader(string|Element $title, string|Element|null $description = null, ?Element $action = null): Element
{
    $titleEl = is_string($title) ? text($title)->semibold() : $title;
    $descEl = is_string($description) ? text($description)->textColor(t('muted-foreground'))->textSize(14) : $description;
    return row()->px(24)->gap(8)->itemsStart()
        ->child(col()->gap(6)->flex1()->child($titleEl)->child($descEl))
        ->child($action);
}

function cardContent(Element ...$children): Element
{
    return col()->px(24)->children($children);
}

function cardFooter(Element ...$children): Element
{
    return row()->itemsCenter()->px(24)->gap(8)->children($children);
}

// Inputs --------------------------------------------------------------------

/** h-9, rounded-md, border-input, px-3, shadow-xs; ring on focus. */
function textInput(string $id, string $value, callable $onInput, string $placeholder = '', ?string $icon = null): Element
{
    $e = input($id, $value, $onInput)->h(36)->pl($icon ? 34 : 12)->pr(12)->rounded(8)
        ->bg(Theme::dark() ? t('input', 0.3) : '#00000000')->border(1, t('input'))->shadow('xs')->textSize(14)
        ->focus(fn ($s) => $s->borderColor(t('ring'))->ring(3, t('ring', 0.5)));
    $e->placeholder = $placeholder;
    if (!$icon) return $e;
    return div()->h(36)->child($e->w('100%'))->child(icon($icon, 16, t('muted-foreground'))->absolute()->left(11)->top(10));
}

function label(string $text): Element
{
    return text($text)->textSize(14)->medium();
}

/** size-4, rounded-[4px], border-input; checked fills with primary and shows a check. */
function checkbox(bool $checked, callable $onToggle): Element
{
    return row()->center()->size(16)->rounded(4)->shadow('xs')
        ->when($checked,
            fn ($e) => $e->bg(t('primary'))->border(1, t('primary'))->icon('check', t('primary-foreground'), 14),
            fn ($e) => $e->bg(Theme::dark() ? t('input', 0.3) : '#00000000')->border(1, t('input')))
        ->onClick($onToggle);
}

/** h-[1.15rem] w-8 track, size-4 thumb. */
function switchToggle(bool $on, callable $onToggle): Element
{
    $track = $on ? t('primary') : (Theme::dark() ? t('input', 0.8) : t('input'));
    $thumb = Theme::dark() ? ($on ? t('primary-foreground') : t('foreground')) : t('background');
    return row()->itemsCenter()->w(32)->h(18)->rounded(9)->bg($track)->px(1)->shadow('xs')
        ->when($on, fn ($e) => $e->justifyEnd())
        ->child(div()->size(16)->rounded(8)->bg($thumb))
        ->onClick($onToggle);
}

/** A select trigger: shows the value and a chevron. Pair with dropdownMenu() for the options. */
function selectTrigger(string $value, callable $onClick, string $size = 'default'): Element
{
    return row()->itemsCenter()->justifyBetween()->gap(8)->h($size === 'sm' ? 32 : 36)->pl(12)->pr(10)->rounded(8)
        ->bg(Theme::dark() ? t('input', 0.3) : '#00000000')->border(1, t('input'))->shadow('xs')->textSize(14)
        ->hover(fn ($s) => $s->bg(Theme::dark() ? t('input', 0.5) : '#00000000'))
        ->child(text($value))
        ->child(icon('chevron-down', 16, t('muted-foreground'))->opacity(0.5))
        ->onClick($onClick);
}

// Tabs ----------------------------------------------------------------------

/** bg-muted rounded-lg p-[3px] h-9; the active trigger is bg-background with shadow-sm. */
function tabs(array $items, string $active, callable $onChange, string $idPrefix = 'tab'): Element
{
    $list = row()->h(36)->p(3)->rounded(10)->bg(t('muted'))->textColor(t('muted-foreground'))->self('start');
    foreach ($items as $value => $label) {
        $on = $value === $active;
        $list->child(row()->center()->gap(6)->px(8)->rounded(7)->textSize(14)->medium()->id("$idPrefix-$value")
            ->border(1, $on && Theme::dark() ? t('input') : '#00000000')
            ->when($on,
                fn ($e) => $e->bg(Theme::dark() ? t('input', 0.3) : t('background'))->textColor(t('foreground'))->shadow('sm'),
                fn ($e) => $e->hover(fn ($s) => $s->textColor(t('foreground'))))
            ->onClick(fn () => $onChange($value))
            ->child($label));
    }
    return $list;
}

/** A row of toggle buttons sharing one border (ToggleGroup variant outline). */
function toggleGroup(array $items, string $active, callable $onChange, string $idPrefix = 'toggle'): Element
{
    $group = row()->h(32)->rounded(8)->border(1, t('input'))->shadow('xs')->self('start')->clip();
    $keys = array_keys($items);
    foreach ($items as $value => $label) {
        $on = $value === $active;
        $group->child(row()->center()->px(12)->textSize(14)->medium()->id("$idPrefix-$value")
            ->when($value !== end($keys), fn ($e) => $e->borderR(1, t('input')))
            ->when($on, fn ($e) => $e->bg(t('accent')), fn ($e) => $e->hover(fn ($s) => $s->bg(t('muted'))))
            ->onClick(fn () => $onChange($value))
            ->child($label));
    }
    return $group;
}

// Table ---------------------------------------------------------------------

/**
 * $columns: [key => [label, width|null, align]] (null width shares the rest).
 * $rows: arrays of key => string|Element. Header h-10, rows border-b with hover:bg-muted/50.
 */
function table(array $columns, array $rows, ?callable $rowId = null, ?string $headBg = null, ?callable $rowStyle = null): Element
{
    $cell = function ($content, ?float $w, string $align, bool $head) {
        $el = is_string($content) ? text($content)->truncate()->when($align === 'right', fn ($e) => $e->textRight()) : $content;
        return row()->itemsCenter()->px(8)->when($w !== null, fn ($e) => $e->w($w), fn ($e) => $e->flex1())
            ->when($align === 'right', fn ($e) => $e->justifyEnd())
            ->when($head, fn ($e) => $e->medium())
            ->child($el->when(is_string($content), fn ($t) => $t->grow()));
    };

    $head = row()->h(40)->borderB(1, t('border'))->textSize(14)->when($headBg !== null, fn ($e) => $e->bg($headBg));
    foreach ($columns as [$label, $w, $align]) $head->child($cell($label, $w, $align ?? 'left', true));

    $body = col();
    foreach ($rows as $i => $r) {
        $line = row()->h(48)->textSize(14)->hover(fn ($s) => $s->bg(t('muted', 0.5)))
            ->when($i < count($rows) - 1, fn ($e) => $e->borderB(1, t('border')));
        if ($rowId) $line->id($rowId($i));
        if ($rowStyle) $rowStyle($line, $i);
        foreach ($columns as $key => [$label, $w, $align]) $line->child($cell($r[$key] ?? '', $w, $align ?? 'left', false));
        $body->child($line);
    }
    return col()->child($head)->child($body);
}

// Overlays ------------------------------------------------------------------

/** A dropdown menu panel. $items: [label, onClick, icon?, shortcut?] or 'separator' or ['label' => text]. */
function dropdownMenu(array $items, callable $onClose, float $width = 192): Element
{
    $menu = col()->w($width)->p(4)->rounded(8)->bg(t('popover'))->textColor(t('popover-foreground'))
        ->border(1, t('border'))->shadow('md')->stopClicks()->onClickOutside($onClose);
    foreach ($items as $item) {
        if ($item === 'separator') {
            $menu->child(col()->py(4)->child(separator()));
        } elseif (isset($item['label'])) {
            $menu->child(row()->itemsCenter()->h(32)->px(8)->textSize(14)->medium()->child($item['label']));
        } else {
            [$label, $onClick, $ico, $shortcut] = $item + [null, null, null, null];
            $destructive = ($item['variant'] ?? null) === 'destructive';
            $fg = $destructive ? t('destructive') : t('popover-foreground');
            $menu->child(row()->itemsCenter()->gap(8)->h(32)->px(8)->rounded(4)->textSize(14)->textColor($fg)
                ->hover(fn ($s) => $s->bg($destructive ? t('destructive', 0.1) : t('accent')))
                ->when($ico !== null, fn ($e) => $e->child(icon($ico, 16, $destructive ? $fg : t('muted-foreground'))))
                ->child(text($label)->grow())
                ->when($shortcut !== null, fn ($e) => $e->child(text($shortcut)->textSize(12)->tracking(1)->textColor(t('muted-foreground'))))
                ->onClick(function () use ($onClick, $onClose) {
                    $onClose();
                    if ($onClick) $onClick();
                }));
        }
    }
    return $menu;
}

/** A modal dialog: overlay, centered panel with title, description, body and footer. */
function dialog(string $title, string $description, Element $body, Element $footer, callable $onClose): Element
{
    return div()->absolute()->inset(0)->bg(t('overlay'))->center()->onClick($onClose)->cursor('default')
        ->child(col()->w(448)->gap(16)->p(24)->rounded(10)->bg(t('background'))->border(1, t('border'))->shadow('lg')
            ->stopClicks()
            ->child(row()->itemsStart()
                ->child(col()->gap(8)->flex1()
                    ->child(text($title)->textSize(18)->semibold())
                    ->child(text($description)->textSize(14)->textColor(t('muted-foreground'))))
                ->child(icon('x', 16, t('muted-foreground'))->opacity(0.7)->hover(fn ($s) => $s->opacity(1))->onClick($onClose)->id('dialog-close')))
            ->child($body)
            ->child($footer->justifyEnd()));
}

/** A pure message overlay; callbacks and queue ownership belong to the caller. */
function messageBox(string $title, string $message, array $buttons, callable $onButton, string $type = 'info', int $default = 0, array $destructive = []): Element
{
    $ico = match ($type) { 'warning', 'error' => 'circle-alert', 'question' => 'circle-help', default => 'info' };
    $footer = row()->gap(8)->justifyEnd();
    foreach ($buttons as $i => $label) {
        $variant = in_array($i, $destructive, true) ? 'destructive' : ($i === $default ? 'default' : 'outline');
        $footer->child(button($label, fn () => $onButton($i), $variant)->id("message-button-$i"));
    }
    $box = div()->absolute()->inset(0)->bg(t('overlay'))->center()->stopClicks()->id('message-overlay')
        ->child(col()->w(448)->gap(16)->p(24)->rounded(10)->bg(t('background'))->textColor(t('foreground'))
            ->border(1, t('border'))->shadow('lg')->stopClicks()->id('message-panel')
            ->child(row()->itemsCenter()->gap(10)->child(icon($ico, 20))->child(text($title)->textSize(18)->semibold()))
            ->child(paragraph($message, 400)->textColor(t('muted-foreground'))->id('message-text'))
            ->child($footer));
    $box->style['modalDefault'] = $default;
    return $box;
}

/** A tooltip shown while the pointer is over the parent: bg-foreground, text-xs. */
function tooltip(string $text, string $side = 'bottom'): Element
{
    $tip = row()->absolute()->hoverOnly()->centerX()->h(26)->px(12)->rounded(6)
        ->bg(t('foreground'))->textColor(t('background'))->textSize(12);
    return ($side === 'top' ? $tip->top(-32) : $tip->bottom(-32))->child($text);
}

/** A toast in the corner: bg-popover border shadow-lg, title and description. */
function toast(string $title, string $description, ?string $icon = 'circle-check'): Element
{
    return row()->absolute()->right(24)->bottom(24)->w(356)->gap(10)->p(16)->rounded(8)
        ->bg(t('popover'))->border(1, t('border'))->shadow('lg')->itemsStart()
        ->when($icon !== null, fn ($e) => $e->child(col()->pt(2)->child(icon($icon, 16, t('foreground')))))
        ->child(col()->gap(2)->flex1()
            ->child(text($title)->textSize(13)->semibold())
            ->child(text($description)->textSize(13)->textColor(t('muted-foreground'))->truncate()));
}

// Display -------------------------------------------------------------------

/** Fixed-width wrapped text, measured with the app's painter at build time. */
function paragraph(string $text, float $width, float $size = 14): Element
{
    $painter = Painter::$shared ?? throw new \LogicException('Create an App before building a paragraph');
    return col()->w($width)->textSize($size)->children(array_map(
        fn (string $line) => text($line)->font('regular')->h($painter->lineHeight($size)),
        $painter->wrap($text, $width, 'regular', $size),
    ));
}

function separator(bool $vertical = false): Element
{
    return $vertical ? div()->w(1)->bg(t('border')) : div()->h(1)->bg(t('border'));
}

/** size-8 rounded-full with fallback initials on bg-muted. */
function avatar(string $initials, float $size = 32, string $radius = 'full'): Element
{
    return row()->center()->size($size)->rounded($radius === 'full' ? $size / 2 : 8)->bg(t('muted'))
        ->textSize($size * 0.38)->medium()->child($initials);
}

/** bg-primary/20 h-2 rounded-full; the fill is bg-primary. */
function progress(float $value): Element
{
    $v = max(0, min(100, $value));
    return row()->h(8)->rounded(4)->bg(t('primary', 0.2))->clip()
        ->child(div()->w("$v%")->rounded(4)->bg(t('primary')));
}

/** A slider: track (bg-muted h-1.5), range (bg-primary) and thumb (size-4, border-primary). */
function slider(float $value, ?callable $onChange = null): Element
{
    $v = max(0, min(100, $value)) / 100;
    return canvas(function ($vg, Element $e, Painter $p) use ($v) {
        $cy = $e->y + $e->h / 2;
        $tx = $e->x + 8 + ($e->w - 16) * $v;
        $p->shape($e->x, $cy - 3, $e->w, 6, 3);
        $vg->fillColor($p->color(t('muted')));
        $vg->fill();
        $p->shape($e->x, $cy - 3, $tx - $e->x, 6, 3);
        $vg->fillColor($p->color(t('primary')));
        $vg->fill();
        $vg->beginPath();
        $vg->circle($tx, $cy, 7.5);
        $vg->fillColor($p->color(t('background')));
        $vg->fill();
        $vg->strokeWidth(1);
        $vg->strokeColor($p->color(t('primary')));
        $vg->stroke();
    })->h(16)
        ->when($onChange !== null, fn ($e) => $e->onClick(fn ($x, $y, $el) => $onChange(round(max(0, min(1, ($x - $el->x - 8) / ($el->w - 16))) * 100))));
}

/** Rounded skeleton placeholder: bg-accent rounded-md. */
function skeleton(float|string $w, float $h): Element
{
    return div()->w($w)->h($h)->rounded(6)->bg(t('accent'));
}

/** A notice box: border, rounded-lg, px-4 py-3, icon, title and description. */
function alert(string $title, string $description, string $icon = 'circle-alert', string $variant = 'default'): Element
{
    $fg = $variant === 'destructive' ? t('destructive') : t('card-foreground');
    return row()->gap(12)->px(16)->py(12)->rounded(10)->bg(t('card'))->border(1, t('border'))->itemsStart()
        ->child(col()->pt(2)->child(icon($icon, 16, $fg)))
        ->child(col()->gap(4)->flex1()
            ->child(text($title)->textSize(14)->medium()->textColor($fg))
            ->child(text($description)->textSize(14)->textColor($variant === 'destructive' ? t('destructive', 0.9) : t('muted-foreground'))->truncate()));
}

/** A keyboard key: bg-muted text-muted-foreground h-5 rounded-sm px-1 text-xs. */
function kbd(string $keys): Element
{
    return row()->center()->h(20)->px(4)->rounded(4)->bg(t('muted'))->textColor(t('muted-foreground'))
        ->textSize(12)->medium()->self('center')->child($keys);
}
