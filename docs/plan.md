# Plan: desktop APIs, batch 1 (Linux first)

Scope is the "Next task" in `docs/handover.md`. Do the tasks in order, one commit each.
Part A fixes what the review found; Part B builds the desktop APIs on top.

## What the review found

Checked on this machine on 2026-11-15 by running the code, not only by reading it.

**Bugs**

1. **Typed characters are lost between frames.** `App::type()` and `App::key()` read the
   input's text from the last built tree. Two events before the next frame both start from
   the same old text. `$app->type('a'); $app->type('b'); $app->type('c');` leaves `'c'`.
   The real loop has the same problem when GLFW delivers several key events in one batch
   (fast typing, key repeat, a slow frame). Paste would make it worse. Task A2.
2. **Three of four benchmarks crash.** `bench.php`, `bench-stages.php` and
   `bench-dashboard.php` call `Painter::paint()`, which no longer exists (it is `render()`
   now). `bench.php` also asks for an icon named `close`; the file is `x.svg`. Task A5.
3. **`php` on PATH is not the PHP that works.** Here `php` is 8.5.7 without glfw;
   `php84` (8.4.27, Remi) has glfw, sockets, posix, pcntl. The handover and README say
   `php`. Every command in this plan uses `php84`. Task A1 fixes the docs.
4. **Escape never reaches `App::onKey` while an input has focus.** The dashboard's
   "Escape closes the dialog" does nothing while the dialog's own input is focused: the
   first Escape only removes focus. Task A4.

**Risks for this batch**

5. **The loop wakes only twice a second** (`glfwWaitEventsTimeout(0.5)`) and has no hook
   for outside work. A finished dialog would be noticed up to 0.5 s late. Task A3.
6. **No teardown.** `run()` does not clean up on an exception, and nothing would kill
   child processes (an open zenity dialog) when the app quits. Task A3.
7. **The key callback drops modifiers**, so Ctrl+V and Ctrl+C cannot be told apart from V
   and C. Task A4.
8. **Auto ids are tree paths.** `App::$pressed` is matched by id on release. If a callback
   from outside (a dialog result) changes the tree between press and release, the release
   can hit a different element with the same path. Task A3 clears `$pressed` when an
   outside callback ran.
9. **No tests.** The examples print JSON; nothing asserts. Task A1.
10. **No wrapped text.** `dialog()` puts the description on one line; a long message
    runs out of the 448 px panel. Message boxes need wrapping. Task A7.
11. **`Theme` can only toggle.** Following the system needs an explicit setter and a
    "source" (system, light, dark). Task A6.
12. `glfwCreateWindow()` is not checked for failure. Fold into A3.

**The handover is wrong about this machine in two places**

- The portal on the user's session bus (`/run/user/1000/bus`) does **not** start:
  `xdg-desktop-portal.service` fails with "Dependency failed" because there is no
  graphical session. `org.freedesktop.Notifications` is not activatable at all.
- So tests cannot use the session bus. They use a private bus (`dbus-run-session`),
  see "Test setup" below. This was tried and works.

## Decision: the D-Bus transport

**A small D-Bus client in pure PHP over the bus socket. Not `gdbus`.**

Measured with the real portal on a private bus:

- `gdbus call ... FileChooser.OpenFile` returns the request handle and exits. The portal
  then sends `Response` **only to the caller's connection** (`destination=:1.15`), and
  because that connection is gone, it ends the request with code 2. The dialog is
  canceled. A one-shot call can never show a file dialog.
- `gdbus monitor` in a second process saw nothing: it is another connection, and the
  signal is not a broadcast.
- A 100-line throwaway PHP client (`stream_socket_client('unix://...')`, `AUTH EXTERNAL`,
  hand-written marshaling) connected, called `Hello`, `Settings.ReadOne` and `OpenFile`
  with filters, got the replies, kept the request open, and polled 5,000 times in 3 s
  without blocking.

So: one long-lived connection that both calls and listens. It needs no extension beyond
what the static binary already has (streams, `posix`), no helper process, and it also
carries notification clicks and theme changes. `gdbus` stays useful for poking at the
bus by hand in tests. Fallbacks when the bus or the service is missing: `zenity`,
`notify-send`, `gsettings`.

This means two files, not one: `src/DBus.php` (wire protocol) and `src/Desktop.php` (the
API). The protocol code is about 250 lines and would bury the API.

## Rules for every task

- **Never block the frame loop.** No `shell_exec`, no `proc_close` on a running child,
  no blocking read. Sockets and pipes are non-blocking (`stream_set_blocking(false)`).
  Everything is advanced by `Desktop::poll()`, called once per loop turn.
  One exception: the D-Bus connect and `AUTH` handshake block for a few ms on a local
  socket, once. Give the connect a 1 s timeout.
