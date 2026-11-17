# Getting started

MyPHP runs native desktop apps in plain PHP. The binary includes PHP and the UI
toolkit: you do not need to install PHP.

Requires **x86-64 Linux, glibc 2.17+, X11 or XWayland, and OpenGL**. Desktop
integration uses your session bus and optional tools; see [Desktop](desktop.md).
macOS (Apple Silicon and Intel) and Windows x86-64 are experimental. A build runs on
the platform that made it: build Linux apps on Linux, macOS apps on macOS and
Windows apps on Windows. On Windows install with
`irm https://raw.githubusercontent.com/crabphp/myphp/main/install.ps1 | iex`; apps
need OpenGL 3.3 from the graphics driver.

## Install

```sh
curl -fsSL https://raw.githubusercontent.com/crabphp/myphp/main/install.sh | sh
export PATH="$HOME/.local/bin:$PATH"
```

The installer downloads the latest release into `~/.local/bin`, verifies its
checksum when available, and prints the version. See [CLI](cli.md) for overrides.

## Create and run

```sh
myphp init hello
cd hello
myphp run
```

Close the window to return to the shell. The new folder contains `app.php`,
`composer.json`, `.gitignore`, and a short `README.md`. There is no `vendor/`
requirement and no background service.

`app.php` is a small counter:

- `$state` holds the count in a typed object, so your editor autocompletes it.
- `$render` builds a fresh element tree from that state.
- `col()` fills the window; `card()` groups the title and controls.
- `text()` displays the current count.
- The button callback increments the same state object.
- `App` owns the window, input, layout, and painting.
- Input triggers a redraw; the idle loop sleeps.
- `->run()` starts the loop. No `require` is needed with `myphp run`.

While you work, `myphp run --watch` hot-reloads the app each time you save: the
window stays and so does your state, so you see each change right where you are.

See [Components](components.md) and [Layout](layout.md) to change the UI.

## Build and share

```sh
myphp build
./build/hello
```

Send **just `build/hello`** to another x86-64 Linux machine. It contains PHP, the
UI toolkit, fonts, icons, and your app files. It still needs glibc 2.17+, a display
server, and OpenGL; it is not a headless server app. Building is offline.

`vendor/`, `build/`, dotfiles, and symlinks are excluded. Do not store secrets in
ordinary project files: they would be packed. See [CLI](cli.md#build) for assets
and custom output paths.

## Composer packages and editor autocomplete

```sh
composer install
composer require guzzlehttp/guzzle   # any package
```

`vendor/autoload.php` loads by itself in `myphp run`, and `myphp build` packs your
packages into the app. The generated `composer.json` also installs the toolkit's
sources and the runtime's stubs, so your editor autocompletes everything. Composer needs a
separate PHP installation; built apps never do.

### Two runtimes

myphp itself is the small **core** runtime: PHP with the UI toolkit (`mbstring`,
`ctype`, `filter`, `zlib`, `tokenizer`...). A plain app builds to about 12 MB.

Apps that need HTTPS (`openssl`, `curl`), SQLite (`pdo_sqlite`, `sqlite3`), XML,
`fileinfo`, `iconv`, `bcmath` or `sockets` use the **full** runtime (about 35 MB).
You don't choose: `myphp run` and `myphp build` look at your project and switch on
their own, downloading the full runtime once:

- `composer.json` (and `composer.lock`) requirements such as `"ext-curl": "*"`;
- your code: calls like `curl_init()`, classes like `PDO` with `sqlite:` or `DOMDocument`,
  `https://` URLs; and a quick search of `vendor/`.

To decide yourself, add `"extra": {"myphp": {"runtime": "full"}}` (or `"core"`) to
`composer.json`, or pass `--full` / `--core` to `run` and `build`. If detection misses
something, the error tells you which of these to use.

Certificates come from the system, or from a bundled Mozilla CA list where there is
none (Windows).

## Explore

The [`examples/`](../examples) folder of the repository has complete apps: a
showcase (a dashboard, and every component with its code), notes, a mail client, a music player, a pocket player with a click wheel, a log viewer, a todo list
and desktop APIs.

```sh
git clone https://github.com/crabphp/myphp
cd myphp/examples
myphp run showcase.php
```

Run any of them the same way. Inside the repository, `myphp run` uses the
repository's own toolkit, so the examples always match the code beside them. See [Desktop APIs](desktop.md), [CLI reference](cli.md), and the
[manual desktop checklist](handover.md#still-needs-the-users-desktop).
