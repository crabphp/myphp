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


// Badge ---------------------------------------------------------------------


// Card ----------------------------------------------------------------------


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



function label(string $text): Element
{
    return text($text)->textSize(14)->medium();
}



/** A select trigger: shows the value and a chevron. Pair with dropdownMenu() for the options. */
function selectTrigger(string $value, callable $onClick, string $size = 'default'): Element
{
    return row()->itemsCenter()->justifyBetween()->gap(8)->h($size === 'sm' ? 32 : 36)->pl(12)->pr(10)->rounded(8)
        ->bg(Theme::dark() ? t('input', 0.3) : '#00000000')->border(1, t('input'))->shadow('xs')->textSize(14)
        ->transition()->hover(fn ($s) => $s->bg(Theme::dark() ? t('input', 0.5) : '#00000000'))
        ->child(text($value))
        ->child(icon('chevron-down', 16, t('muted-foreground'))->opacity(0.5))
        ->role('combobox')->label($value)->onClick($onClick);
}


// Tabs ----------------------------------------------------------------------



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
function dropdownMenu(array $items, callable $onClose, float $width = 192, ?string $idPrefix = null): Element
{
    $menu = col()->w($width)->p(4)->rounded(8)->bg(t('popover'))->textColor(t('popover-foreground'))
        ->border(1, t('border'))->shadow('md')->stopClicks()->onClickOutside($onClose)->onEscape($onClose)->animateIn(120)->role('menu');
    $n = 0;
    foreach ($items as $item) {
        if ($item === 'separator') {
            $menu->child(col()->py(4)->child(separator()));
        } elseif (isset($item['label'])) {
            $menu->child(row()->itemsCenter()->h(32)->px(8)->textSize(14)->medium()->child($item['label']));
        } else {
            [$label, $onClick, $ico, $shortcut] = $item + [null, null, null, null];
            $destructive = ($item['variant'] ?? null) === 'destructive';
            $fg = $destructive ? t('destructive') : t('popover-foreground');
            $menu->child(row()->itemsCenter()->gap(8)->h(32)->px(8)->rounded(4)->textSize(14)->textColor($fg)->transition(100)
                ->when($idPrefix !== null, fn ($e) => $e->id($idPrefix . '-' . $n))->role('menuitem', isset($item['checked']) ? ['checked' => $item['checked']] : [])->label($label)
                ->hover(fn ($s) => $s->bg($destructive ? t('destructive', 0.1) : t('accent')))
                ->when($ico !== null, fn ($e) => $e->child(icon($ico, 16, $destructive ? $fg : t('muted-foreground'))))
                ->child(text($label)->grow())
                ->when($shortcut !== null, fn ($e) => $e->child(text($shortcut)->textSize(12)->tracking(1)->textColor(t('muted-foreground'))))
                ->when(isset($item['checked']), fn ($e) => $e->child($item['checked'] ? icon('check', 16, t('foreground')) : div()->size(16)))
                ->onClick(function () use ($onClick, $onClose) {
                    $onClose();
                    if ($onClick) $onClick();
                }));
            $n++;
        }
    }
    return $menu;
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
    $box = div()->absolute()->inset(0)->bg(t('overlay'))->center()->stopClicks()->id('message-overlay')->animateIn(150)
        ->child(col()->w(448)->gap(16)->p(24)->rounded(10)->bg(t('background'))->textColor(t('foreground'))
            ->border(1, t('border'))->shadow('lg')->stopClicks()->id('message-panel')
            ->child(row()->itemsCenter()->gap(10)->child(icon($ico, 20))->child(text($title)->textSize(18)->semibold()))
            ->child(paragraph($message, 400)->textColor(t('muted-foreground'))->selectable()->id('message-text'))
            ->child($footer));
    $box->style['modalDefault'] = $default;
    return $box;
}

/** A tooltip shown while the pointer is over the parent: bg-foreground, text-xs. */
function tooltip(string $text, string $side = 'bottom'): Element
{
    $tip = row()->absolute()->hoverOnly()->centerX()->h(26)->px(12)->rounded(6)->animateIn(100)
        ->bg(t('foreground'))->textColor(t('background'))->textSize(12);
    return ($side === 'top' ? $tip->top(-32) : $tip->bottom(-32))->child($text);
}


// Display -------------------------------------------------------------------

/** Fixed-width wrapped text, measured with the app's painter at build time. */
/** Text that wraps to its width; without $width it fills the space its parent gives it. */
function paragraph(string $text, ?float $width = null, float $size = 14): Element
{
    $e = text($text)->wrap()->textSize($size)->font('regular');
    return $width === null ? $e : $e->w($width);
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
    return row()->h(8)->rounded(4)->bg(t('primary', 0.2))->clip()->role('progressbar', ['value' => $v])
        ->child(div()->w("$v%")->rounded(4)->bg(t('primary')));
}


/** Rounded skeleton placeholder: bg-accent rounded-md. */
function skeleton(float|string $w, float $h): Element
{
    return div()->w($w)->h($h)->rounded(6)->bg(t('accent'));
}