- Callbacks run on the main thread from `Desktop::poll()`, so they can change app state
  directly. The app is redrawn after any callback ran.
- A call that cannot work (no bus, no zenity) still calls its callback, with `null`,
  and records why in `Desktop::lastError()`. It never throws from `poll()`.
- Children are started with `proc_open` and an **argv array**, never a shell string.
- Interpreter: `php84`. Display: `DISPLAY=:99`. Check screenshots with pixel probes or
  `magick compare`, not by eye.

## Test setup (used by all tasks)

- Xvfb `:99` is running (1280x800). Start it if not: `Xvfb :99 -screen 0 1600x1000x24 &`.
- Available: `gdbus`, `dbus-monitor`, `dbus-send`, `busctl`, `dbus-run-session`, `zenity`
  4.2, `notify-send` 0.8.8, `xdg-open`, `gio`, `gsettings`, `xsel`, `xprop`, `magick`,
  `import`, `python3` with `dbus` and `gi`. Missing: `xdotool`, `xwininfo`, `xclip`.
  No tool can click inside a GTK window, so real dialogs cannot be driven here.
- **Fake mode (default, deterministic):** `dbus-run-session` starts a private bus;
  `tests/fakes/desktop.py` owns `org.freedesktop.portal.Desktop` and
  `org.freedesktop.Notifications` on it, logs every call as one JSON line, and answers
  as told. Fake `zenity`, `xdg-open`, `notify-send`, `gsettings` scripts in
  `tests/fakes/bin` are put first on `PATH` when a test wants the fallback.
- **Real-portal mode (smoke):** the same private bus, but with the real
  `/usr/libexec/xdg-desktop-portal-gtk` and `/usr/libexec/xdg-desktop-portal`, started
  with `DISPLAY=:99 GDK_BACKEND=x11 XDG_CURRENT_DESKTOP=gnome` and
  `XDG_CONFIG_HOME` pointing at a folder whose `xdg-desktop-portal/portals.conf` says
  `[preferred]` / `default=gtk`. Without that file the gnome backend is picked and file
  dialogs fail with "No such interface". Wait about 3 s after starting them.
- Trap: services started inside `dbus-run-session` (gvfsd and others) keep stdout open.
  Send their output to files, and kill the process group at the end, or the runner
  hangs after the tests finish.
- Do not run `gsettings set`: it would change the user's real settings.
- The user checks the real dialogs, notifications and drag-and-drop on his desktop
  (task B11 gives him the buttons).

---

## Part A: fix first

### A1. Test runner and the right PHP

Files: `tests/run.sh`, `tests/lib.php`, `tests/ui.php`, `tests/fakes/` (empty for now),
`README.md`, `docs/handover.md`.

- `tests/lib.php`: `check(bool $ok, string $what)`, `same($expected, $actual, string $what)`,
  a failure counter, exit code 1 on any failure, one line per check.
- `tests/run.sh [fake|real-portal] [file...]`: picks PHP (`$PHP`, else `php84`, else `php`;
  stops with a clear message if it has no glfw), starts Xvfb `:99` if absent, runs each
  `tests/*.php` under `dbus-run-session`, output to files, kills the group at the end.
- `tests/ui.php`: the todo flow from `examples/todo.php` as assertions (3 todos, filter
  open, 1 shown), plus loading `examples/dashboard.php` with `PHPUI_NO_RUN=1` and one
  click.
- Docs: say `php84` (or "a PHP with glfw") where they say `php`; correct the handover's
  claim that the session portal is activatable here.

Accept: `tests/run.sh` exits 0 and prints the checks; with `PHP=php` (8.5, no glfw) it
stops with the message, not a stack trace.

### A2. Do not lose input between frames

Files: `src/App.php`, `tests/ui.php`.

- Add `private bool $stale`. Set it after every handler that may change state:
  `onClick`, `onClickOutside`, `onInput`, `onSubmit`, `onKey`, and outside callbacks (A3).
- Add `private function fresh(): void` that calls `relayout()` when stale. Call it at the
  start of `press`, `release`, `type`, `key`, `wheel`, `find`, `focused`. `draw()` clears
  the flag.
- `click()` keeps working; its own `relayout()` call becomes `fresh()`.

Test: `type('a'); type('b'); type('c')` gives `'abc'`; then two Backspace keys give `'a'`;
two clicks on a counter button without a frame between give 2.

Accept: those pass; the dashboard and todo screenshots are unchanged
(`magick compare -metric AE` is 0 against shots taken before the change).

### A3. Loop hook, wake-up rate, teardown, `App::current()`

Files: `src/App.php`, `tests/ui.php`.

