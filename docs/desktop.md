# Desktop

```sh
myphp run examples/desktop.php
PHPUI_DESKTOP=fallback myphp run examples/desktop.php  # force the command-line tools
```

Create an `App`, then call `PhpUi\Desktop` from your handlers. `App` polls desktop
work automatically. Callbacks run on the main thread and trigger a redraw; file
dialogs and child processes never wait in the frame loop. The initial local D-Bus
connection/authentication has a bounded startup wait. There is one file chooser at
a time, with no timeout while the user is choosing. Cancellation or failure returns
`null`; `Desktop::lastError(): ?string` gives the failure reason.

API (all methods below are static on `Desktop`):

```php
openFile(callable $done, string $title = 'Open', array $filters = [],
    bool $multiple = false, ?string $folder = null): void; // done(?array $paths)
saveFile(callable $done, string $title = 'Save', ?string $name = null,
    ?string $folder = null, array $filters = []): void;    // done(?string $path)
pickFolder(callable $done, string $title = 'Select Folder', ?string $folder = null): void;
// Filters: ['Images' => ['png', 'jpg'], 'All files' => ['*']]

message(string $title, string $message, array $buttons = ['OK'], ?callable $done = null,
    string $type = 'info', ?int $default = null, ?int $cancel = null,
    array $destructive = []): void; // done(int $button); destructive: button indexes
confirm(string $title, string $message, callable $done, string $ok = 'OK',
    string $cancel = 'Cancel', bool $destructive = false): void; // done(bool $ok)
error(string $title, string $message, ?callable $done = null): void;

notify(string $title, string $body = '', ?callable $onClick = null,
    ?string $icon = null, array $actions = []): int; // local notification ID
// onClick(string $action): 'default' for body clicks; actions: ['open' => 'Open']
closeNotification(int $id): void;
clipboard(): string;
setClipboard(string $text): void;
open(string $target, ?callable $done = null): bool; // done(bool $ok); false if refused
systemDark(): ?bool;                             // null = unknown/no preference
followSystemTheme(?callable $onChange = null): void; // onChange(bool $dark)
```

Set `Desktop::$appName` to the app's name. Notifications accept an icon name or an
absolute path (including bundled `phar://` assets); use `$app->focus()` in a click
handler. Delivered notifications outlive app shutdown. Message boxes are queued,
use wrapped text, and support `info`, `warning`, `error`, `question`; Enter selects
the default button and Escape the cancel button (first Cancel/No, otherwise first).
They do not dismiss on outside clicks. The pure `PhpUi\messageBox()` component can
also be used directly.

```php
$app->onDrop(function (array $paths, float $x, float $y) { /* window target */ });
$element->onDrop(function (array $paths) { /* nearest target under pointer */ });
$app->drop(['/tmp/example.txt'], 20, 20); // scripted input, like clickId()
Theme::use('system'); // source only; Desktop::followSystemTheme() also reads/watches
Theme::set('dark');   // mode only, preserves source
Theme::toggle();      // changes mode and switches source to explicit light/dark
```

| API | Linux first choice | Linux fallback | macOS (experimental) | Windows (experimental) |
|---|---|---|---|---|
| File chooser | Desktop portal FileChooser | `zenity` | `osascript` (`choose file`) | `powershell` (Windows Forms dialogs) |
| Notifications | `org.freedesktop.Notifications` | `notify-send` (0.8+ for actions) | `osascript`; no icon, click or actions | `powershell` tray balloon; no icon, click or actions |
| System theme | Portal Settings read + signals | `gsettings get` (one read, no live updates) | `defaults read` (one read, no live updates) | `reg query` (one read, no live updates) |
| Open URL/file | `xdg-open` | `gio open` | `open` | `rundll32 url.dll` |
| Clipboard/drop | SDL3 | — | SDL3 | SDL3 |
| Message/confirm | In-app components | — | In-app components | In-app components |

`PHPUI_DESKTOP=auto` is the default (bus first, then tools). `portal` disables the
file/notification/theme tool fallbacks; `fallback` skips D-Bus. D-Bus needs `posix`
and PHP streams, not a PHP D-Bus extension or `gdbus`. The connection stays alive to
receive portal responses and notification actions.

Known limits:

- File dialogs are not tied to the app window yet. A chooser may appear behind the app. Zenity multiple results cannot
  represent filenames containing newlines.
- Inputs copy, cut and paste the selection with Ctrl+C/X/V (Cmd on macOS); pasting
  into a one-line input turns line breaks into spaces. With nothing selected, copy
  and cut take the **whole input**. On X11 the clipboard is
  owned by the app and disappears on quit unless a clipboard manager saves it.
- System-theme fallback has no live updates. No-preference preserves the current
  theme. Toggling the theme in the app stops following the system.
- `open()` accepts `http:`, `https:`, `mailto:`, `file:` and existing local paths;
  it rejects other schemes and targets starting with `-`.
- One window. On macOS, notifications are shown as coming from Script Editor and
  file-type filters are a flat list of extensions. Tray,
  menus, global shortcuts, single instance and deep links are not implemented.

Tests: `tests/run.sh` uses deterministic private-bus fakes;
`tests/run.sh real-portal` also opens/closes a real GTK portal chooser and zenity on
Xvfb and reads real portal settings. Test config/cache/data are isolated; it never
writes the user's system-style preference. Pass test filenames to run a subset.

## Audio

The runtime includes an audio engine (miniaudio, in phpsdl): no extension or library
to install. It decodes MP3, FLAC and WAV.

```php
$engine = new GL\Audio\Engine([]);
$engine->start();
$song = $engine->soundFromDisk(__DIR__ . '/song.mp3');
$song->play();             // stop() pauses; play() resumes
$song->seekTo(30.0);       // seconds; getCursor() and getLength() too
$song->setVolume(0.8);
```

`readFrames()` returns decoded samples, e.g. to draw a waveform. Redraw while
playing with `$app->every(1 / 30, ...)`. `examples/player.php` is a complete example.

