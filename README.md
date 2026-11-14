# PHP UI

A tiny GPUI-style UI toolkit in plain PHP, drawn on the GPU with [PHP-GLFW](https://phpgl.net).

```php
col()->full()->p(24)->gap(12)->bg('#131619')
    ->child(text('Hello')->textSize(26)->bold())
    ->child(button('Add', fn () => $state->count++));
```

## How it works

Each frame, `render()` builds a fresh tree of `Element`s, `Layout` (a small flexbox) gives each one a box, `Painter` draws boxes and shadows as instanced quads with its own shader (`QuadRenderer`) and text and icons with NanoVG, and `App` routes mouse and keys by hit-testing those boxes. The window only redraws after input.

| File | Role |
|---|---|
| `src/Element.php` | Node and chainable style API |
| `src/Layout.php` | Flexbox: row/col, gap, padding, px/%, grow, align, justify |
| `src/Painter.php` | Backgrounds, rounded corners, borders, text, icons, clipping |
| `src/QuadRenderer.php` | Boxes, borders, gradients and shadows in one instanced draw (SDF shader) |
| `src/App.php` | Window, loop, hover, click, focus, typing, cursors |
| `src/functions.php` | Primitives: `div`, `row`, `col`, `text`, `input`, `canvas`, `icon` |
| `src/components.php` | shadcn/ui default components: button, badge, card, input, checkbox, switch, select, tabs, toggle group, table, dropdown menu, dialog, tooltip, toast, avatar, progress, slider, alert, kbd |
| `src/charts.php` | Area chart, bar chart, sparkline |
| `src/Theme.php` | shadcn's neutral theme, light and dark (`t('primary')`, `Theme::toggle()`) |

## Run

Needs PHP 8.2+ with the `glfw` extension.

```sh
php examples/todo.php
```

```sh
php examples/dashboard.php   # shadcn dashboard-01
```

Headless check (renders, clicks, types, saves PNGs at 2x):

```sh
Xvfb :99 & DISPLAY=:99 PHPUI_SCREENSHOTS=/tmp/shots php examples/dashboard.php
```

Renderer: `PHPUI_RENDERER=quads` (default), `nanovg-fast` or `nanovg`. Compare them with `php examples/bench-renderers.php`.

## One binary

`build/make-binary.sh` makes `build/dashboard` (12 MB): PHP, the glfw extension, the code and the assets in one file. It runs on any x86-64 Linux with glibc 2.17+ that has X11 and an OpenGL driver; no PHP needed.

It needs `micro.sfx` from [static-php-cli](https://github.com/crazywhalecc/static-php-cli), built against glibc (glfw cannot be fully static):

```sh
# craft.yml: php-version 8.4, extensions glfw,opcache,phar,mbstring,ctype,tokenizer,zlib,posix,pcntl, sapi cli + micro
mkdir x11libs && for l in X11 Xrandr Xinerama Xcursor Xi; do ln -s /usr/lib64/lib$l.so x11libs/; done
SPC_TARGET=native-native-gnu.2.17 SPC_CMD_VAR_PHP_MAKE_EXTRA_LDFLAGS="-L$PWD/x11libs" bin/spc craft
```

The `x11libs` links are needed on Fedora, where the libraries are in `/usr/lib64`. Fonts are copied out of the binary to `~/.cache/phpui` on first run, since NanoVG cannot read them inside it. The binary runs without OPcache: micro crashes with `opcache.enable_cli=1`, and the frame rate is the same without JIT.

## Install PHP-GLFW

- **Linux (Fedora):** `sudo dnf install php-devel gcc-c++ autoconf glfw-devel`, then in a php-glfw checkout `phpize && ./configure --enable-glfw && make && sudo make install`, and add `extension=glfw.so` to php.ini.
- **macOS:** same build steps, with `brew install glfw`.
- **Windows:** download the prebuilt DLL for your PHP version from the php-glfw releases.

Fonts: Geist and Geist Mono (OFL, `assets/fonts`). Icons: Lucide (ISC, `assets/icons`).

## Not yet

Text wrapping, scrolling, text selection and cursor movement, IME, accessibility, animation.
