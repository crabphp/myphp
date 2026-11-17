<?php

declare(strict_types=1);

namespace PhpUi;

/*
 * Components configured by chaining, Filament style:
 *
 *     card()->heading('Plan')->description('For teams')->schema([...])->footer([...])
 *     textInput('email')->label('Email')->placeholder('you@example.com')->bind($state->email)
 *
 * Each is an Element that builds its look from its settings right before layout,
 * so methods can be called in any order. Styling you add yourself (->w(), ->id(),
 * ->textColor(), ...) wins over the component's own.
 */

/** Base for components that build their elements from chained settings. */
abstract class Component extends Element
{
    private bool $composed = false;

    public function __construct(string $kind = 'div')
    {
        parent::__construct($kind);
    }

    /** The component's look for its current settings. */
    abstract protected function build(): Element;

    /** @internal App calls this once before layout. */
    public function compose(): void
    {
        if ($this->composed) return;
        $this->composed = true;
        $built = $this->build();
        $this->style += $built->style;
        $this->hoverStyle += $built->hoverStyle;
        $this->focusStyle += $built->focusStyle;
        $this->children = [...$built->children, ...$this->children];
        foreach (['onClick', 'onClickOutside', 'onInput', 'onSubmit', 'onDrop', 'onDrag', 'onRightClick', 'onKeyDown', 'onEscape', 'draw'] as $handler) {
            $this->$handler ??= $built->$handler;
        }
        $this->label ??= $built->label;
        $this->id ??= $built->id;
        if ($this->placeholder === '') $this->placeholder = $built->placeholder;
    }
}

/**
 * Two-way binding: ->bind($state->email) shows the property and writes changes back
 * to it; ->afterStateUpdated($fn) runs after each change.
 */
trait BindsState
{
    protected mixed $bound = null;
    protected bool $isBound = false;
    protected ?\Closure $afterUpdate = null;

    public function bind(mixed &$value): static
    {
        $this->bound = &$value;
        $this->isBound = true;
        return $this;
    }

    public function afterStateUpdated(callable $fn): static
    {
        $this->afterUpdate = $fn(...);
        return $this;
    }

    protected function current(mixed $fallback): mixed
    {
        return $this->isBound ? $this->bound : $fallback;
    }

    /** Store a new value in the bound property, in that property's type. */
    protected function write(mixed $value): void
    {
        if ($this->isBound) {
            $old = $this->bound;
            $this->bound = match (true) {
                is_int($old) && is_numeric($value) => (int) round((float) $value),
                is_float($old) && is_numeric($value) => (float) $value,
                is_bool($old) => (bool) $value,
                is_string($old) && !is_string($value) => (string) $value,
                default => $value,
            };
            $value = $this->bound;
        }
        if ($this->afterUpdate) ($this->afterUpdate)($value);
    }
}

// Button and badge ------------------------------------------------------------

/** button('Save')->color('primary')->size('sm')->icon('check')->onClick($save) */
class Button extends Component
{
    private string $variant = 'default';
    private string $buttonSize = 'default';
    private ?string $iconName = null;
    private bool $iconAfter = false;
    private ?string $iconTint = null;
    private bool $disabled = false;
    private ?string $tip = null;

    public function __construct(private string $caption = '')
    {
        parent::__construct();
    }

    /** The button's text (and its accessible name). */
    public function label(string $name): static { $this->caption = $name; return $this; }
    /** default, secondary, outline, ghost, destructive, link. */
    public function variant(string $variant): static { $this->variant = $variant; return $this; }
    /** primary, gray, danger, or any variant name. */
    public function color(string $color): static
    {
        return $this->variant(match ($color) { 'primary' => 'default', 'gray' => 'secondary', 'danger' => 'destructive', default => $color });
    }
    public function outlined(bool $on = true): static { return $this->variant($on ? 'outline' : 'default'); }
    /** 'default', 'sm', 'lg', 'icon' or 'icon-sm'; a number sets width and height, as on any element. */
    public function size(string|float $size): static
    {
        if (is_string($size) && !is_numeric($size)) { $this->buttonSize = $size; return $this; }
        return parent::size($size);
    }
    /** A Lucide icon before the label (or after, with iconPosition('after')). */
    public function icon(string $name, ?string $color = null, float $size = 16): static { $this->iconName = $name; return $this; }
    public function iconPosition(string $position): static { $this->iconAfter = $position === 'after'; return $this; }
    public function iconColor(string $color): static { $this->iconTint = $color; return $this; }
    public function disabled(bool $on = true): static { $this->disabled = $on; return $this; }
    public function tooltip(string $text): static { $this->tip = $text; return $this; }

