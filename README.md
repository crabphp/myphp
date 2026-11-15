# MyPHP

Native GPU desktop apps in plain PHP, drawn with [PHP-GLFW](https://phpgl.net).
One binary includes PHP, the toolkit, and assets. Linux x86-64, glibc 2.17+,
X11/XWayland, OpenGL; no PHP installation needed.

```sh
curl -fsSL https://raw.githubusercontent.com/crabphp/myphp/main/install.sh | sh
myphp init hello && cd hello && myphp run
myphp build
```

Send `build/hello` to another Linux desktop. **The public download is pending the
first release**; until then, build `build/myphp` locally (below) and put it on PATH.
The installer prints a PATH hint if needed.

An `app.php`:

```php
<?php
use PhpUi\App;
use function PhpUi\{button, col, text};

$state = (object) ['count' => 0];
$render = fn () => col()->full()->p(24)->gap(12)->bg('#131619')->textColor('#ffffff')
    ->child(text('Count: ' . $state->count)->textSize(26)->bold())
    ->child(button('Add', fn () => $state->count++));
(new App('Hello', 480, 320, $render))->run();
```

## Docs

- [Getting started](docs/getting-started.md): install, create, run, build
- [CLI](docs/cli.md): commands, packing assets, environment, exit codes
- [Components](docs/components.md): controls, overlays, tables, charts
- [Layout](docs/layout.md): primitives, flex sizing, styles, limits
- [Desktop](docs/desktop.md): file dialogs, messages, notifications, clipboard, drop, theme
- [Handover](docs/handover.md): verification and pending desktop checks

## How it works

Each frame, `render()` builds a fresh tree of `Element`s. `Layout` gives each one a
box. `Painter` draws boxes and shadows as instanced SDF quads, and text/icons with
NanoVG. `App` routes input by hit-testing those boxes; the idle loop sleeps.

| File | Role |
|---|---|
| `bin/myphp`, `cli/` | Source CLI, commands, app template |
| `src/Element.php` | Node and chainable style API |
| `src/Layout.php` | Flexbox: row/col, gap, padding, px/%, grow, align, justify |
| `src/Painter.php` | Backgrounds, rounded corners, borders, text, icons, clipping |
| `src/QuadRenderer.php` | Boxes, borders, gradients, shadows in one instanced draw |
| `src/App.php` | Window, loop, hover, click, focus, typing, cursors |
| `src/functions.php` | Primitives and bundled-asset export |
| `src/components.php` | shadcn-style controls, cards, tables, overlays |
| `src/charts.php` | Area chart, bar chart, sparkline |
| `src/Theme.php` | Neutral theme, explicit or system light/dark |
| `src/Desktop.php` | Linux file choosers, messages, notifications, clipboard, open, theme |
| `src/DBus.php` | Persistent non-blocking D-Bus, pure PHP streams |

## Develop

Use PHP 8.2+ with `glfw`; on this machine use `php84` (Remi PHP 8.4.27).
The system PHP has no GLFW. Tests use Xvfb `:99` and private D-Bus sessions.

```sh
php84 bin/myphp version
php84 examples/todo.php
tests/run.sh
build/make-myphp.sh
build/myphp demo desktop
build/release.sh --dry-run 0.1.0
```

`build/make-myphp.sh [micro.sfx]` uses `$PHP`, then `php84`, then `php`; default
runtime: `~/.cache/spc/buildroot/bin/micro.sfx`. Build artifacts are ignored.
`build/make-binary.sh` still makes the legacy `build/dashboard` test fixture.
`PHPUI_SCREENSHOT=/tmp/app.png` saves one frame at window size and exits.
`PHPUI_RENDERER=quads` is the default; alternatives: `nanovg-fast`, `nanovg`.

Release preparation needs `gh` on PATH, a clean pushed `main`, and a new version.
Only a maintainer should run `build/release.sh X.Y.Z` without `--dry-run`: it tags,
pushes, and creates the GitHub release. Public downloads, a project license, and
Packagist publication are still [maintainer steps](docs/plan-cli.md#user-steps-not-for-the-implementer).

For editor testing before Packagist, run `composer config repositories.myphp path
/path/to/myphp` in a generated app, then `composer install`. The PHP used by Composer
does not need GLFW. Do not commit `vendor/` or the root `composer.lock`.

### Build the runtime

Use [static-php-cli](https://github.com/crazywhalecc/static-php-cli), targeting glibc
(glfw cannot be fully static). In its checkout, set `craft.yml`: PHP 8.4, extensions
`glfw,opcache,phar,mbstring,ctype,tokenizer,zlib,posix,pcntl`, SAPIs `cli` and `micro`.
On Fedora, supply the X11 library paths:

```sh
mkdir -p x11libs
for l in X11 Xrandr Xinerama Xcursor Xi; do ln -sf /usr/lib64/lib$l.so x11libs/; done
SPC_TARGET=native-native-gnu.2.17 SPC_CMD_VAR_PHP_MAKE_EXTRA_LDFLAGS="-L$PWD/x11libs" bin/spc craft
```

The CLI is micro + an INI block enabling phar creation + the CLI phar. App builds
reuse the clean runtime prefix, with no INI block or OPcache/JIT (micro crashes with
`opcache.enable_cli=1`). Fonts and native assets are exported to the user cache by
`PhpUi\realFile()`.

Fonts: Geist and Geist Mono (OFL, `assets/fonts`). Icons: Lucide (ISC, `assets/icons`).
