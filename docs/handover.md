# Handover: native PHP UI and Linux desktop APIs

MyPHP draws native GPU desktop apps in plain PHP. Reference: MyGo
(github.com/egoist/mygo), cloned at `~/.cache/mygo`; its API docs are
`docs/native.md`, `docs/menus.md`, `docs/app.md`, and Linux code is `internal/linux`.

## What exists

- PHP 8.4.27 (Remi, `php84`) with PHP-GLFW 2.2 and JIT. System `php` is 8.5
  without glfw. Use `php84` or another PHP with glfw for examples/tests.
- Element tree, flexbox layout, NanoVG text/icons, instanced SDF quads for boxes,
  borders, gradients and shadows, shadcn-style components, charts and themes.
  Fixed-width `paragraph()` wraps measured text; general wrapping is not a layout feature.
- App input is refreshed between events (fast typing no longer drops characters),
  key modifiers are preserved, Escape reaches the app, and pollers/wake hints
  advance outside work. The loop tears down its window even after exceptions.
- Linux `Desktop`: portal file open/multiple/save/folder with zenity fallback;
  queued in-app messages/confirm/error; notifications with body/named click actions
  and notify-send fallback; clipboard with whole-input Ctrl+C/X/V; URL/file open
  via xdg-open/gio; portal system-theme read/watch with one-shot gsettings fallback.
- Drops are routed to the nearest element handler, then the window. `App::focus()`
  supports notification clicks. `Theme::$source` separates explicit and system modes.
- `src/DBus.php`: persistent pure-PHP session-bus client (streams + posix), bounded
  non-blocking polling, marshaling, method deadlines, signals and disconnect cleanup.
  One-shot gdbus cannot implement portal dialogs: Response is unicast to the same
  connection that made the request and that connection must stay alive.
- `examples/desktop.php`: buttons, editable clipboard/open target, result log and
  whole-window drop target. `examples/dashboard.php`, `examples/todo.php`, and
  four repaired benchmarks remain available.
- `build/make-binary.sh` builds `build/dashboard` (~12 MB) from micro.sfx at
  `~/.cache/spc/buildroot/bin/micro.sfx`. It packs the code/assets and all three
  examples. `PHPUI_EXAMPLE=desktop` selects the desktop example (dashboard default).
  Requires x86-64 Linux, glibc 2.17+, X11 and OpenGL; no installed PHP needed.

## Verification

The ordered A1–A7 and B1–B11 work is described in `docs/plan.md`. Each task is a
separate commit. Test commands:

```sh
tests/run.sh
tests/run.sh real-portal
DISPLAY=:99 PHPUI_SCREENSHOTS=/tmp/desktop php84 examples/desktop.php
build/make-binary.sh
DISPLAY=:99 PHPUI_EXAMPLE=desktop PHPUI_SCREENSHOTS=/tmp/desktop-binary build/dashboard
```

The runner picks `$PHP`, then php84, then php, and diagnoses missing glfw before
running anything. Each test has a private D-Bus session; test config/cache/data
are isolated. Fakes use dbus-python + GLib and argv-logging executable scripts.
Tests assert protocol bytes against an independent libdbus fixture, timing and
callback results, UI state, clipboard interoperability with xsel, and screenshot
pixels. Real smoke opens a GTK chooser, checks changed root pixels, closes its
request, checks identical root pixels, terminates real zenity, and reads portal
Settings. Screenshots are checked with pixel probes, not by eye.

## Traps and limits

- There is no interactive desktop session on this machine. Xvfb `:99` supplies
  X11. The user's bus at `/run/user/1000/bus` exists, but its portal cannot start
  without a graphical session and Notifications is not activatable. Use the
  private-bus test runner, not that session bus, for verification.
- The real portal test needs GTK preference in `xdg-desktop-portal/portals.conf`;
  otherwise GNOME's backend may be chosen and file dialogs fail. The runner sets it.
- PHP-GLFW's drop callback receives **(int count, array paths)**, not just paths;
  verified in `phpglfw_callback_dropcallback_handler` in the cached extension C.
  Actual file-manager XDND remains a manual test.
- Calling `glfwTerminate()` before NanoVG destructors run causes a native shutdown
  crash. App destroys its window but leaves global GLFW termination to process exit.
- `micro.sfx` crashes with embedded `opcache.enable_cli=1`; the binary runs without
  OPcache/JIT. Its desktop screenshot JSON verifies proc_open, stream_socket_client
  and posix_geteuid are present. Fonts/icons passed to C or child programs are
  exported from phar to the asset cache with `PhpUi\realFile()`.
- Static-php-cli's glfw recipe expects Debian X11 paths. On Fedora build with
  `SPC_CMD_VAR_PHP_MAKE_EXTRA_LDFLAGS=-L$HOME/.cache/spc/x11libs`.
- Portal dialogs have no parent X11 handle (PHP-GLFW does not expose one), so they
  may appear behind the app. Only one file chooser can be open at once.
- Clipboard text is owned by the process on X11; a clipboard manager is needed to
  retain it after quit. No text selection yet: Ctrl+C/X copies the entire input.
- Gsettings fallback reads once, with no live theme updates. Delivered desktop
  notifications intentionally survive app shutdown; waiting helper processes do not.
- `PHPUI_DESKTOP=auto|portal|fallback` chooses bus-first, bus-only or tools-only.
  Fallback tools must be installed on the user's desktop. `Desktop::lastError()`
  records unavailable services and failed commands.

## Still needs the user's desktop

Headless checks cannot confirm interactive usability. Run `php84 examples/desktop.php`
(or `PHPUI_EXAMPLE=desktop build/dashboard`) and check:

1. Open one file, open multiple Images-filtered files, cancel, save with a suggested
   name, choose a folder; cancel each chooser too.
2. Repeat with `PHPUI_DESKTOP=fallback` to exercise zenity.
3. Notification appears; clicking the body focuses the window; named action logs
   its key; Close notification removes it.
4. Copy in this app and paste in another, then back. Try Ctrl+C/X/V in the input.
5. Drop two files from a file manager (including a path with a space).
6. Open a URL and a PDF using the target field.
7. System theme button, then switch GNOME's style: the app follows. Toggle theme
   in the app: it stops following until System theme is selected again.

These manual checks are pending, not claimed as completed. Not in this batch:
single instance, deep links, start at login, tray/menus, global shortcuts, multiple
windows, kdialog, macOS/Windows desktop backends, webview/cookies/PDF/printing.
