# Handover: proof of concept so far

MyPHP: native GPU desktop apps in plain PHP, like MyGo (github.com/egoist/mygo,
a clone is at ~/.cache/mygo; its desktop API docs are docs/native.md, docs/menus.md,
docs/app.md, and its Linux code is internal/linux).

## What exists

- PHP 8.4 (Remi, `php` on PATH runs it) with the PHP-GLFW extension (glfw.so) and JIT on.
- `src/`: Element tree, flexbox `Layout`, `Painter` (NanoVG text/icons + `QuadRenderer`,
  our own instanced SDF shader for boxes, borders, gradients, shadows), `App` (window loop,
  input, hit-testing, `screenshot()`, scripted `click()`/`type()`/`key()` for tests),
  shadcn-style components, charts, light/dark theme.
- `examples/dashboard.php` (shadcn dashboard-01), `examples/todo.php`, benchmarks
  (`bench-renderers.php` compares `PHPUI_RENDERER=quads|nanovg-fast|nanovg`).
- Speed: ~70 fps on the dashboard on llvmpipe (CPU rendering) at 1440x900, up from 6.
  The big win was caching icons as textures: `drawSVG` costs two full-screen stencil
  passes per call.
- One-file binary: `build/make-binary.sh` -> `build/dashboard` (12 MB, PHP + glfw + code +
  assets). Needs micro.sfx from static-php-cli at ~/.cache/spc (see README "One binary").
  Runs on glibc 2.17+ Linux with X11 + OpenGL; tested in a clean ubuntu:22.04 container.

## Traps learned

- No desktop session on this machine: render headless with Xvfb, e.g.
  `DISPLAY=:99 PHPUI_SCREENSHOTS=/tmp/shots php examples/dashboard.php`
  (start `Xvfb :99 -screen 0 1600x1000x24 &` if :99 is not running). Check images
  with pixel probes / `magick compare`, not by eye.
- micro.sfx crashes or prints nothing with `opcache.enable_cli=1` in its embedded ini:
  the binary runs without OPcache (same fps).
- NanoVG `createFont` cannot read `phar://`: `Painter::realFile()` copies fonts to
  `~/.cache/phpui` first. Anything else handed to C by path needs the same.
- static-php-cli's glfw recipe looks for X11 in Debian's lib path; on Fedora pass
  `SPC_CMD_VAR_PHP_MAKE_EXTRA_LDFLAGS=-L$HOME/.cache/spc/x11libs`.
- PHP-GLFW's FloatBuffer only takes floats (ints throw); no FFI extension in the system PHP.

## Next task: desktop APIs, batch 1 (Linux first)

One file, `src/Desktop.php`, plus buttons in an example to try each:

- File dialogs: open (filters, multiple), save, pick folder. Use the desktop portal
  (`org.freedesktop.portal.FileChooser` over D-Bus via `gdbus`), fall back to `zenity`.
- Message and confirm boxes: our own components (a dialog component exists).
- Notifications with click handling: `org.freedesktop.Notifications` via `gdbus`
  (`notify-send` as fallback).
- Clipboard (GLFW has it), files dropped on the window (`glfwSetDropCallback`),
  open URL/file in the default app (`xdg-open`/`gio open`), follow the system dark mode
  (portal Settings `org.freedesktop.appearance color-scheme`).
- Never block the frame loop: dialogs and portal replies run as child processes
  (`proc_open`, non-blocking pipes) polled each frame, with a callback when done.
- The session bus exists here (`/run/user/1000/bus`) and the portal and notification
  services are activatable, but nothing shows on screen: verify calls at the D-Bus
  level and in tests; the user checks the real dialogs on his desktop.

Later (not now): single instance, deep links, start at login, tray and menus,
global shortcuts, multiple windows. Skip webview/cookies/PDF/printing.