    protected function build(): Element
    {
        [$bg, $hover, $fg, $border] = match ($this->variant) {
            'secondary' => [t('secondary'), t('secondary', 0.8), t('secondary-foreground'), null],
            'outline' => [Theme::dark() ? t('input', 0.3) : t('background'), t('accent'), t('foreground'), t('input')],
            'ghost' => [null, t('accent'), t('accent-foreground'), null],
            'destructive' => [t('destructive'), t('destructive', 0.9), '#ffffff', null],
            'link' => [null, null, t('primary'), null],
            default => [t('primary'), t('primary', 0.9), t('primary-foreground'), null],
        };
        [$h, $px] = match ($this->buttonSize) {
            'sm' => [32, 12], 'lg' => [40, 24], 'icon' => [36, 0], 'icon-sm' => [32, 0], default => [36, 16],
        };
        $iconOnly = str_starts_with($this->buttonSize, 'icon');
        // On an icon-only button the label names it for screen readers but is not drawn.
        $shown = $iconOnly ? '' : $this->caption;
        $ico = $this->iconName === null ? null : icon($this->iconName, 16, $this->iconTint ?? $fg);
        return row()->center()->gap(8)->h($h)
            ->when($iconOnly, fn ($e) => $e->w($h), fn ($e) => $e->px($this->iconName && $shown !== '' ? $px - 4 : $px))
            ->rounded(8)->textColor($fg)->medium()->textSize(14)->transition()
            ->when($bg !== null, fn ($e) => $e->bg($bg))
            ->when($hover !== null && !$this->disabled, fn ($e) => $e->hover(fn ($s) => $s->bg($hover)))
            ->when($border !== null, fn ($e) => $e->border(1, $border))
            ->when(in_array($this->variant, ['default', 'outline', 'destructive', 'secondary'], true), fn ($e) => $e->shadow('xs'))
            ->when($ico !== null && !$this->iconAfter, fn ($e) => $e->child($ico))
            ->when($shown !== '', fn ($e) => $e->child($shown))
            ->when($ico !== null && $this->iconAfter, fn ($e) => $e->child($ico))
            ->when($this->tip !== null, fn ($e) => $e->child(tooltip($this->tip)))
            ->role('button', $this->disabled ? ['disabled' => true] : [])
            ->when($this->caption !== '', fn ($e) => $e->label($this->caption))
            ->when($this->disabled, fn ($e) => $e->opacity(0.5));
    }

    public function compose(): void
    {
        parent::compose();
        if ($this->disabled) {
            [$this->onClick, $this->style['focusable']] = [null, false];
            unset($this->style['cursor']);
        } elseif ($this->onClick === null) {
            $this->style['cursor'] ??= 'pointer';
        }
    }
}

/** badge('New')->color('secondary')->icon('sparkles') */
class Badge extends Component
{
    private string $variant = 'default';
    private ?string $iconName = null;

    public function __construct(private string $caption = '')
    {
        parent::__construct();
    }

    public function label(string $name): static { $this->caption = $name; return $this; }
    /** default, secondary, destructive, outline. */
    public function variant(string $variant): static { $this->variant = $variant; return $this; }
    public function color(string $color): static
    {
        return $this->variant(match ($color) { 'primary' => 'default', 'gray' => 'secondary', 'danger' => 'destructive', default => $color });
    }
    public function icon(string $name, ?string $color = null, float $size = 16): static { $this->iconName = $name; return $this; }

    protected function build(): Element
    {
        [$bg, $fg, $border] = match ($this->variant) {
            'secondary' => [t('secondary'), t('secondary-foreground'), null],
            'destructive' => [t('destructive'), '#ffffff', null],
            'outline' => [null, t('foreground'), t('border')],
            default => [t('primary'), t('primary-foreground'), null],
        };
        return row()->itemsCenter()->gap(4)->h(22)->px(8)->rounded(6)->self('start')
            ->textColor($fg)->textSize(12)->medium()
            ->when($bg !== null, fn ($e) => $e->bg($bg))
            ->border(1, $border ?? '#00000000')
            ->when($this->iconName !== null, fn ($e) => $e->child(icon($this->iconName, 12, $fg)))
            ->child($this->caption);
    }
}

