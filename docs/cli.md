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

`--watch` (or `-w`, before the file) hot-reloads the app whenever you save a PHP,
image or font file in its folder; `vendor/`, `build/` and dotfiles are ignored, and
so is data the app writes (JSON, text, databases). The script runs again in the same
window: the new render function, handlers and timers take over, while `state()`
values (matched by order, property by property), focus, scroll positions, text being
edited and zoom stay. Changed images and icons load again. A syntax error or an
exception shows in a banner over the app, and the last working version keeps
running until the next save.

PHP cannot declare a named function or class twice, so a save that changes a file
declaring them (`function helper()`, `class Model`, an enum) restarts the app with
fresh state instead; closures, `fn`, methods and anonymous classes
(`state(new class { ... })`) reload in place. The script's top-level code runs again
on each reload, and the replaced app's `onShutdown()` handlers run first. If the app
crashes, the watcher waits for your next save. Closing the window or Ctrl+C stops
watching.

## build

```sh
myphp build
myphp build app.php -o build/custom
myphp build --full
myphp build --dmg        # macOS: also a disk image
```

Defaults to `app.php`; the project is the entry file's directory, not necessarily
the working directory. Default output: `<project>/build/<project-name>`.
The result is one executable with the runtime, toolkit, assets, and project.

**Runtime:** plain apps get the core runtime (about 12 MB). Apps that need HTTPS,
SQLite, XML and the like (found from `composer.json` `ext-*` requirements and the code)
are built with the full runtime (about 35 MB): myphp hands the command to
`myphp-full`, downloaded once to the user cache. `--full` and `--core` choose
explicitly, as does `"extra": {"myphp": {"runtime": "full"}}` in `composer.json`.
`run` makes the same choice. `MYPHP_FULL=/path/to/myphp-full` uses a local one.
See [Getting started](getting-started.md#two-runtimes).

Packing skips `build/`, `.git/`, every dot-prefixed path component, symlinks, and
the output file itself. A hidden entry file is rejected. All other project files are
included, so keep secrets outside the project or in dotfiles.

**Composer packages** come along: `vendor/` is packed (without packages' `tests/`,
`docs/` and `examples/` folders, and without the myphp package itself), and
`vendor/autoload.php` loads before your app, in `myphp run` too. Run
`composer install` before building. Composer needs a separate PHP; the built app does not.

### Name, icon and macOS apps

The app's icon is `icon.png` beside the entry file (a 1024×1024 PNG works everywhere),
or myphp's own. Its windows show it in the taskbar or Dock, in `myphp run` too.
`composer.json` names the app:

```json
{
    "extra": {
        "myphp": {
            "name": "Hello World",
            "id": "com.example.hello",
            "version": "1.2.0",
            "icon": "art/icon.png"
        }
    }
}
```

The name defaults to the folder's (`my-app` becomes `My App`), the id to
`dev.myphp.<folder>`, the version to `composer.json`'s `version` or `1.0.0`.

On macOS, `myphp build` also makes `build/<Name>.app`: the executable in a bundle with
an `Info.plist` (name, id, version, Retina) and the icon as `AppIcon.icns` at every
size, signed ad hoc so Apple Silicon Macs open it. `--dmg` puts the app in
`build/<Name>.dmg`, a compressed disk image with a link to Applications to drag it
to. Apps downloaded from the internet also need a Developer ID signature and Apple's
notarization to open without a warning; myphp does not do that yet.

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
Linux x86-64 (glibc 2.17+, X11/XWayland, Vulkan), experimental macOS (arm64,
x86-64; Metal) and experimental Windows (x86-64; Direct3D 12). No cross-compilation: `myphp build` targets the platform it runs on (on Windows it writes `build/<name>.exe`). Windows limits: built apps also open a
console window, and a binary started through an 8.3 short path (`C:\Users\LONGNA~1\...`)
does not work; use the full path.
Source CLI runs use `php84 bin/myphp`; source builds also need `MYPHP_SFX` and
`-d phar.readonly=0`. The standalone binary needs neither.