```php
public static function current(): ?App          // the app made last
public function poller(callable $fn): self      // $fn(): bool, true when it changed state
public function waitHint(callable $fn): self    // $fn(): float, seconds the loop may sleep
```

- `run()` becomes: `glfwWaitEventsTimeout(min(0.5, hint))`, then run the pollers; if one
  returned true: `stale = true`, `dirty = true`, `pressed = null`.
- Wrap the loop in `try { } finally { }`: run shutdown handlers
  (`public function onShutdown(callable $fn): self`), then destroy the window.
- Do not call `glfwTerminate()` in this teardown: A3's exception test exposed a
  native crash when NanoVG objects are destroyed after GLFW unloads its GL entry
  points. Destroy the window; let process shutdown terminate GLFW.
- Add `public function step(): void`: one loop turn without waiting (pollers, then a
  frame if dirty). Tests use it instead of `run()`.
- Throw a clear error if `glfwCreateWindow()` fails.
- B2 registers `Desktop::poll`, `Desktop::waitHint` and `Desktop::shutdown` here; `App`
  itself knows nothing about D-Bus.

Wake-up rates B2 will return: 1/60 s while a dialog, child process or method reply is
pending; 0.25 s while only signals are awaited (theme, notification clicks); else 0.5 s.

Test: a poller that returns true on its third call; call `step()` three times; the tree
shows the new state after the third. A shutdown handler runs when the loop body throws.

Accept: tests pass; idle CPU of `examples/todo.php` stays near zero (`top`, by hand).

### A4. Key modifiers and key routing

Files: `src/App.php`, `examples/dashboard.php`, `tests/ui.php`.

- Key callback takes the fourth argument: `function (int $key, int $scancode, int $action, int $mods)`.
- `public function key(int $key, int $mods = 0): void`; `onKey` handlers get `($key, $mods)`.
- Order in `key()`: 1) the open message box (B6), 2) the focused input (Backspace,
  Enter; Ctrl+V/C/X come in B7), 3) `onKey`. Escape with a focused input removes focus
  **and** goes on to `onKey`. Keys the input did not use also go on to `onKey`.

Test: with the dashboard dialog open and its input focused, `key(GLFW_KEY_ESCAPE)` closes
the dialog. `key(GLFW_KEY_V, GLFW_MOD_CONTROL)` reaches `onKey` with the mods when no
input is focused.

Accept: tests pass.

### A5. Repair the benchmarks

Files: `examples/bench.php`, `examples/bench-stages.php`, `examples/bench-dashboard.php`.

- Replace `$painter->vg->beginFrame(); $painter->paint($tree, true); ...cancelFrame()/endFrame()`
  with `$painter->render($tree, $w, $h, $ratio, true)` (it begins and ends its own
  frames). `bench-stages.php` can no longer time `endFrame` alone: report "render" and
  "GPU" (`glFinish`), and drop the other column.
- `bench.php`: icon `close` becomes `x`.

Accept: all four `examples/bench*.php` run to the end under `DISPLAY=:99 php84` and print
their tables. `tests/run.sh` runs each once with small sizes so they cannot rot again.

### A6. Theme source

Files: `src/Theme.php`, `examples/dashboard.php`, `tests/ui.php`.

```php
Theme::set(string $mode): void        // 'light' | 'dark'; throws on anything else
Theme::$source                        // 'system' | 'light' | 'dark', default 'light'
Theme::use(string $source): void      // sets $source; 'light'/'dark' also call set()
Theme::toggle(): void                 // as now, and sets $source to the new mode
```

`Theme` stays free of D-Bus: B9 calls `Theme::set()` when the source is `system`.

Accept: toggle after `use('system')` leaves the source explicit; unit checks pass.

### A7. Wrapped text

Files: `src/Painter.php`, `src/components.php`, `tests/ui.php`.

```php
Painter::wrap(string $text, float $width, string $font = 'regular', float $size = 14): array  // lines
function paragraph(string $text, float $width, float $size = 14): Element   // a col of text lines
```

- `wrap()` breaks at spaces, keeps `\n`, and breaks inside a word only when the word
  alone is wider than `$width`. It uses `textWidth()`.
- `paragraph()` needs a painter at build time: add `Painter::$shared` (set in the
  constructor) and use it. Line height as `lineHeight()`.
- Not a general layout feature; that stays in "Not yet".

Test: a 300-character sentence at width 400 gives more than one line, none wider than
400; a screenshot shows no text right of the box (pixel probe).

Accept: tests pass.

---

## Part B: desktop APIs

### B1. `src/DBus.php`: the client

Files: `src/DBus.php`, `src/autoload.php`, `tests/dbus.php`, `tests/fakes/desktop.py`.