/** A keyboard key: bg-muted text-muted-foreground h-5 rounded-sm px-1 text-xs. */
function kbd(string $keys): Element
{
    return row()->center()->h(20)->px(4)->rounded(4)->bg(t('muted'))->textColor(t('muted-foreground'))
        ->textSize(12)->medium()->self('center')->child($keys);
}

// Chainable components (src/Components.php) --------------------------------------
// The positional arguments are the older form and keep working.

/** button('Save')->color('primary')->size('sm')->icon('check')->onClick($save) */
function button(string $label = '', ?callable $onClick = null, string $variant = 'default', string $size = 'default', ?string $icon = null): Button
{
    $b = (new Button($label))->variant($variant)->size($size);
    if ($icon !== null) $b->icon($icon);
    return $onClick === null ? $b : $b->onClick($onClick);
}

/** badge('New')->color('secondary')->icon('sparkles') */
function badge(string $label = '', string $variant = 'default', ?string $icon = null): Badge
{
    $b = (new Badge($label))->variant($variant);
    return $icon === null ? $b : $b->icon($icon);
}

/** card()->heading('Plan')->description('For teams')->schema([...])->footer([...]); or card(cardHeader(...), cardContent(...)) */
function card(Element ...$sections): Card
{
    return (new Card())->children($sections);
}

/** textInput('email')->label('Email')->placeholder('you@example.com')->prefixIcon('mail')->bind($state->email) */
function textInput(string $name = '', ?string $value = null, ?callable $onInput = null, string $placeholder = '', ?string $icon = null): TextInput
{
    $e = new TextInput($name, $value ?? '', $placeholder);
    if ($onInput !== null) $e->onInput($onInput);
    return $icon === null ? $e : $e->prefixIcon($icon);
}

/** textarea('bio')->rows(4)->label('Bio')->bind($state->bio). Enter adds a line, Ctrl/Cmd+Enter submits. */
function textarea(string $name = '', ?string $value = null, ?callable $onInput = null, string $placeholder = '', int $rows = 4): Textarea
{
    $e = (new Textarea($name, $value ?? '', $placeholder))->rows($rows);
    return $onInput === null ? $e : $e->onInput($onInput);
}

/** checkbox()->label('Accept the terms')->bind($state->agree) */
function checkbox(?bool $checked = null, ?callable $onToggle = null): Checkbox
{
    return new Checkbox($checked ?? false, $onToggle === null ? null : $onToggle(...));
}

/** switchToggle()->label('Email me')->bind($state->emails) */
function switchToggle(?bool $on = null, ?callable $onToggle = null): SwitchToggle
{
    return new SwitchToggle($on ?? false, $onToggle === null ? null : $onToggle(...));
}

/** select('plan')->options(['free' => 'Free', 'pro' => 'Pro'])->bind($state->plan). $name must be unique. */
function select(string $name = 'select', ?string $value = null, array $options = [], ?callable $onChange = null, string $placeholder = 'Select…', string $size = 'default'): Select
{
    return (new Select($name, $value, $options, $onChange === null ? null : $onChange(...), $placeholder))->size($size);
}

/** slider()->bind($state->volume); min(), max() and step() change the range (default 0 to 100). */
function slider(?float $value = null, ?callable $onChange = null): Slider
{
    return new Slider($value ?? 0, $onChange === null ? null : $onChange(...));
}

/** tabs(['account' => 'Account', 'team' => 'Team'])->bind($state->tab) */
function tabs(array $items = [], ?string $active = null, ?callable $onChange = null, string $idPrefix = 'tab'): Tabs
{
    return new Tabs($items, $active, $onChange === null ? null : $onChange(...), $idPrefix);
}

/** toggleGroup(['grid' => 'Grid', 'list' => 'List'])->bind($state->view) */
function toggleGroup(array $items = [], ?string $active = null, ?callable $onChange = null, string $idPrefix = 'toggle'): ToggleGroup
{
    return new ToggleGroup($items, $active, $onChange === null ? null : $onChange(...), $idPrefix);
}

/** dialog()->heading('Rename')->description('...')->schema([...])->footer([...])->onClose($close) */
function dialog(string $title = '', string $description = '', ?Element $body = null, ?Element $footer = null, ?callable $onClose = null): Dialog
{
    $d = new Dialog($title, $description, $onClose === null ? null : $onClose(...));
    if ($body !== null) $d->schema([$body]);
    return $footer === null ? $d : $d->footer($footer);
}

/** alert('Heads up')->description('...')->icon('info')->color('danger') */
function alert(string $title = '', string $description = '', string $icon = 'circle-alert', string $variant = 'default'): Alert
{
    return (new Alert($title, $description, $icon))->variant($variant);
}

/** toast('Saved')->description('Your changes are safe.') */
function toast(string $title = '', string $description = '', ?string $icon = 'circle-check'): Toast
{
    return new Toast($title, $description, $icon);
}
