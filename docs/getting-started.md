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

While you work, `myphp run --watch` restarts the window each time you save.

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

The [`examples/`](../examples) folder of the repository has complete apps: a
dashboard, a component gallery, notes, a music player, a log viewer, a todo list
and desktop APIs.

```sh
git clone https://github.com/crabphp/myphp
cd myphp/examples
myphp run gallery.php
```

Run any of them the same way. Inside the repository, `myphp run` uses the
repository's own toolkit, so the examples always match the code beside them. See [Desktop APIs](desktop.md), [CLI reference](cli.md), and the
[manual desktop checklist](handover.md#still-needs-the-users-desktop).
