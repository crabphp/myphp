# Contributing

Notes for working on MyPHP itself. Internal history: `docs/handover.md` and the `docs/plan*.md` files.

## Setup

Use PHP 8.2+ with the `phpsdl` extension (`ext/phpsdl`, built as in its README; the
older `glfw` extension also works). Examples below call it `php84`. Tests need
Xvfb on `:99` and `dbus-run-session`; they start private D-Bus sessions themselves.

```sh
php84 bin/myphp version
php84 examples/todo.php
tests/run.sh
build/make-myphp.sh
build/myphp run examples/desktop.php
build/release.sh --dry-run 0.1.0
```

`build/make-myphp.sh [core micro.sfx] [full micro.sfx]` uses `$PHP`, then `php84`,
then `php`, and writes `build/myphp` and `build/myphp-full`; default runtimes:
`~/.cache/spc/runtimes/core/micro.sfx` and `~/.cache/spc/runtimes/full/micro.sfx`. Build artifacts are ignored.
`build/make-binary.sh` still makes the legacy `build/dashboard` test fixture.
`PHPUI_SCREENSHOT=/tmp/app.png` saves one frame at window size and exits.
`PHPUI_RENDERER=quads` is the default; alternatives: `nanovg-fast`, `nanovg`.

Release preparation needs `gh` on PATH, a clean pushed `main`, and a new version.
Only a maintainer should run `build/release.sh X.Y.Z` without `--dry-run`: it tags,
pushes, and creates the GitHub release.

For editor testing before Packagist, run `composer config repositories.myphp path
/path/to/myphp` in a generated app, then `composer install`. The PHP used by Composer
does not need phpsdl. Do not commit `vendor/` or the root `composer.lock`.

## Build the runtime

Use [static-php-cli](https://github.com/crazywhalecc/static-php-cli) v3 (the commit CI
uses), targeting glibc: SDL3 loads X11, Wayland and D-Bus at run time. First add
myphp's recipes for SDL3 and phpsdl, which builds from `ext/phpsdl` in this checkout:
`php build/spc/install.php SPC_DIR`. SDL needs the X11, Wayland, D-Bus and IBus
development headers to build with input-method support (the build stops without it);
`MYPHP_SDL_PKG_CONFIG_PATH` points it at headers unpacked elsewhere, e.g. from
`dnf download` + `rpm2cpio` without root. In the checkout, build twice: `build/craft.yml`
(the core runtime: PHP 8.4 with the toolkit's extensions) and `build/craft-full.yml`
(plus `openssl`, `curl`, `pdo_sqlite`, XML and friends; keep `cli/runtime.php` in sync),
copying `buildroot/bin/micro.sfx` and `php` to `~/.cache/spc/runtimes/core/` and
`.../full/` after each:

```sh
SPC_TOOLCHAIN='StaticPHP\Toolchain\GccNativeToolchain' SPC_TARGET=native-native-gnu bin/spc craft craft.yml
```

The CLI is micro + an INI block enabling phar creation + the CLI phar. App builds
reuse the clean runtime prefix, with no INI block or OPcache/JIT (micro crashes with
`opcache.enable_cli=1`). Fonts and native assets are exported to the user cache by
`PhpUi\realFile()`.

Fonts: Geist and Geist Mono (OFL, `assets/fonts`). Icons: Lucide (ISC, `assets/icons`).
The macOS build bundles AccessKit (MIT/Apache-2.0) in `assets/native`, downloaded by CI.
`assets/certs/cacert.pem` is Mozilla's CA list as published by curl.se (MPL-2.0);
refresh it with `curl -o assets/certs/cacert.pem https://curl.se/ca/cacert.pem`.

## macOS binaries

`.github/workflows/macos.yml` builds `myphp-macos-arm64` and `myphp-macos-x86_64` on
GitHub runners with the same recipe, smoke-tests `init`, `build` and `install.sh`,
and attaches the binaries to a release when it is published (`build/release.sh` only
uploads the Linux binary). Run it by hand from the Actions tab to get test binaries
as artifacts. Nobody has run these on a real Mac yet: `src/Desktop.php` uses
`osascript`, `defaults` and `open` there instead of D-Bus.

## Windows binary

`.github/workflows/windows.yml` builds `myphp-windows-x86_64.exe` with
`build/craft-windows.yml` (no `posix`/`pcntl`), smoke-tests `init`, `build` and
`install.ps1`, renders screenshots of a built app and two demos with Mesa's software
OpenGL (`GALLIUM_DRIVER=llvmpipe`, the runners have no GPU), and attaches the binary
to a published release. `src/Desktop.php` uses `powershell`, `reg` and `rundll32`
there; those paths have not run on a real Windows desktop yet.