// Card ------------------------------------------------------------------------

/** card()->heading('Plan')->description('...')->headerActions([...])->schema([...])->footer([...]) */
class Card extends Component
{
    private string|Element|null $heading = null;
    private string|Element|null $description = null;
    private array $actions = [];
    private array $content = [];
    private array $footer = [];

    public function heading(string|Element $heading): static { $this->heading = $heading; return $this; }
    public function description(string|Element $description): static { $this->description = $description; return $this; }
    public function headerActions(array $actions): static { $this->actions = $actions; return $this; }
    public function headerAction(Element $action): static { $this->actions[] = $action; return $this; }
    /** The card's content. */
    public function schema(iterable $schema): static { array_push($this->content, ...$schema); return $this; }
    public function footer(array|Element $footer): static
    {
        array_push($this->footer, ...(is_array($footer) ? $footer : [$footer]));
        return $this;
    }

    protected function build(): Element
    {
        $action = match (count($this->actions)) { 0 => null, 1 => $this->actions[0], default => row()->gap(8)->children($this->actions) };
        return col()->gap(24)->py(24)->rounded(14)->bg(t('card'))->textColor(t('card-foreground'))->border(1, t('border'))->shadow('sm')
            ->when($this->heading !== null || $this->description !== null || $action !== null,
                fn ($e) => $e->child(cardHeader($this->heading ?? '', $this->description, $action)))
            ->when($this->content !== [], fn ($e) => $e->child(cardContent(col()->gap(12)->children($this->content))))
            ->when($this->footer !== [], fn ($e) => $e->child(cardFooter(...$this->footer)));
    }
}

// Fields ----------------------------------------------------------------------

/** Field parts shared by inputs and selects: a label above, helper text below. */
trait FieldLabel
{
    protected ?string $fieldLabel = null;
    protected ?string $helper = null;

    /** Shown above the field, and its accessible name. */
    public function label(string $name): static { $this->fieldLabel = $name; return $this; }
    /** Small text below the field. */
    public function helperText(string $text): static { $this->helper = $text; return $this; }

    protected function field(Element $control): Element
    {
        if ($this->fieldLabel === null && $this->helper === null) return col()->child($control);
        return col()->gap(8)
            ->when($this->fieldLabel !== null, fn ($e) => $e->child(label($this->fieldLabel)))
            ->child($control)
            ->when($this->helper !== null, fn ($e) => $e->child(paragraph($this->helper)->textSize(13)->textColor(t('muted-foreground'))));
    }
}

/** textInput('email')->label('Email')->placeholder('you@example.com')->prefixIcon('mail')->bind($state->email) */
class TextInput extends Component
{
    use BindsState, FieldLabel;

    protected ?string $iconName = null;
    protected ?\Closure $changed = null;
    protected ?\Closure $submitted = null;
    protected ?int $rows = null;

    public function __construct(protected string $name = '', protected string $value = '', protected string $hint = '')
    {
        parent::__construct();
    }

    /** Shown while the field is empty. */
    public function placeholder(string $text): static { $this->hint = $text; return $this; }
    public function prefixIcon(string $name): static { $this->iconName = $name; return $this; }
    public function value(string $value): static { $this->value = $value; return $this; }
    /** Called with each new value (with bind(), after the property is updated). */
    public function onInput(callable $fn): static { $this->changed = $fn(...); return $this; }
    /** Enter in a one-line input, Ctrl/Cmd+Enter in a textarea. */
    public function onSubmit(callable $fn): static { $this->submitted = $fn(...); return $this; }

