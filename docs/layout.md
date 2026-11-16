# Layout

`div()`, `row()`, and `col()` create containers. `div()` defaults to a column;
`row()` places children horizontally and `col()` vertically. Add a node with
`->child($element)` or many with `->children($iterable)`. Strings become text;
`null` children are ignored.

A complete `app.php` for `myphp run`:

```php
<?php
use PhpUi\App;
use function PhpUi\{button, col, paragraph, row, state, t, text};

$state = state(new class {
    public int $count = 0;
});
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

## State

Keep app state in an object and change it in event handlers; the next frame shows
the new values. An anonymous class gives your editor autocompletion and types:

```php
$state = state(new class {
    public int $count = 0;
    /** @var list<string> */
    public array $todos = [];
});
button('Add', fn () => $state->todos[] = 'Ship it');
```

`state()` returns the object you pass. Clicks, keys, drops and desktop callbacks
(dialogs, notifications) redraw on their own. After changing a typed object from
anywhere else, call `App::current()->invalidate()`.

`state(['count' => 0])` with an array is a quick untyped store instead: `$s->count++`,
`$s->todos[] = $x`, `$s->set([...])`. It has no autocompletion, but changes to it
always redraw the window.

With a key, `state(new class { public bool $open = false; }, 'menu')` returns the same
state on every call, so a component function can keep its own state between renders.

## Text

`text()` stays on one line. `->wrap()` breaks it onto as many lines as its width
allows, and lets it shrink in a row; give its container a width (or `flex1()`) so it
has something to wrap to. `paragraph($text, $width = null, $size = 14)` is wrapping
text with an optional fixed width. `->truncate()` cuts one line with an ellipsis.

Inputs (`textInput()`, and `textarea()` for several lines) are full editors: click
or drag to place the caret and select, double-click selects a word, arrows move
(Ctrl/Alt for words, Shift to select), Home/End, Backspace/Delete, select all, and
copy, cut and paste of the selection. Long text scrolls to keep the caret visible.
In a textarea, Enter adds a line, Up/Down move between lines, the wheel scrolls, and
Ctrl/Cmd+Enter calls `onSubmit`. On macOS the shortcuts use Cmd, and Alt for words.

## Limits

There is no IME (input method) support, undo, rich text, accessibility layer, or
animation system yet. With nothing selected, copy and cut take the whole value of an
input. See [Components](components.md)
and [Desktop](desktop.md) for the available controls and desktop behavior.
