# PHP UI

A tiny GPUI-style UI toolkit in plain PHP, drawn on the GPU with [PHP-GLFW](https://phpgl.net).

```php
col()->full()->p(24)->gap(12)->bg('#131619')
    ->child(text('Hello')->textSize(26)->bold())
    ->child(button('Add', fn () => $state->count++));
```

## How it works

Each frame, `render()` builds a fresh tree of `Element`s, `Layout` (a small flexbox) gives each one a box, `Painter` draws boxes and shadows as instanced quads with its own shader (`QuadRenderer`) and text and icons with NanoVG, and `App` routes mouse and keys by hit-testing those boxes. The window redraws after input or a desktop callback; otherwise its loop sleeps.

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
| `src/Theme.php` | shadcn's neutral theme, explicit or system light/dark |
| `src/Desktop.php` | Linux file choosers, messages, notifications, clipboard, open and system theme |
| `src/DBus.php` | Persistent non-blocking D-Bus connection, pure PHP streams |

## Run

Needs PHP 8.2+ with the `glfw` extension. On this machine use `php84` (Remi PHP 8.4); the system `php` has no glfw.

```sh
php84 examples/todo.php
```

```sh
php84 examples/dashboard.php   # shadcn dashboard-01
```

Headless check (renders, clicks, types, saves PNGs at 2x):

```sh
Xvfb :99 & DISPLAY=:99 PHPUI_SCREENSHOTS=/tmp/shots php84 examples/dashboard.php
```

Renderer: `PHPUI_RENDERER=quads` (default), `nanovg-fast` or `nanovg`. Compare them with `php84 examples/bench-renderers.php`.

Tests: `tests/run.sh` (or `PHP=/path/to/php-with-glfw tests/run.sh`). The runner uses Xvfb and a private D-Bus session, never the user's session bus.

## Desktop (Linux)

```sh
php84 examples/desktop.php
PHPUI_DESKTOP=fallback php84 examples/desktop.php  # force the command-line tools
```

Create an `App`, then call `PhpUi\Desktop` from your handlers. `App` polls desktop
work automatically. Callbacks run on the main thread and trigger a redraw; file
dialogs and child processes never wait in the frame loop. The initial local D-Bus
connection/authentication has a bounded startup wait. There is one file chooser at
a time, with no timeout while the user is choosing. Cancellation or failure returns
`null`; `Desktop::lastError(): ?string` gives the failure reason.

API (all methods below are static on `Desktop`):

```php
openFile(callable $done, string $title = 'Open', array $filters = [],
    bool $multiple = false, ?string $folder = null): void; // done(?array $paths)
saveFile(callable $done, string $title = 'Save', ?string $name = null,
    ?string $folder = null, array $filters = []): void;    // done(?string $path)
pickFolder(callable $done, string $title = 'Select Folder', ?string $folder = null): void;
// Filters: ['Images' => ['png', 'jpg'], 'All files' => ['*']]

message(string $title, string $message, array $buttons = ['OK'], ?callable $done = null,
    string $type = 'info', ?int $default = null, ?int $cancel = null,
    array $destructive = []): void; // done(int $button); destructive: button indexes
confirm(string $title, string $message, callable $done, string $ok = 'OK',
    string $cancel = 'Cancel', bool $destructive = false): void; // done(bool $ok)
error(string $title, string $message, ?callable $done = null): void;

notify(string $title, string $body = '', ?callable $onClick = null,
    ?string $icon = null, array $actions = []): int; // local notification ID
// onClick(string $action): 'default' for body clicks; actions: ['open' => 'Open']
closeNotification(int $id): void;
clipboard(): string;
setClipboard(string $text): void;
open(string $target, ?callable $done = null): bool; // done(bool $ok); false if refused
systemDark(): ?bool;                             // null = unknown/no preference
followSystemTheme(?callable $onChange = null): void; // onChange(bool $dark)
```

Set `Desktop::$appName` to the app's name. Notifications accept an icon name or an
absolute path (including bundled `phar://` assets); use `$app->focus()` in a click
handler. Delivered notifications outlive app shutdown. Message boxes are queued,
use wrapped text, and support `info`, `warning`, `error`, `question`; Enter selects
the default button and Escape the cancel button (first Cancel/No, otherwise first).
They do not dismiss on outside clicks. The pure `PhpUi\messageBox()` component can
also be used directly.

```php
$app->onDrop(function (array $paths, float $x, float $y) { /* window target */ });
$element->onDrop(function (array $paths) { /* nearest target under pointer */ });
$app->drop(['/tmp/example.txt'], 20, 20); // scripted input, like clickId()
Theme::use('system'); // source only; Desktop::followSystemTheme() also reads/watches
Theme::set('dark');   // mode only, preserves source
Theme::toggle();      // changes mode and switches source to explicit light/dark
```

| API | First choice | Fallback |
|---|---|---|
| File chooser | Desktop portal FileChooser | `zenity` |
| Notifications | `org.freedesktop.Notifications` | `notify-send` (0.8+ for actions) |
| System theme | Portal Settings read + signals | `gsettings get` (one read, no live updates) |
| Open URL/file | `xdg-open` | `gio open` |
| Clipboard/drop | GLFW | — |
| Message/confirm | In-app components | — |

`PHPUI_DESKTOP=auto` is the default (bus first, then tools). `portal` disables the
file/notification/theme tool fallbacks; `fallback` skips D-Bus. D-Bus needs `posix`
and PHP streams, not a PHP D-Bus extension or `gdbus`. The connection stays alive to
receive portal responses and notification actions.

Known limits:

- File dialogs are not tied to the app window: PHP-GLFW does not expose its X11
  window ID. A chooser may appear behind the app. Zenity multiple results cannot
  represent filenames containing newlines.
- Inputs support Ctrl+V (line breaks become spaces), Ctrl+C and Ctrl+X. With no
  selection yet, copy and cut take the **whole input**. On X11 the clipboard is
  owned by the app and disappears on quit unless a clipboard manager saves it.
- System-theme fallback has no live updates. No-preference preserves the current
  theme. Toggling the theme in the app stops following the system.
- `open()` accepts `http:`, `https:`, `mailto:`, `file:` and existing local paths;
  it rejects other schemes and targets starting with `-`.
- This batch targets Linux and one window. macOS/Windows desktop backends, tray,
  menus, global shortcuts, single instance and deep links are not implemented.

Tests: `tests/run.sh` uses deterministic private-bus fakes;
`tests/run.sh real-portal` also opens/closes a real GTK portal chooser and zenity on
Xvfb and reads real portal settings. Test config/cache/data are isolated; it never
writes the user's system-style preference. Pass test filenames to run a subset.

## One binary

`build/make-binary.sh` makes `build/dashboard` (12 MB): PHP, the glfw extension, the code and the assets in one file. It runs on any x86-64 Linux with glibc 2.17+ that has X11 and an OpenGL driver; no PHP needed.

It needs `micro.sfx` from [static-php-cli](https://github.com/crazywhalecc/static-php-cli), built against glibc (glfw cannot be fully static):

```sh
# craft.yml: php-version 8.4, extensions glfw,opcache,phar,mbstring,ctype,tokenizer,zlib,posix,pcntl, sapi cli + micro
mkdir x11libs && for l in X11 Xrandr Xinerama Xcursor Xi; do ln -s /usr/lib64/lib$l.so x11libs/; done
SPC_TARGET=native-native-gnu.2.17 SPC_CMD_VAR_PHP_MAKE_EXTRA_LDFLAGS="-L$PWD/x11libs" bin/spc craft
```

The binary includes dashboard, desktop and todo examples. Select one at runtime:

```sh
PHPUI_EXAMPLE=desktop ./build/dashboard
DISPLAY=:99 PHPUI_EXAMPLE=desktop PHPUI_SCREENSHOTS=/tmp/desktop-shots ./build/dashboard
```

`build/make-binary.sh` picks `$PHP`, then `php84`, then `php` to build the archive.
The embedded PHP must provide `proc_open`, `stream_socket_client` and `posix_geteuid`
for desktop services; the screenshot run reports those capabilities in its JSON.

The `x11libs` links are needed on Fedora, where the libraries are in `/usr/lib64`. Fonts and notification icons are copied out of the binary to `~/.cache/phpui` (or `$XDG_CACHE_HOME/phpui`) on first use, since native APIs cannot read them inside it. The binary runs without OPcache: micro crashes with `opcache.enable_cli=1`, and the frame rate is the same without JIT.

## Install PHP-GLFW

- **Linux (Fedora):** `sudo dnf install php-devel gcc-c++ autoconf glfw-devel`, then in a php-glfw checkout `phpize && ./configure --enable-glfw && make && sudo make install`, and add `extension=glfw.so` to php.ini.
- **macOS:** same build steps, with `brew install glfw`.
- **Windows:** download the prebuilt DLL for your PHP version from the php-glfw releases.

Fonts: Geist and Geist Mono (OFL, `assets/fonts`). Icons: Lucide (ISC, `assets/icons`).

## Not yet

General layout-driven text wrapping (fixed-width `paragraph()` is available), text
selection and cursor movement, IME, accessibility, animation.

## Try the demo

Prebuilt for x86-64 Linux (X11 or XWayland, OpenGL), no PHP needed:

```sh
git clone git@github.com:crabphp/myphp.git && cd myphp
demo/desktop                                  # desktop APIs: dialogs, notifications, clipboard, drop, theme
PHPUI_DESKTOP=fallback demo/desktop           # same, through zenity / notify-send
PHPUI_EXAMPLE=dashboard demo/myphp-demo       # the shadcn dashboard
```

What to check is listed in `docs/handover.md`, "Still needs the user's desktop".
`demo/myphp-demo` is a copy of `build/dashboard`; refresh it after `build/make-binary.sh`.