    protected function build(): Element
    {
        $input = input($this->name, (string) $this->current($this->value), function (string $v) {
            if ($this->changed) ($this->changed)($v);
            $this->write($v);
        })->rounded(8)->bg(Theme::dark() ? t('input', 0.3) : '#00000000')->border(1, t('input'))->shadow('xs')->textSize(14)
            ->transition()->focus(fn ($s) => $s->borderColor(t('ring'))->ring(3, t('ring', 0.5)));
        $input->placeholder = $this->hint;
        if ($this->submitted) $input->onSubmit($this->submitted);
        if ($this->fieldLabel !== null) $input->label($this->fieldLabel);
        if ($this->rows !== null) {
            $control = $input->multiline($this->rows)->px(12)->py(8)->grow();
        } else {
            $input->h(36)->pl($this->iconName ? 34 : 12)->pr(12);
            $control = $this->iconName === null ? $input
                : div()->h(36)->child($input->w('100%'))->child(icon($this->iconName, 16, t('muted-foreground'))->absolute()->left(11)->top(10));
        }
        return $this->field($control->when($this->rows !== null, fn ($e) => $e->grow()));
    }
}

/** textarea('bio')->rows(4)->label('Bio')->bind($state->bio) */
class Textarea extends TextInput
{
    protected ?int $rows = 4;

    public function rows(int $rows): static { $this->rows = max(1, $rows); return $this; }
}

/** checkbox()->label('Accept the terms')->bind($state->agree) */
class Checkbox extends Component
{
    use BindsState;

    private ?string $caption = null;

    public function __construct(private bool $checked = false, private ?\Closure $toggled = null)
    {
        parent::__construct();
    }

    public function label(string $name): static { $this->caption = $name; return $this; }
    public function checked(bool $on = true): static { $this->checked = $on; return $this; }
    /** Called on each click (with bind(), after the property is updated). */
    public function onToggle(callable $fn): static { $this->toggled = $fn(...); return $this; }

    protected function build(): Element
    {
        $on = (bool) $this->current($this->checked);
        $toggle = function () use ($on) {
            if ($this->toggled) ($this->toggled)();
            $this->write(!$on);
        };
        $box = row()->center()->size(16)->rounded(4)->shadow('xs')->transition()
            ->when($on,
                fn ($e) => $e->bg(t('primary'))->border(1, t('primary'))->icon('check', t('primary-foreground'), 14),
                fn ($e) => $e->bg(Theme::dark() ? t('input', 0.3) : '#00000000')->border(1, t('input')));
        $control = $this->caption === null ? $box
            : row()->itemsCenter()->gap(8)->child($box)->child(text($this->caption)->textSize(14)->medium())->cursor('pointer');
        return $control->role('checkbox', ['checked' => $on])->onClick($toggle)
            ->when($this->caption !== null, fn ($e) => $e->label($this->caption));
    }
}

/** switchToggle()->label('Email me')->bind($state->emails) */
class SwitchToggle extends Component
{
    use BindsState;

    private ?string $caption = null;

    public function __construct(private bool $on = false, private ?\Closure $toggled = null)
    {
        parent::__construct();
    }

    public function label(string $name): static { $this->caption = $name; return $this; }
    public function on(bool $on = true): static { $this->on = $on; return $this; }
    public function onToggle(callable $fn): static { $this->toggled = $fn(...); return $this; }

    protected function build(): Element
    {
        $on = (bool) $this->current($this->on);
        $toggle = function () use ($on) {
            if ($this->toggled) ($this->toggled)();
            $this->write(!$on);
        };
        $track = $on ? t('primary') : (Theme::dark() ? t('input', 0.8) : t('input'));
        $thumb = Theme::dark() ? ($on ? t('primary-foreground') : t('foreground')) : t('background');
        $switch = row()->itemsCenter()->w(32)->h(18)->rounded(9)->bg($track)->px(1)->shadow('xs')->transition()
            ->when($on, fn ($e) => $e->justifyEnd())
            ->child(div()->size(16)->rounded(8)->bg($thumb));
        $control = $this->caption === null ? $switch
            : row()->itemsCenter()->gap(8)->child($switch)->child(text($this->caption)->textSize(14)->medium())->cursor('pointer');
        return $control->role('switch', ['checked' => $on])->onClick($toggle)
            ->when($this->caption !== null, fn ($e) => $e->label($this->caption));
    }
}

/** select('plan')->options(['free' => 'Free', 'pro' => 'Pro'])->placeholder('Choose')->bind($state->plan) */
class Select extends Component
{
    use BindsState, FieldLabel;

    private string $selectSize = 'default';

    public function __construct(private string $name = 'select', private ?string $value = null, private array $choices = [],
        private ?\Closure $changed = null, private string $hint = 'Select…')
    {
        parent::__construct();
    }

