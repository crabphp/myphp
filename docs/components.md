# Components

All functions are in `PhpUi` and return chainable `Element` objects. Import with
`use function PhpUi\{button, card, text};`. State belongs to your app (see `state()` in
[Layout](layout.md#state)); callbacks update it and the next render builds a new tree. Theme colors come from `t()`.

Signatures below match `src/components.php` and `src/charts.php`.

## Controls

- `button(string $label, ?callable $onClick = null, string $variant = 'default', string $size = 'default', ?string $icon = null): Element` — Variants: default, secondary, outline, ghost, destructive, link; sizes: default, sm, lg, icon, icon-sm.
- `badge(string $label, string $variant = 'default', ?string $icon = null): Element` — Small label; variants: default, secondary, destructive, outline.
- `textInput(string $id, string $value, callable $onInput, string $placeholder = '', ?string $icon = null): Element` — Controlled one-line input; `onInput(string $value)` updates your state.
- `textarea(string $id, string $value, callable $onInput, string $placeholder = '', int $rows = 4): Element` — Multi-line input; text wraps and scrolls; Ctrl/Cmd+Enter calls `onSubmit`.
- `label(string $text): Element` — Medium-weight field label.
- `checkbox(bool $checked, callable $onToggle): Element` — Click to toggle your boolean state.
- `switchToggle(bool $on, callable $onToggle): Element` — Compact boolean switch.
- `select(string $id, string $value, array $options, callable $onChange, string $placeholder = 'Select…', string $size = 'default'): Element` — A complete dropdown: `[value => label]` options; opens and closes on its own and marks the chosen option.
- `selectTrigger(string $value, callable $onClick, string $size = 'default'): Element` — Value and chevron; pair with `dropdownMenu()`; size also accepts `sm`.
- `tabs(array $items, string $active, callable $onChange, string $idPrefix = 'tab'): Element` — `[value => label]` choices; callback receives the selected value.
- `toggleGroup(array $items, string $active, callable $onChange, string $idPrefix = 'toggle'): Element` — Connected outlined buttons with the same choice/callback format as tabs.
- `slider(float $value, ?callable $onChange = null): Element` — Value 0–100; click or drag to report a new value.

## Containers and table

- `card(Element ...$sections): Element` — Rounded panel containing card sections.
- `cardHeader(string|Element $title, string|Element|null $description = null, ?Element $action = null): Element` — Title, optional description, and right-hand action.
- `cardContent(Element ...$children): Element` — Padded vertical card content.
- `cardFooter(Element ...$children): Element` — Padded horizontal card footer.
- `table(array $columns, array $rows, ?callable $rowId = null, ?string $headBg = null, ?callable $rowStyle = null): Element` — Columns: `[key => [label, width|null, align]]`; rows: `[key => string|Element]`; null widths share space.

`rowId($index)` returns an ID; `rowStyle($element, $index)` styles each table row.

## Overlays

- `dropdownMenu(array $items, callable $onClose, float $width = 192): Element` — Items are `[label, onClick, icon?, shortcut?]` (add `'checked' => bool` for a check column), `'separator'`, or `['label' => text]`; position the panel with absolute insets.
- `dialog(string $title, string $description, Element $body, Element $footer, callable $onClose): Element` — Full-parent modal overlay with a close button and outside-click dismissal.
- `messageBox(string $title, string $message, array $buttons, callable $onButton, string $type = 'info', int $default = 0, array $destructive = []): Element` — Pure message overlay; caller owns its queue and callbacks; `Desktop::message()` manages those for you.
- `tooltip(string $text, string $side = 'bottom'): Element` — Child overlay visible while its parent is hovered; side may be `top`.
- `toast(string $title, string $description, ?string $icon = 'circle-check'): Element` — Bottom-right notice; your state controls its lifetime.

## Display

- `paragraph(string $text, ?float $width = null, float $size = 14): Element` — Wrapped text; without a width it fills the space its parent gives it.
- `separator(bool $vertical = false): Element` — One-pixel theme-colored divider.
- `avatar(string $initials, float $size = 32, string $radius = 'full'): Element` — Initials in a circle; a radius value other than `full` gives rounded corners.
- `progress(float $value): Element` — Progress bar clamped to 0–100.
- `skeleton(float|string $w, float $h): Element` — Static rounded placeholder.
- `alert(string $title, string $description, string $icon = 'circle-alert', string $variant = 'default'): Element` — Notice panel; variant also accepts `destructive`.
- `kbd(string $keys): Element` — Keyboard shortcut label.

## Charts

Give each chart a width and height with the layout API. Use nonempty series with
one value per label; sparklines need at least two values.

- `areaChart(array $labels, array $series, int $ticks = 6): Element` — Stacked areas; series: `[name => [color, values[]]]`, bottom series first.
- `barChart(array $labels, array $series): Element` — Grouped bars using the same series format.
- `sparkline(array $values, string $color): Element` — Small smooth line and shaded area.

`smoothPath(GL\VectorGraphics\VGContext $vg, array $pts, bool $move): void` is an
internal chart drawing helper, not a component.

See [Layout](layout.md) for primitives, sizing, and styling; [Desktop](desktop.md)
for managed messages, file choosers, and notifications.
