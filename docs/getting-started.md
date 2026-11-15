# Getting started

MyPHP runs native desktop apps in plain PHP. The binary includes PHP and the UI
toolkit: you do not need to install PHP.

Requires **x86-64 Linux, glibc 2.17+, X11 or XWayland, and OpenGL**. Desktop
integration uses your session bus and optional tools; see [Desktop](desktop.md).

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

- `$state` is a plain PHP object holding the count.
- `$render` builds a fresh element tree from that state.
- `col()` fills the window; `card()` groups the title and controls.
- `text()` displays the current count.
- The button callback increments the same state object.
- `App` owns the window, input, layout, and painting.
- Input triggers a redraw; the idle loop sleeps.
- `->run()` starts the loop. No `require` is needed with `myphp run`.

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

## Optional editor autocomplete

```sh
composer install
```

This installs toolkit sources and GLFW stubs for your editor only. Running and
building still use the toolkit inside `myphp`, not `vendor/`. Composer requires a
separate PHP installation; it is never needed to run the app.

## Explore

```sh
myphp demo
myphp demo todo
myphp demo desktop
```

Each command opens one window; close it before the next. `demo` defaults to the
dashboard. See [Desktop APIs](desktop.md), [CLI reference](cli.md), and the
[manual desktop checklist](handover.md#still-needs-the-users-desktop).