    /** [value => label] */
    public function options(array $options): static { $this->choices = $options; return $this; }
    public function placeholder(string $text): static { $this->hint = $text; return $this; }
    /** 'default' or 'sm'; a number sets width and height, as on any element. */
    public function size(string|float $size): static
    {
        if (is_string($size) && !is_numeric($size)) { $this->selectSize = $size; return $this; }
        return parent::size($size);
    }
    public function onChange(callable $fn): static { $this->changed = $fn(...); return $this; }

    protected function build(): Element
    {
        $id = $this->name;
        $value = $this->current($this->value);
        $value = $value === null ? null : (string) $value;
        $menu = state(['open' => false], "select:$id");
        $known = $value !== null && isset($this->choices[$value]);
        $label = $known ? (string) $this->choices[$value] : $this->hint;
        $items = [];
        foreach ($this->choices as $key => $text) {
            $items[] = [(string) $text, function () use ($key) {
                if ($this->changed) ($this->changed)((string) $key);
                $this->write((string) $key);
            }, 'checked' => (string) $key === $value];
        }
        $h = $this->selectSize === 'sm' ? 32 : 36;
        $select = div()
            ->child(selectTrigger($label, fn () => $menu->open = !$menu->open, $this->selectSize)->id("$id-trigger")
                ->role('combobox', ['expanded' => $menu->open, 'value' => $label])
                ->when($this->fieldLabel !== null, fn ($e) => $e->label($this->fieldLabel))
                // Down opens the menu and moves into it, as in native selects.
                ->onKeyDown(function (int $key) use ($menu, $id) {
                    if ($key !== Key::DOWN) return false;
                    $menu->open = true;
                    App::current()?->focusElement("$id-menu-0");
                    return true;
                })
                ->when(!$known, fn ($e) => $e->textColor(t('muted-foreground'))))
            ->when($menu->open, fn ($e) => $e->child(
                dropdownMenu($items, function () use ($menu, $id) { $menu->open = false; App::current()?->focusElement("$id-trigger"); }, 0, "$id-menu")
                    ->w('100%')->absolute()->top($h + 4)->left(0)->id("$id-menu")));
        if ($this->fieldLabel === null && $this->helper === null) return $select->id($id);
        return $this->field($select)->id($id);
    }
}

/** slider()->min(0)->max(10)->step(1)->bind($state->rating) */
class Slider extends Component
{
    use BindsState;

    private float $min = 0, $max = 100;
    private ?float $step = null;

    public function __construct(private float $value = 0, private ?\Closure $changed = null)
    {
        parent::__construct('canvas');
    }

    public function min(float $min): static { $this->min = $min; return $this; }
    public function max(float $max): static { $this->max = $max; return $this; }
    /** Values snap to this; arrow keys move by it. Default: a twentieth of the range. */
    public function step(float $step): static { $this->step = $step; return $this; }
    public function onChange(callable $fn): static { $this->changed = $fn(...); return $this; }

    protected function build(): Element
    {
        $range = max(0.000001, $this->max - $this->min);
        $step = $this->step ?? $range / 20;
        $value = max($this->min, min($this->max, (float) $this->current($this->value)));
        $v = ($value - $this->min) / $range;
        $set = function (float $to) use ($step, $value) {
            $to = max($this->min, min($this->max, $this->min + round(($to - $this->min) / $step) * $step));
            if ($to == $value) return;
            if ($this->changed) ($this->changed)($to);
            $this->write($to);
        };
        $draw = function (Canvas $c) use ($v) {
            $cy = $c->height / 2;
            $tx = 8 + ($c->width - 16) * $v;
            $c->fillStyle = t('muted');
            $c->beginPath();
            $c->roundRect(0, $cy - 3, $c->width, 6, 3);
            $c->fill();
            $c->fillStyle = t('primary');
            $c->beginPath();
            $c->roundRect(0, $cy - 3, $tx, 6, 3);
            $c->fill();
            $c->beginPath();
            $c->arc($tx, $cy, 7.5, 0, 2 * M_PI);
            $c->fillStyle = t('background');
            $c->fill();
            $c->lineWidth = 1;
            $c->strokeStyle = t('primary');
            $c->stroke();
        };
        $interactive = $this->changed !== null || $this->isBound || $this->afterUpdate !== null;
        return canvas($draw)->h(16)->role('slider', ['value' => $value])
            ->when($interactive, fn ($e) => $e
                ->onDrag(fn ($x, $y, $el) => $set($this->min + max(0, min(1, ($x - $el->x - 8) / max(1, $el->w - 16))) * $range))
                // Arrows move one step, Page Up/Down two, Home/End to the ends.
                ->onKeyDown(function (int $key) use ($set, $value, $step) {
                    $to = match ($key) {
                        Key::RIGHT, Key::UP => $value + $step, Key::LEFT, Key::DOWN => $value - $step,
                        Key::PAGE_UP => $value + 2 * $step, Key::PAGE_DOWN => $value - 2 * $step,
                        Key::HOME => $this->min, Key::END => $this->max, default => null,
                    };
                    if ($to === null) return false;
                    $set($to);
                    return true;
                }));
    }
}