```php
final class DBus {
    public static function session(): ?DBus                 // shared; null when there is no bus
    public string $name;                                     // unique name, e.g. ":1.42"
    public function call(string $dest, string $path, string $iface, string $member,
        string $signature = '', array $args = [], ?callable $done = null, float $timeout = 3.0): void
        // $done(?array $result, ?string $error)
    public function on(string $iface, string $member, callable $fn, ?string $path = null, ?string $sender = null): int
        // $fn(array $args, string $path); sends AddMatch; returns an id
    public function off(int $id): void
    public function poll(): bool            // read, dispatch, expire timeouts; true when a callback ran
    public function pending(): int          // calls waiting for a reply
    public function close(): void
    public static function pack(string $signature, array $values): string   // public for tests
    public static function unpack(string $signature, string $bytes): array
}
```

How it works:

- Address from `DBUS_SESSION_BUS_ADDRESS`, else `unix:path=$XDG_RUNTIME_DIR/bus`. Support
  `unix:path=` and `unix:abstract=` (leading NUL byte); take the first address in a list.
- Connect with `stream_socket_client`, 1 s timeout. Send `"\0AUTH EXTERNAL <hex of uid as text>\r\n"`,
  expect `OK`, send `BEGIN\r\n`, switch to non-blocking, call `Hello` and wait up to 1 s
  for the name (the only blocking wait). Uid from `posix_geteuid()`.
- Messages: little endian, protocol 1. Marshal `y b n q i u x t d s o g v a ( ) { }`.
  Variants are passed as `[signature, value]`; `a{..}` as PHP arrays keyed by the dict key.
  Alignment: 1 for `y g v`, 2 for `n q`, 8 for `x t d ( {`, else 4; arrays pad to the
  element alignment after the length even when empty.
- Writes go through an out buffer flushed in `poll()` (a non-blocking `fwrite` can be
  partial). Reads append to an in buffer; parse while a whole message is there.
- Reply (type 2) and error (type 3) are matched by reply serial. Signals (type 4) are
  matched by interface, member and optional path/sender. Method calls to us (type 1)
  get an `UnknownMethod` error back.
- A call with no reply by its deadline gets `$done(null, 'timeout')`.
- EOF or a read error: fail every pending call with `'disconnected'`, drop the shared
  instance; the next `session()` reconnects.
- Cap message size at 16 MB and drop the connection on a malformed header.

`tests/fakes/desktop.py` (python3-dbus + GLib loop), first version: owns
`org.myphp.Test` with `Echo(v) -> v`, `EmitTo(s name)` (sends a unicast signal),
`Hang()` (never replies). Logs calls to `$FAKE_LOG` as JSON lines.

Test (`tests/dbus.php`, fake mode):
- `pack`/`unpack` round trip for each type, nested `a{sv}`, `a(sa(us))`, empty arrays;
  compare `pack('a{sv}', ...)` bytes with a fixture from libdbus (dbus-python's wire
  library), regenerated by `tests/fakes/make-fixture.py`, to prove we match another
  implementation, not only ourselves. Python dbus exposes no marshal method, so the
  fixture generator calls its native library directly.
- `ListNames` on `org.freedesktop.DBus` contains our unique name.
- `Echo` returns what was sent; `Hang` gives `'timeout'` after 3 s while a counter loop
  shows `poll()` returned at once each time (over 1,000 turns per second).
- A unicast signal and a broadcast signal both reach `on()` handlers.
- Kill the bus mid-test: pending calls fail with `'disconnected'`, no exception.
- No bus (`DBUS_SESSION_BUS_ADDRESS=unix:path=/nonexistent`): `session()` is null within 1 s.

Accept: all pass; no call in `poll()` takes more than 2 ms (assert with `hrtime`).

### B2. `src/Desktop.php`: skeleton, child processes, loop wiring

Files: `src/Desktop.php`, `src/autoload.php`, `src/App.php` (three lines in the
constructor), `tests/desktop-jobs.php`.

```php
final class Desktop {
    public static function poll(): bool          // DBus::poll() + children; true when a callback ran
    public static function waitHint(): float     // 1/60, 0.25 or 0.5 (see A3)
    public static function shutdown(): void      // kill children, close notifications we own, close the bus
    public static function lastError(): ?string
    public static string $appName = 'PHP UI';    // shown by notifications and dialogs
    /** @internal */
    public static function spawn(array $argv, ?callable $done = null, ?array $env = null): bool
        // $done(int $exitCode, string $stdout); false when the program is not on PATH
    /** @internal */
    public static function has(string $program): bool   // PATH lookup, cached
}
```

