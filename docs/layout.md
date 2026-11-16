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

`grid(3)->gap(16)->children($cards)` places children in three equal columns; each
row is as tall as its tallest cell, and cells without a height stretch to it.
`row()->flexWrap()->gap(8)` lets children continue on the next line when they do not
fit, like tags or toolbar buttons; `justify()` applies to each line.

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
layout. A text color set this way also colors the text inside the element.

`->transition($ms = 150)` fades background, text and border color, opacity and the
focus ring between states (hover, focus, or a style that changed in the next render)
with an ease-out curve. `->animateIn($ms = 150)` fades an element in each time it
appears. Buttons, inputs, tabs, toggles, checkboxes, switches, menus, dialogs, tooltips
and toasts use them already. Animations follow elements by `id()` (or their position
in the tree), and screenshots always show the finished state.

`when($condition, $then, $else = null)` conditionally changes the node;
callbacks must return it. Use stable, unique `id()` values for inputs and scrolling.
IDs also support scripted input through `$app->clickId('add')` and `$app->find('add')`.

## Events and the app

- `->onClick(fn ($x, $y, $e) => ...)` runs on release over the element it was pressed on.
- `->onDrag(fn ($x, $y, $e) => ...)` runs on press and on every move until release
  (sliders, splitters); `->onRightClick(fn ($x, $y, $e) => ...)` for context menus.
- `->onClickOutside(fn () => ...)` closes menus; `->stopClicks()` keeps clicks inside.
- `->onDrop(fn (array $paths) => ...)` receives files dropped on the element.
- `$app->onKey(fn ($key, $mods) => ...)` gets keys that no focused input used.
  Tab and Shift+Tab move between all controls; see [Accessibility](accessibility.md) for
  `onKeyDown()`, `onEscape()`, `label()`, `role()` and zoom.
- `$app->every(1, fn () => ...)` and `$app->after(0.5, fn () => ...)` run timers on the
  UI loop and redraw afterwards; `$app->cancel($id)` stops one.
- `$app->title('New title')` renames the window.
- `dataDir('my-app')` returns (and creates) the folder for your app's own files:
  `~/.local/share/my-app`, `~/Library/Application Support/my-app` or `%APPDATA%\my-app`.

Other primitives: `text($string)`, controlled `input($id, $value, $onInput)`,
`icon($name, $size = 16, $color = null)`, `spacer()`, `image($path)` and `canvas($draw)`.

`image(__DIR__ . '/logo.png')` shows a PNG, JPEG, BMP or GIF (first frame). Give it
`->w()` or `->h()` (pixels or a percent) and the other side keeps the picture's
aspect ratio; with neither it shows at its pixel size. `->fit('cover')` fills the box
and crops, `'contain'` (the default) shows the whole picture, `'fill'` stretches it.
`->rounded()` clips the corners. Use `__DIR__` paths so built apps find their files.
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

`->selectable()` on text, or on a container for all text inside, lets the user drag to
select, double-click a word, and copy with Ctrl/Cmd+C (Ctrl/Cmd+A selects all of it).
A selection stays within one text element. Message boxes and alerts are selectable.

`text()` stays on one line. `->wrap()` breaks it onto as many lines as its width
allows, and lets it shrink in a row; give its container a width (or `flex1()`) so it
has something to wrap to. `paragraph($text, $width = null, $size = 14)` is wrapping
text with an optional fixed width. `->truncate()` cuts one line with an ellipsis.

Inputs (`textInput()`, and `textarea()` for several lines) are full editors: click
or drag to place the caret and select, double-click selects a word, arrows move
(Ctrl/Alt for words, Shift to select), Home/End, Backspace/Delete, select all,
copy, cut and paste of the selection, and undo/redo (Ctrl+Z, Ctrl+Shift+Z or Ctrl+Y). Long text scrolls to keep the caret visible.
In a textarea, Enter adds a line, Up/Down move between lines, the wheel scrolls, and
Ctrl/Cmd+Enter calls `onSubmit`. On macOS the shortcuts use Cmd, and Alt for words.

Input methods work in inputs: Vietnamese (Telex, VNI), Chinese, Japanese, Korean and
others. Text being composed shows at the caret, underlined, and becomes part of the
value only when the input method commits it; the candidate list opens beside the caret.
On Linux this uses IBus or Fcitx.

## Limits

There is no rich text or animation of sizes and positions yet. With nothing selected, copy and cut take the whole value of an
input. See [Components](components.md)
and [Desktop](desktop.md) for the available controls and desktop behavior.
