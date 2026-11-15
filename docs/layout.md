# Layout

`div()`, `row()`, and `col()` create containers. `div()` defaults to a column;
`row()` places children horizontally and `col()` vertically. Add a node with
`->child($element)` or many with `->children($iterable)`. Strings become text;
`null` children are ignored.

A complete `app.php` for `myphp run`:

```php
<?php
use PhpUi\App;
use function PhpUi\{button, col, paragraph, row, t, text};

$state = (object) ['count' => 0];
$render = fn () => col()->full()->p(24)->gap(16)->bg(t('background'))
    ->child(row()->itemsCenter()->gap(12)
        ->child(text('Count: ' . $state->count)->grow()->bold())
        ->child(button('Add', fn () => $state->count++)->id('add')
            ->hover(fn ($s) => $s->opacity(0.8))))
    ->child(paragraph('This text wraps inside a fixed-width box.', 300)
        ->when($state->count > 0, fn ($e) => $e->textColor('#2563eb')));
(new App('Layout', 480, 320, $render))->run();
```

## Size and spacing

| Chain | Meaning |
|---|---|
| `w(240)`, `h(40)`, `size(32)` | Size in pixels |
| `w('50%')`, `full()` | Percentage size; `full()` is 100% width and height |
| `gap(12)` | Space between children |
| `p(24)`, `px(16)`, `py(8)` | Padding on all, horizontal, or vertical sides |
| `pt(8)`, `pr(8)`, `pb(8)`, `pl(8)` | Individual padding |
| `grow(1)`, `shrink(1)`, `basis(0)` | Main-axis flex sizing |
| `flex1()` | Equal share: basis 0, grow 1, shrink 1 |
| `items($align)` | Cross-axis: start, center, end, stretch (default) |
| `self('center')` | Override this child's cross-axis alignment |
| `justify($mode)` | Main-axis: start (default), center, end, between |
| `itemsCenter()`, `itemsStart()` | Cross-axis shortcuts |
| `justifyCenter()`, `justifyBetween()`, `justifyEnd()` | Main-axis shortcuts |
| `center()` | Center on both axes |
| `clip()`, `scroll('list')` | Clip children; vertical scrolling uses a stable ID |
| `absolute()->top(8)->right(8)` | Remove from flow and draw over the parent |
| `inset(0)`, `centerX()` | Absolute insets or horizontal centering |

## Style and state

Colors accept hex strings, including alpha; `t('background')`, `t('foreground')`,
and other theme tokens adapt to light/dark mode. Use `bg()`, `gradient()`,
`rounded()`, `border()`, `shadow()`, and `opacity()` for boxes. Text styles inherit:
`textColor()`, `textSize()`, `medium()`, `semibold()`, `bold()`, `mono()`, `tracking()`.
`truncate()` adds an ellipsis; `textCenter()` and `textRight()` align text.

`hover(fn ($s) => $s->bg('#eeeeee'))` and `focus(...)` change **painting only**, not
layout. `when($condition, $then, $else = null)` conditionally changes the node;
callbacks must return it. Use stable, unique `id()` values for inputs and scrolling.
IDs also support scripted input through `$app->clickId('add')` and `$app->find('add')`.

Other primitives: `text($string)`, controlled `input($id, $value, $onInput)`,
`icon($name, $size = 16, $color = null)`, `spacer()`, and `canvas($draw)`.
Canvas callbacks receive `(VGContext $vg, Element $box, Painter $painter)`.

## Limits

`paragraph($text, $width, $size = 14)` measures fixed-width text using the app's
painter at render time; it is not general layout-driven wrapping. There is no text
selection or cursor movement, IME, accessibility layer, or animation system yet.
Inputs are single-line; copy/cut operate on the whole value. See [Components](components.md)
and [Desktop](desktop.md) for the available controls and desktop behavior.