- `spawn()`: `proc_open($argv, [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['file', '/dev/null', 'w']])`,
  stdout non-blocking. `poll()` reads what is there and checks `proc_get_status()`;
  when the child has exited, read the rest, `proc_close`, call `$done`. Keep the exit
  code from the first `proc_get_status()` that reports it (later calls return -1).
- `shutdown()`: `proc_terminate` each running child, then close pipes. Registered with
  `App::onShutdown`, and with `register_shutdown_function` for scripts that `exit`.
- `App::__construct` registers `Desktop::poll(...)`, `Desktop::waitHint(...)`,
  `Desktop::shutdown(...)`.
- Env switch for tests and for users with a broken portal:
  `PHPUI_DESKTOP=auto|portal|fallback` (default `auto`: bus first, then the tools).

Test: spawn `sh -c 'sleep 0.3; echo hi; exit 3'`; poll in a loop that also counts turns;
the callback gets `(3, "hi\n")` after about 0.3 s and the loop made hundreds of turns.
Spawn `sleep 30`, call `shutdown()`, check with `pgrep` that it is gone.
`spawn(['no-such-program'])` returns false and sets `lastError()`.

Accept: tests pass.

### B3. File dialogs through the portal

Files: `src/Desktop.php`, `tests/fakes/desktop.py`, `tests/dialogs.php`.

```php
Desktop::openFile(callable $done, string $title = 'Open', array $filters = [],
    bool $multiple = false, ?string $folder = null): void      // $done(?array $paths), null = canceled or failed
Desktop::saveFile(callable $done, string $title = 'Save', ?string $name = null,
    ?string $folder = null, array $filters = []): void          // $done(?string $path)
Desktop::pickFolder(callable $done, string $title = 'Select Folder', ?string $folder = null): void  // $done(?string $path)
// $filters: ['Images' => ['png', 'jpg'], 'All files' => ['*']]
```

How it works (`org.freedesktop.portal.FileChooser` on `org.freedesktop.portal.Desktop`,
path `/org/freedesktop/portal/desktop`):

1. Once: `on('org.freedesktop.portal.Request', 'Response', ...)`.
2. Make a token (`phpui` + counter). The request path is known before the call:
   `/org/freedesktop/portal/desktop/request/<unique name without ":" and with "." as "_">/<token>`.
   Store the callback under that path **before** calling, so a fast Response is not missed.
3. Call `OpenFile(s parent, s title, a{sv} options)` or `SaveFile(...)`, signature `ssa{sv}`.
   Parent is `''` (PHP-GLFW has no `glfwGetX11Window`, so the dialog cannot be tied to
   our window; note it in the README). Options:
   - `handle_token` `s`
   - `multiple` `b`, `directory` `b` (pick folder; needs FileChooser version 3+)
   - `filters` `a(sa(us))`: `[name, [[0, '*.png'], [0, '*.jpg']]]`; `'*'` stays `'*'`
   - `current_folder` `ay`: the path bytes plus a trailing NUL
   - `current_name` `s` (save only)
4. The method reply is the handle path. If it differs from the predicted one (old
   portals), move the callback to it.
5. `Response(u code, a{sv} results)`: code 0 gives `results['uris']` (`as`); turn
   `file://` URIs into paths with `rawurldecode(parse_url(..., PHP_URL_PATH))`. Code 1 or
   2 gives `null`.
6. Error or timeout on the method call (no portal, no backend): go to the fallback (B4).
   There is **no** timeout on the Response: the user may take minutes.
7. One file dialog at a time: a second call while one is open calls `$done(null)` and
   sets `lastError()`.

Frame loop: nothing waits. While a request is open `waitHint()` is 1/60.

Fake: `desktop.py` gains `org.freedesktop.portal.FileChooser` (`OpenFile`, `SaveFile`,
version property 4) and `org.myphp.Test.Set(s key, s value)` with keys
`chooser=ok:<path>,<path>|cancel|hold|error`. It returns the handle, then sends a
unicast `Response` after 100 ms (`hold`: never; `error`: a D-Bus error reply).

Tests:
- Fake, `ok`: callback gets the paths, including one with a space and one with `é`
  (URI decoding). The log line shows the title, `multiple`, the filters and the token.
- Fake, `cancel`: callback gets `null`. `hold`: after 1 s no callback, the loop turned
  at least 50 times, and a second `openFile` is refused.