/** tabs(['account' => 'Account', 'team' => 'Team'])->bind($state->tab) */
class Tabs extends Component
{
    use BindsState;

    public function __construct(protected array $items = [], protected ?string $active = null, protected ?\Closure $changed = null, protected string $prefix = 'tab')
    {
        parent::__construct();
    }

    /** [value => label] */
    public function options(array $options): static { $this->items = $options; return $this; }
    /** Ids of the items: "$prefix-$value". */
    public function idPrefix(string $prefix): static { $this->prefix = $prefix; return $this; }
    public function onChange(callable $fn): static { $this->changed = $fn(...); return $this; }

    protected function pick(string $value): \Closure
    {
        return function () use ($value) {
            if ($this->changed) ($this->changed)($value);
            $this->write($value);
        };
    }

    protected function activeValue(): string
    {
        return (string) ($this->current($this->active) ?? array_key_first($this->items));
    }

    protected function build(): Element
    {
        $active = $this->activeValue();
        $list = row()->h(36)->p(3)->rounded(10)->bg(t('muted'))->textColor(t('muted-foreground'))->self('start')->role('tablist');
        foreach ($this->items as $value => $label) {
            $on = (string) $value === $active;
            $list->child(row()->center()->gap(6)->px(8)->rounded(7)->textSize(14)->medium()->id("{$this->prefix}-$value")->transition()
                ->border(1, $on && Theme::dark() ? t('input') : '#00000000')
                ->when($on,
                    fn ($e) => $e->bg(Theme::dark() ? t('input', 0.3) : t('background'))->textColor(t('foreground'))->shadow('sm'),
                    fn ($e) => $e->hover(fn ($s) => $s->textColor(t('foreground'))))
                ->role('tab', ['selected' => $on])->tabStop($on)->onClick($this->pick((string) $value))
                ->child($label));
        }
        return $list;
    }
}

/** toggleGroup(['grid' => 'Grid', 'list' => 'List'])->bind($state->view) */
class ToggleGroup extends Tabs
{
    protected function build(): Element
    {
        $active = $this->activeValue();
        $group = row()->h(32)->rounded(8)->border(1, t('input'))->shadow('xs')->self('start')->clip()->role('radiogroup');
        $keys = array_keys($this->items);
        foreach ($this->items as $value => $label) {
            $on = (string) $value === $active;
            $group->child(row()->center()->px(12)->textSize(14)->medium()->id("{$this->prefix}-$value")->transition()
                ->when($value !== end($keys), fn ($e) => $e->borderR(1, t('input')))
                ->when($on, fn ($e) => $e->bg(t('accent')), fn ($e) => $e->hover(fn ($s) => $s->bg(t('muted'))))
                ->role('radio', ['checked' => $on])->tabStop($on)
                ->onClick($this->pick((string) $value))
                ->child($label));
        }
        return $group;
    }
}

// Overlays and notices ----------------------------------------------------------

/** dialog()->heading('Rename')->description('...')->schema([...])->footer([...])->onClose($close) */
class Dialog extends Component
{
    private array $body = [];
    private array $footer = [];
    private float $width = 448;

    public function __construct(private string $title = '', private string $caption = '', private ?\Closure $closed = null)
    {
        parent::__construct();
    }

