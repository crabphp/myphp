# MyPHP

Native desktop apps in plain PHP, drawn on the GPU.

One 12 MB binary holds PHP, the UI toolkit and its assets. No PHP install, no
webview, no Electron. Apps build into a single file you can send to anyone.

![Dashboard built with MyPHP](docs/images/dashboard.webp)

## Install

Linux x86-64 (X11 or XWayland, OpenGL), or macOS on Apple Silicon or Intel (experimental):

```sh
curl -fsSL https://raw.githubusercontent.com/crabphp/myphp/main/install.sh | sh
```

Later, `myphp upgrade` updates it (`myphp upgrade --nightly` for unreleased work).

Windows x86-64 (experimental), in PowerShell:

```powershell
irm https://raw.githubusercontent.com/crabphp/myphp/main/install.ps1 | iex
```

## Create an app

```sh
myphp init hello
cd hello
myphp run --watch    # reloads when you save
```

`app.php`:

```php
<?php
use PhpUi\App;
use function PhpUi\{button, col, state, text};

$state = state(new class {
    public int $count = 0;
});

$render = fn () => col()->full()->p(24)->gap(12)
    ->child(text('Count: ' . $state->count)->textSize(26)->bold())
    ->child(button('Add', fn () => $state->count++));

(new App('Hello', 480, 320, $render))->run();
```

The UI is a function of state: change `$state`, and the next frame shows it.

## Ship it

```sh
myphp build
```

You get `build/hello`, one executable with everything inside.

## Editor autocomplete

Optional. If you have Composer:

```sh
composer install
```

This only feeds your editor. Apps never need `vendor/`.

## What's inside

- **Layout:** flexbox rows and columns, grids, wrapping rows, gap, padding, grow,
  align, scrolling, wrapping text and images.
- **Text editing:** inputs and textareas with a caret, mouse selection, word jumps,
  copy/paste, undo/redo and Tab between fields.
- **Components:** shadcn/ui style buttons, inputs, select, tabs, table, dialog,
  dropdown menu, tooltip, toast, switch, slider, charts and more, with smooth
  hover and focus transitions.
- **State:** `state()` with a typed object your editor autocompletes; timers;
  a data folder per app.
- **Live reload:** `myphp run --watch` restarts the window when you save.
- **Desktop:** file dialogs, notifications, clipboard, file drop, open links,
  follow system dark mode.
- **Fast:** one GPU draw for all boxes and shadows; the app sleeps when idle.

Try the built-in demos:

```sh
myphp demo dashboard
myphp demo desktop
myphp demo gallery
myphp demo notes
myphp demo todo
```

![Desktop APIs demo](docs/images/desktop.webp)

## Docs

- [Getting started](docs/getting-started.md)
- [CLI](docs/cli.md)
- [Layout](docs/layout.md)
- [Components](docs/components.md)
- [Desktop](docs/desktop.md)
- [Contributing](docs/contributing.md)

## Status

Early. Linux is the main platform; macOS and Windows are experimental (built and
smoke-tested by CI, not yet tested on real hardware). Not yet: tray icons, menus,
multiple windows, global shortcuts.

## License

MIT. Fonts: Geist (SIL OFL). Icons: Lucide (ISC).