- Fake, `error`: falls back (B4's fake zenity is called).
- Real-portal smoke: `openFile`; within 3 s there is a method reply with a handle and
  **no** Response (the GTK dialog is up on `:99`); `import -window root` differs from a
  shot taken before; then call `org.freedesktop.portal.Request.Close` on the handle and
  check the root window returns to the first shot.

Accept: tests pass in both modes. The user confirms on his desktop: open with filters,
multiple, save with a suggested name, pick folder, and cancel each.

### B4. File dialogs through zenity (fallback)

Files: `src/Desktop.php`, `tests/fakes/bin/zenity`, `tests/dialogs.php`.

Used when there is no bus, the portal call fails, or `PHPUI_DESKTOP=fallback`.

- Open: `zenity --file-selection --title=T [--multiple --separator=<newline>] [--filename=<folder>/]
  [--file-filter=Images | *.png *.jpg]...`
- Save: add `--save --filename=<folder>/<name>` (zenity 4 always confirms overwrite).
- Folder: add `--directory`.
- Through `spawn()`. Exit 0: stdout split on newlines (trim the last one). Exit 1: `null`.
  Any other exit: `null` and `lastError()`.
- No zenity: `$done(null)`, `lastError() = 'No file dialog available: install xdg-desktop-portal or zenity'`.
- `kdialog` is not in this batch.

Tests: fake `zenity` writes its argv to `$FAKE_LOG` and prints `$FAKE_ZENITY_OUT`, exits
with `$FAKE_ZENITY_EXIT`; check the argv for each of open, multiple, save, folder, and
the three results. A slow fake (`sleep 1`) shows the loop keeps turning. One real smoke:
start real `zenity --file-selection` on `:99` through `openFile`, wait 1 s (still
running, no callback), call `Desktop::shutdown()`, check the process is gone.

Accept: tests pass; `PHPUI_DESKTOP=fallback` on the user's desktop shows zenity.

### B5. Open a URL or file in the default app

Files: `src/Desktop.php`, `tests/fakes/bin/xdg-open`, `tests/open.php`.

```php
Desktop::open(string $target, ?callable $done = null): bool   // false when refused; $done(bool $ok)
```

- Accept `http:`, `https:`, `mailto:` and `file:` URLs, and existing paths (made absolute
  with `realpath`). Refuse anything else, and anything starting with `-`.
- `spawn(['xdg-open', $target])`, else `['gio', 'open', $target]`. Exit code 0 is ok.
- No blocking: the child is reaped in `poll()`.

Tests: fake `xdg-open` logs argv; a URL, a path with a space, a relative path (becomes
absolute); refused: `javascript:alert(1)`, `--help`, a missing path. Without `xdg-open`
on PATH the fake `gio` is used.

Accept: tests pass.

### B6. Message and confirm boxes (our own components)

Files: `src/components.php`, `src/App.php`, `src/Desktop.php`, `tests/message.php`.

```php
function messageBox(string $title, string $message, array $buttons, callable $onButton,
    string $type = 'info', int $default = 0, array $destructive = []): Element   // pure component

Desktop::message(string $title, string $message, array $buttons = ['OK'], ?callable $done = null,
    string $type = 'info', ?int $default = null, ?int $cancel = null, array $destructive = []): void
    // $done(int $button); $type: info | warning | error | question
Desktop::confirm(string $title, string $message, callable $done, string $ok = 'OK',
    string $cancel = 'Cancel', bool $destructive = false): void      // $done(bool $ok)
Desktop::error(string $title, string $message, ?callable $done = null): void
```

- `messageBox()` is built from `dialog()`'s look: overlay, 448 px panel, icon by type
  (`info`, `circle-alert`, `circle-help`), title, `paragraph($message, 400)` (A7), buttons
  right-aligned with ids `message-button-<i>`; destructive ones use the `destructive`
  variant; the default one the `default` variant, others `outline`.
- `App` keeps a queue of open boxes (`public function modal(Element|callable $box): void`,
  `closeModal()`). In `draw()` and `relayout()` the first box is appended as the last
  child of the root, so it is on top and takes all clicks. The overlay does not close on
  click outside.
- Keys (A4 order): Enter presses the default button, Escape the cancel button. `cancel`
  defaults to the first button named Cancel or No, else the first button (as MyGo).
  While a box is open, input focus is cleared and other keys are swallowed.
- Nothing here blocks: `$done` is called from the button's click handler.

Tests: `Desktop::confirm(...)`, screenshot, the panel is in the middle (pixel probe on the
overlay and the panel colors); `clickId('message-button-1')` calls `$done(true)` and the
box is gone on the next frame; Enter and Escape give the default and cancel buttons; two
queued boxes show one after the other; a 400-character message stays inside the panel.

Accept: tests pass in light and dark.

### B7. Clipboard

Files: `src/Desktop.php`, `src/App.php`, `tests/clipboard.php`.

```php
Desktop::clipboard(): string                 // '' when empty or not text
Desktop::setClipboard(string $text): void
```

- `glfwGetClipboardString(App::current()->window)` / `glfwSetClipboardString(...)`.
  Wrap the read in `try/catch`: return `''` if GLFW reports no text.
- Inputs: Ctrl+V types the clipboard text with line breaks turned into spaces; Ctrl+C
  copies the whole input text; Ctrl+X copies and clears. (No selection yet, so "whole
  text" is the honest behavior; say so in the README.)
- The clipboard is owned by our process on X11: it empties when the app quits. Leave
  it; note it.

Tests (both directions were tried on `:99` and work):
- `setClipboard('a ✓')` then `clipboard()` gives it back.
- Another program to us: `printf x | xsel -b -i`, then `clipboard()` is `x`.
- Us to another program: `setClipboard('y')`, then run `xsel -b -o` **through
  `Desktop::spawn`** and keep calling `glfwPollEvents()` until it exits: output `y`.
  (A blocking `shell_exec('xsel -b -o')` deadlocks: we are the owner and must answer.)
- `key(GLFW_KEY_V, GLFW_MOD_CONTROL)` in a focused input inserts the text.
- `pkill xsel` at the end.

Accept: tests pass.

### B8. Files dropped on the window

Files: `src/App.php`, `src/Element.php`, `tests/drop.php`.

```php
App::onDrop(callable $fn): self              // $fn(array $paths, float $x, float $y); window level
Element::onDrop(callable $fn): self          // $fn(array $paths); the nearest one under the pointer wins
App::drop(array $paths, ?float $x = null, ?float $y = null): void   // scripted, like click()
```

- `glfwSetDropCallback($window, fn (array $paths) => $this->drop($paths))` (check the
  callback's arguments with a `...$args` dump first; PHP-GLFW's are not documented).
- `drop()`: `fresh()`, move the pointer if x/y are given, find the nearest element with
  `onDrop` from the top element up (`handler()`), call it; else call the window handler.
  Then `stale`/`dirty`.
- Lives in `App`/`Element`, not `Desktop`: it is window input, routed by hit-testing.

Tests: scripted `drop()` over an element with `onDrop` reaches it; over empty space
reaches the window handler; paths with spaces arrive unchanged. A real XDND drop cannot
be made here (no `xdotool`): the user tests it in B11.

Accept: tests pass; the user drops files from his file manager on the example.

### B9. Follow the system dark mode

Files: `src/Desktop.php`, `tests/fakes/desktop.py`, `tests/fakes/bin/gsettings`, `tests/theme.php`.

```php
Desktop::systemDark(): ?bool                        // last known; null = unknown or no preference
Desktop::followSystemTheme(?callable $onChange = null): void   // Theme::use('system'), read now, watch changes
```

- Read: `org.freedesktop.portal.Settings.ReadOne('org.freedesktop.appearance', 'color-scheme')`
  (signature `ss`, reply `v` holding `u`): 1 dark, 2 light, 0 no preference. On
  `UnknownMethod` (Settings older than version 2) call `Read`, whose value is a variant
  inside a variant. The real portal on the private bus answered `1` here.
- Watch: `on('org.freedesktop.portal.Settings', 'SettingChanged', ...)` with args
  `(s namespace, s key, v value)`; filter on the namespace and key.
- Apply only while `Theme::$source === 'system'`: `Theme::set(...)`, then `$onChange`.
  0 keeps the current mode. The reply arrives in `poll()`, so the first frame may be
  light and the second dark; to avoid the flash, `followSystemTheme()` may wait for this
  one reply for at most 150 ms when called before `run()`. That is the only allowed wait.
- Fallback without a portal: `spawn(['gsettings', 'get', 'org.gnome.desktop.interface', 'color-scheme'])`,
  `'prefer-dark'` means dark. No live updates in the fallback; say so.
- `waitHint()` is 0.25 s while watching, so a change shows within a quarter second.

Fake: `desktop.py` gains `org.freedesktop.portal.Settings` (`ReadOne`, `Read`, version 2)
and `Test.Set('scheme', '0|1|2')`, which also emits `SettingChanged`.

Tests: start with scheme 1: after `followSystemTheme()` and a few `step()`s `Theme::dark()`
is true and a screenshot's corner pixel is the dark background. `Set scheme 2`: light
within 0.5 s, `$onChange` called once. `Theme::toggle()` then `Set scheme 1`: no change
(source is explicit). Fallback: fake `gsettings` prints `'prefer-dark'`. Real-portal
smoke: read returns 0, 1 or 2 without error.

Accept: tests pass; on the user's desktop, switching GNOME's style flips the example.

### B10. Notifications with click handling

Files: `src/Desktop.php`, `tests/fakes/desktop.py`, `tests/fakes/bin/notify-send`, `tests/notify.php`.

```php
Desktop::notify(string $title, string $body = '', ?callable $onClick = null,
    ?string $icon = null, array $actions = []): int      // our id; $onClick(string $action), 'default' for the body
    // $actions: ['open' => 'Open', 'dismiss' => 'Dismiss']
Desktop::closeNotification(int $id): void
```

How it works (`org.freedesktop.Notifications`, path `/org/freedesktop/Notifications`):

- Once: `on(..., 'ActionInvoked', ...)` `(u id, s action)` and `on(..., 'NotificationClosed', ...)` `(u id, u reason)`.
- `Notify(s app, u replaces, s icon, s summary, s body, as actions, a{sv} hints, i timeout)`,
  signature `susssasa{sv}i`: app is `Desktop::$appName`, replaces 0, actions
  `['default', 'Open', ...]` only when `$onClick` is set (the `default` action makes a
  click on the body report back), hints empty, timeout -1.
- The reply is the server's id. We return our own id at once and map it when the reply
  comes; a `closeNotification()` before the reply is remembered and sent after it.
- `ActionInvoked` for a known id calls `$onClick($action)`. `NotificationClosed` drops
  the entry. Keep at most 64 entries; drop the oldest.
- `$icon` is a name or an absolute path. A `phar://` path must be copied out first (same
  trap as fonts): move `Painter::realFile()` to a shared helper and use it.
- `waitHint()` is 0.25 s while a notification with a click handler is alive.
- `shutdown()` does not close notifications: a "done" notice should outlive the app.
- Fallback (no bus, or `Notify` fails): `spawn(['notify-send', '--app-name=...', '--icon=...',
  $title, $body])`; with `$onClick`, add `--action=default=Open` (0.8.8 here supports it
  and waits): the action name comes on stdout when clicked. These waiting children are
  killed in `shutdown()`.
- Typical click handler: `glfwFocusWindow($app->window)`; add `App::focus()` for it.

Fake: `desktop.py` gains `org.freedesktop.Notifications` (`Notify` returns a counter,
`CloseNotification`, `GetCapabilities` with `actions`, `GetServerInformation`) and
`Test.Invoke(u id, s action)` / `Test.CloseNote(u id)`, which emit the signals unicast
to the sender of that `Notify`.

Tests: `notify('T', 'B', $cb)`: the log line has app name, summary, body and
`['default', 'Open']`; `gdbus call ... org.myphp.Test.Invoke 1 default` then a few
`step()`s: `$cb('default')` ran once. A named action reports its key. `CloseNote` then
`Invoke`: no callback. Without `$onClick` the actions array is empty. No
notification service on the bus (fake started without it): fake `notify-send` gets the
argv. `closeNotification` right after `notify` sends `CloseNotification` with the
server's id.

Accept: tests pass; on the user's desktop a click on the notification focuses the window.

### B11. Example and docs

Files: `examples/desktop.php`, `README.md`, `docs/handover.md`, `build/make-phar.php`.

- `examples/desktop.php`: one card with a button per API (open file, open many with an
  Images filter, save, pick folder, message, confirm, error, notify with click, copy,
  paste, open URL, follow system theme) and a log list showing each result; the whole
  window is a drop target that logs dropped paths. Ids on every button.
- `PHPUI_SCREENSHOTS` mode like the other examples: clicks the buttons that need no
  desktop (message, confirm, copy/paste, drop via `App::drop`) and saves PNGs.
- README: a "Desktop" section with the signatures above, the fallbacks, the env switch,
  and the known limits (dialogs are not tied to the window; clipboard empties on quit;
  copy takes the whole input; theme fallback has no live updates).
- Handover: replace "Next task" with what was built and what the user still has to
  check by hand.
- Check the one-file binary still works: `build/make-binary.sh`, run it on `:99` with
  the example packed in, click "confirm" in screenshot mode. `proc_open` and
  `stream_socket_client` must exist in `micro.sfx` (`posix` is in the craft list).

Accept: `tests/run.sh` (fake) and `tests/run.sh real-portal` exit 0; the example's
screenshot run prints its JSON; the user has gone through the manual list:

1. Each file dialog: pick, pick many, cancel; save with a name; folder.
2. `PHPUI_DESKTOP=fallback`: the same through zenity.
3. Notification: appears, click focuses the window, action button reports its key.
4. Copy in the app, paste in another app, and back.
5. Drop two files from the file manager.
6. Open a URL and a PDF.
7. Switch the system style: the app follows; toggle in the app: it stops following.

## Not in this batch

Single instance, deep links, start at login, tray and menus, global shortcuts, multiple
windows, `kdialog`, the portal's own Notification and OpenURI interfaces, text selection
in inputs, macOS and Windows backends. Webview, cookies, PDF and printing are skipped.