    public function heading(string $title): static { $this->title = $title; return $this; }
    public function description(string $text): static { $this->caption = $text; return $this; }
    public function schema(iterable $schema): static { array_push($this->body, ...$schema); return $this; }
    public function footer(array|Element $footer): static
    {
        array_push($this->footer, ...(is_array($footer) ? $footer : [$footer]));
        return $this;
    }
    /** Called by the close button, a click outside, and Escape. */
    public function onClose(callable $fn): static { $this->closed = $fn(...); return $this; }
    public function width(float $px): static { $this->width = $px; return $this; }

    protected function build(): Element
    {
        $close = fn () => $this->closed ? ($this->closed)() : null;
        return div()->absolute()->inset(0)->bg(t('overlay'))->center()->onClick($close)->cursor('default')->animateIn(150)
            ->focusable(false)->onEscape($close)->trapFocus()
            ->child(col()->w($this->width)->gap(16)->p(24)->rounded(10)->bg(t('background'))->border(1, t('border'))->shadow('lg')
                ->stopClicks()->role('dialog')->label($this->title)
                ->child(row()->itemsStart()
                    ->child(col()->gap(8)->flex1()
                        ->child(text($this->title)->textSize(18)->semibold())
                        ->when($this->caption !== '', fn ($e) => $e->child(paragraph($this->caption)->textColor(t('muted-foreground')))))
                    ->child(icon('x', 16, t('muted-foreground'))->opacity(0.7)->hover(fn ($s) => $s->opacity(1))->onClick($close)
                        ->id('dialog-close')->role('button')->label('Close')))
                ->when($this->body !== [], fn ($e) => $e->child(col()->gap(12)->children($this->body)))
                ->when($this->footer !== [], fn ($e) => $e->child(row()->gap(8)->justifyEnd()->children($this->footer))));
    }
}

/** alert('Heads up')->description('...')->icon('info')->color('danger') */
class Alert extends Component
{
    private string $variant = 'default';

    public function __construct(private string $title = '', private string $caption = '', private string $iconName = 'circle-alert')
    {
        parent::__construct();
    }

    public function title(string $title): static { $this->title = $title; return $this; }
    public function description(string $text): static { $this->caption = $text; return $this; }
    public function icon(string $name, ?string $color = null, float $size = 16): static { $this->iconName = $name; return $this; }
    /** default or destructive (danger). */
    public function variant(string $variant): static { $this->variant = $variant; return $this; }
    public function color(string $color): static { return $this->variant($color === 'danger' ? 'destructive' : $color); }

    protected function build(): Element
    {
        $danger = $this->variant === 'destructive';
        $fg = $danger ? t('destructive') : t('card-foreground');
        return row()->gap(12)->px(16)->py(12)->rounded(10)->bg(t('card'))->border(1, t('border'))->itemsStart()->role('alert')
            ->child(col()->pt(2)->child(icon($this->iconName, 16, $fg)))
            ->child(col()->gap(4)->flex1()
                ->child(text($this->title)->textSize(14)->medium()->textColor($fg))
                ->when($this->caption !== '', fn ($e) => $e->child(paragraph($this->caption)->selectable()->textColor($danger ? t('destructive', 0.9) : t('muted-foreground')))));
    }
}

/** toast('Saved')->description('Your changes are safe.')->icon('circle-check') */
class Toast extends Component
{
    public function __construct(private string $title = '', private string $caption = '', private ?string $iconName = 'circle-check')
    {
        parent::__construct();
    }

    public function title(string $title): static { $this->title = $title; return $this; }
    public function description(string $text): static { $this->caption = $text; return $this; }
    public function icon(string $name, ?string $color = null, float $size = 16): static { $this->iconName = $name; return $this; }

    protected function build(): Element
    {
        return row()->absolute()->right(24)->bottom(24)->w(356)->gap(10)->p(16)->rounded(8)->animateIn(200)
            ->bg(t('popover'))->border(1, t('border'))->shadow('lg')->itemsStart()->role('alert')
            ->when($this->iconName !== null, fn ($e) => $e->child(col()->pt(2)->child(icon($this->iconName, 16, t('foreground')))))
            ->child(col()->gap(2)->flex1()
                ->child(text($this->title)->textSize(13)->semibold())
                ->when($this->caption !== '', fn ($e) => $e->child(text($this->caption)->textSize(13)->textColor(t('muted-foreground'))->truncate())));
    }
}
