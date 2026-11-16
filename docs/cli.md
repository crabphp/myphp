# CLI

One binary, no PHP installation required. Commands below assume `myphp` is on PATH
and you are in an app folder unless stated otherwise.

## init

```sh
myphp init hello
```

Creates a counter app in a new or empty directory. Names start with a letter or
number and contain only letters, numbers, `.`, `_`, or `-`. It writes `app.php`,
`composer.json`, `.gitignore`, and `README.md`; it never runs Composer or Git.
The Composer package name is lowercase with normalized separators. Its toolkit
constraint is `dev-main` for a development CLI or `^X.Y` for version `X.Y.Z`.

## run

```sh
myphp run
myphp run app.php first second
myphp run --watch
```

Defaults to `app.php` in the current directory. The toolkit is already loaded.
`$argv` and `$_SERVER['argv']` contain `[file, ...args]`; `argc` is updated too.
A file inside a myphp checkout (such as `examples/`) runs with that checkout's
`src/` instead of the toolkit in the binary. The working directory does not change. Exceptions print their class, message,
and trace to stderr.

`--watch` (or `-w`, before the file) restarts the app whenever you save a PHP,
image or font file in its folder; `vendor/`, `build/` and dotfiles are ignored, and
so is data the app writes (JSON, text, databases). State starts fresh on each reload.
If the app crashes, the watcher waits for your next save. Closing the window or
Ctrl+C stops watching.

## build

```sh
myphp build
myphp build app.php -o build/custom
```

Defaults to `app.php`; the project is the entry file's directory, not necessarily
the working directory. Default output: `<project>/build/<project-name>`.
The result is one executable with the runtime, toolkit, assets, and project.
No download, PHP subprocess, or second runtime file is needed to build.

Packing skips `vendor/`, `build/`, `.git/`, every dot-prefixed path component,
symlinks, and the output file itself. A hidden entry file is rejected. All other
project files are included, so keep secrets outside the project or in dotfiles.
**Composer dependencies are not packed.**

### Assets

Inside the executable, `__DIR__` is a `phar://` path. PHP file functions can read
it directly. Native APIs and child processes need a real filesystem path:

```php
$data = file_get_contents(__DIR__ . '/data.txt');
$path = PhpUi\realFile(__DIR__ . '/data.txt');
```

Supply `data.txt` beside `app.php`. `realFile()` exports bundled data to a
content-addressed cache under `$XDG_CACHE_HOME/phpui` or `~/.cache/phpui`; regular
filesystem paths pass through unchanged. Fonts and toolkit icons do this for you.

## version

```sh
myphp version
```

Prints `myphp X.Y.Z (PHP 8.4.27)` (or `dev` for a local build).
Aliases: `-v`, `--version`.

## help

```sh
myphp help
```

Lists commands. No arguments, `-h`, and `--help` also print help.

## Environment

| Variable | Meaning |
|---|---|
| `PHPUI_SCREENSHOT=/tmp/app.png` | Render one frame at window size, save PNG, clean up, and exit; still needs a display |
| `PHPUI_SCREENSHOTS=/tmp/shots` | Examples only: run their existing scripted screenshot flows |
| `PHPUI_DESKTOP=auto` | Bus first, then tools; `portal` disables tool fallbacks, `fallback` skips D-Bus |
| `PHPUI_RENDERER=quads` | Default renderer; alternatives: `nanovg-fast`, `nanovg` |
| `MYPHP_INSTALL_DIR` | Installer destination; default `$HOME/.local/bin` |
| `MYPHP_VERSION` | Installer: release number without `v`, empty means latest; build script: embedded CLI version |
| `MYPHP_DOWNLOAD_URL` | Installer binary URL override, including `file://` for local testing |

```sh
PHPUI_SCREENSHOT=/tmp/hello.png myphp run
```

On a headless development machine, provide Xvfb and `DISPLAY` first. Parent
folders for screenshots must already exist.

## upgrade

```sh
myphp upgrade            # the latest release
myphp upgrade 0.1.2      # a specific version
myphp upgrade --nightly  # the newest build from main, not released yet
myphp upgrade --stable   # back to releases
```

A nightly build stays on nightly builds: plain `myphp upgrade` gets the newest one.
To install a nightly directly: `curl -fsSL …/install.sh | sh -s -- --nightly`
(PowerShell: `$env:MYPHP_VERSION='nightly'` before `irm …/install.ps1 | iex`).

Downloads the build for this system with `curl` or `wget`, checks its SHA-256 and
that it runs, then replaces the `myphp` you ran. Nothing changes if any step fails.
Nightly builds come from every push to `main` and may be less stable.

## Exit codes and limits

- `0`: success (help and version write to stdout).
- `1`: failure; CLI diagnostics start with `myphp:` on stderr.
- `2`: wrong usage, such as an unknown command or missing init name.

Apps can print their own output or explicitly exit with their own code.
Linux x86-64 (glibc 2.17+, X11/XWayland, OpenGL), experimental macOS (arm64,
x86-64) and experimental Windows (x86-64). No Composer dependency packing or
cross-compilation: `myphp build` targets the platform it runs on (on Windows it writes `build/<name>.exe`). Windows limits: built apps also open a
console window, and a binary started through an 8.3 short path (`C:\Users\LONGNA~1\...`)
does not work; use the full path.
Source CLI runs use `php84 bin/myphp`; source builds also need `MYPHP_SFX` and
`-d phar.readonly=0`. The standalone binary needs neither.
