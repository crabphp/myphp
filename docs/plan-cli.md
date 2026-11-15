# Plan: the myphp CLI and app workflow

Scope is `docs/next.md`. Do the tasks in order, one commit each. Target:

    curl -fsSL https://raw.githubusercontent.com/crabphp/myphp/main/install.sh | sh
    myphp init hello && cd hello && myphp run
    myphp build          # build/hello: one executable, no PHP needed

## Facts checked on this machine (2026-11-15)

1. `~/.cache/spc/buildroot/bin/micro.sfx` is 11,119,360 bytes, PHP 8.4.27, glibc 2.17
   target, extensions: glfw, opcache, phar, mbstring, ctype, tokenizer, zlib, json,
   posix, pcntl. **No openssl/curl**: the CLI cannot download anything itself.
2. A micro binary is `micro.sfx` + optional INI block + phar, plain concatenation.
   The INI block is `"\xfd\xf6\x69\xe6"` + `pack('N', strlen($ini))` + `$ini`.
3. Without an INI block `phar.readonly` is 1 and `new Phar()` throws. With the block
   `phar.readonly=0\n` a micro binary creates phars. Tested: works.
4. **A micro binary cannot read itself by its own path**: opening
   `micro_get_self_filename()` returns only the payload (the phar; `filesize()` gave
   1244). `/proc/self/exe` returns the real file (starts with `7f454c46`).
5. `micro_get_sfxsize()` includes the INI block (11,119,384 with a 24-byte block), so
   it is not the size of the clean sfx. (`micro_get_sfx_filesize()` is deprecated.)
6. So: store the clean sfx size in the CLI phar at build time. The first N bytes of
   `/proc/self/exe` are then byte-identical to `micro.sfx` (md5 matched), and
   sfx + a phar made by the binary itself ran correctly, with `phar.readonly=1` in the
   built app. **`myphp build` needs no second file and no network.**
7. `require` of a file outside the phar works under micro; `__DIR__` and `getcwd()` are
   normal. `argv[0]` is the binary; `PHP_BINARY` is empty; `PHP_SAPI` is `micro`.
8. The IDE stubs are **`phpgl/ide-stubs`** on Packagist (v1.0.4, `php >=8.0`, MIT/Apache-2.0).
9. `crabphp/myphp` is **not on Packagist** (404). The GitHub repo is **private** and has
   **no releases**; `raw.githubusercontent.com/crabphp/myphp/main/...` returns 404 for
   anonymous users. See "User steps".
10. `gh`, `hc`, `composer` (2.10.1) are in `~/.local/bin`; use full paths if PATH lacks it.
11. The repo has no LICENSE file.

## Decisions

- **Binary layout**: `myphp` = `micro.sfx` + INI block `phar.readonly=0` + `myphp.phar`.
  The phar holds `cli/`, `src/`, `assets/`, `examples/{dashboard,desktop,todo}.php`,
  `version` (text) and `sfx-size` (text, bytes of the clean sfx).
- **`myphp build`** = first `sfx-size` bytes of `/proc/self/exe` + app phar. No INI block
  in built apps (fact: OPcache INI crashes micro; none is needed).
- **App phar layout**: `src/`, `assets/` (toolkit) and `app/` (the project). An app file
  that does `require __DIR__ . '/../src/autoload.php'` (the examples) still works.
- **CLI code**: `bin/myphp` (thin, for running from source with `php84`) and `cli/`.
  Plain functions, no framework, no dependencies.
- **Names stay**: namespace `PhpUi`, env `PHPUI_*`, cache `~/.cache/phpui`. No rename here.
- **Release assets**: `myphp-linux-x86_64` and `myphp-linux-x86_64.sha256`, tag `vX.Y.Z`.
- **Composer** is only for the editor. The app template never requires `vendor/`.
  `vendor/` is not packed by `myphp build`.
- **demo/**: removed in task C11. `myphp demo [name]` replaces it (same examples, inside
  the release binary). Until the first release, `build/make-myphp.sh` builds the same
  binary locally. Git history is not rewritten.
- **`build/make-binary.sh` and `build/make-phar.php` stay** as they are (handover and
  `tests/example.php` use `build/dashboard`).
- Hot reload: not now.

## User steps (not for the implementer)

The implementer does not create releases, change repo visibility or publish packages.

1. Make `crabphp/myphp` public (the `curl | sh` line and release downloads need it).
2. Choose a license; add `LICENSE` and the `license` field in `composer.json`.
3. Run `build/release.sh 0.1.0` for the first release.
4. Submit `https://github.com/crabphp/myphp` to Packagist (needs a tag).
5. On a real desktop: `myphp demo desktop` and the checklist in `docs/handover.md`.

## Rules for every task

- Use `php84` for source runs and tests. `DISPLAY=:99` (Xvfb); there is no desktop.
- `tests/run.sh` must pass before each commit. New CLI tests go in `tests/cli.php` and
  `tests/install.php`, using `tests/lib.php` (`check`, `same`, `pixel`).
- Tests run commands with `proc_open` and argv arrays, in a temp dir under
  `getenv('PHPUI_TEST_TMP')`. Never write into the repo or `~/.local/bin`.
- CLI rules: output to stdout, errors to stderr as `myphp: <message>`, exit 0 on
  success, 1 on failure, 2 on wrong usage (unknown command, missing argument).
- Do not create releases or tags, do not push tags, do not change repo settings.

---

### C1. One-frame screenshot switch in `App::run()`

Files: `src/App.php`, `tests/ui.php`, `README.md` (one line).

Needed to test `myphp run` and built apps without a desktop or test code in the app.

- At the start of `App::run()`: if `getenv('PHPUI_SCREENSHOT')` is a non-empty path,
  read the window size (`glfwGetWindowSize`), call
  `$this->screenshot($path, $w, $h, 1)`, then run the same teardown as a normal exit
  (shutdown handlers, destroy window) and return. No event loop.
- Singular name. `PHPUI_SCREENSHOTS` (plural, used by the examples) is unchanged.

Test: in `tests/ui.php`, start `php84 examples/todo.php` as a child with
`PHPUI_SCREENSHOT=$tmp/one.png` and without `PHPUI_SCREENSHOTS`: exit code 0 within
5 s, the PNG exists, is 640x560 (`magick identify`), and `pixel()` at (0,0) is the
theme background.

Accept: the child exits by itself; existing tests still pass.

### C2. Make `src/autoload.php` safe to load twice

Files: `src/autoload.php`, `tests/ui.php`.

- First line after the glfw check: `if (class_exists(\PhpUi\App::class, false)) return;`
- Reason: under `myphp run` the toolkit is already loaded from the phar; an app (or an
  example, or `vendor/autoload.php`) that requires another copy must not hit
  "Cannot redeclare function".

Test: copy `src/` to a temp dir, `require` the repo's `src/autoload.php`, then the
copy's: no error, `PhpUi\App` is defined once.

Accept: the test passes; the examples run as before.

### C3. CLI skeleton: `help`, `version`, dispatch

Files: `bin/myphp` (new, executable), `cli/main.php` (new), `tests/cli.php` (new).

- `bin/myphp`: `#!/usr/bin/env php`, then `require dirname(__DIR__) . '/cli/main.php';`.
  It is only for source runs: `php84 bin/myphp help`. The binary's stub requires
  `cli/main.php` directly (no shebang line inside the phar).
- `cli/main.php`:
  - `const MYPHP_ROOT = dirname(__DIR__)` equivalent (a `phar://myphp.phar` path in the
    binary, the repo on disk in source runs). All toolkit/template paths start there.
  - `ini_set('display_errors', 'stderr')`, `error_reporting(E_ALL)`.
  - `myphpVersion()`: contents of `MYPHP_ROOT/version` if the file exists, else `dev`.
  - Dispatch on `$argv[1]`: `help`, `-h`, `--help` or nothing prints help (exit 0);
    `version`, `-v`, `--version` prints `myphp <version> (PHP <PHP_VERSION>)`;
    `init`, `run`, `build`, `demo` call `cmdInit(array $args): int` and so on (added in
    later tasks; until then they are not listed); anything else prints
    `myphp: unknown command "x"` plus `Run "myphp help".` to stderr, exit 2.
  - Help text, short:

        myphp - native desktop apps in PHP

        Usage:
          myphp init <name>     create a new app
          myphp run [file]      run an app (default: app.php)
          myphp build [file]    build one executable (default: app.php)
          myphp demo [name]     run a bundled example: dashboard, desktop, todo
          myphp version         print the version
          myphp help            print this help

- `tests/cli.php` starts with a helper
  `cli(array $args, ?string $cwd = null, array $env = []): array` returning
  `[exit code, stdout, stderr]`. It runs `getenv('MYPHP_BIN')` when set, else
  `[PHP_BINARY, 'bin/myphp']`. From C4 on, `MYPHP_BIN` is the built binary.

Tests: `help` and no arguments: exit 0, stdout contains `myphp version` (only
implemented commands are listed; assert `myphp init <name>` starting in C6).
Correction in C3: the original init assertion contradicted the requirement not to
list commands before they exist.
`version`: matches `/^myphp \S+ \(PHP 8\.\d+\.\d+\)$/`. `nope`: exit 2, stderr has
`unknown command`, stdout empty.

Accept: `php84 bin/myphp version` prints `myphp dev (PHP 8.4.27)`.

### C4. Build the `myphp` binary

Files: `build/make-myphp.php`, `build/make-myphp.sh` (new), `.gitignore`, `tests/cli.php`.

- `build/make-myphp.sh [path/to/micro.sfx]`: same shape as `build/make-binary.sh`.
  Default sfx `$HOME/.cache/spc/buildroot/bin/micro.sfx`; fail with a clear message if
  missing. Runs `"$PHP" -d phar.readonly=0 make-myphp.php "$SFX"`, prints `ls -lh myphp`.
- `build/make-myphp.php`:
  1. Build `build/myphp.phar`: `buildFromDirectory` for `src`, `assets`, `cli`;
     `addFile` for `examples/dashboard.php`, `examples/desktop.php`, `examples/todo.php`;
     `addFromString('version', getenv('MYPHP_VERSION') ?: 'dev')`;
     `addFromString('sfx-size', (string) filesize($sfx))`.
  2. Stub: `<?php Phar::mapPhar('myphp.phar'); require 'phar://myphp.phar/cli/main.php'; __HALT_COMPILER();`
  3. Write `build/myphp` = sfx bytes + INI block for `"phar.readonly=0\n"` (fact 2) +
     phar bytes; `chmod 0755`.
- `.gitignore`: add `build/myphp` and `build/release/` (`build/*.phar` is already there).
- `tests/cli.php`: at the top, run `build/make-myphp.sh` (fail the test with its output
  if it fails) and set `MYPHP_BIN` to `build/myphp` unless already set. All CLI tests
  now run against the real binary.

Tests: C3's tests pass against `build/myphp`. Also: `build/myphp version` prints
`myphp dev (PHP 8.4.27)`; with `MYPHP_VERSION=9.9.9 build/make-myphp.sh` it prints
`myphp 9.9.9 ...` (rebuild as `dev` afterwards). The first `filesize(micro.sfx)` bytes
of `build/myphp` equal `micro.sfx` (`cmp`).

Accept: `build/myphp help` works with an empty `PATH` (`env -i build/myphp help`);
size is about 12 MB; nothing new is tracked by git except the two scripts.

### C5. `myphp run [file]` and `myphp demo [name]`

Files: `cli/main.php` (or `cli/run.php`), `tests/cli.php`.

- `run [file] [args...]`: file defaults to `app.php` in the current directory. Missing
  file: `myphp: app.php not found. Create an app with "myphp init <name>".`, exit 1.
- Steps: `require MYPHP_ROOT . '/src/autoload.php'`; set `$argv` and
  `$_SERVER['argv']` to `[file, ...args]` (and `argc`); `require` the file's
  `realpath()` inside a static closure so CLI variables do not leak into the app.
  The working directory is not changed. Exit with 0 when the app returns.
- An uncaught exception from the app: print `myphp: <class>: <message>` and the trace
  to stderr, exit 1.
- `demo [name]`: name is `dashboard` (default), `desktop` or `todo`; anything else is
  usage error 2 listing the names. Runs `MYPHP_ROOT/examples/<name>.php` the same way
  (its `require __DIR__ . '/../src/autoload.php'` is harmless after C2).

Tests (all through `build/myphp`, `DISPLAY=:99` comes from the runner):
- Temp dir with `app.php` (a `col()` with one `text()`, `new App(...)`, `->run()`, no
  `require`): `PHPUI_SCREENSHOT=$tmp/run.png myphp run` exits 0; PNG exists; background
  pixel as expected. Fonts load from the phar through `realFile()` (the text renders:
  some pixel inside the text box differs from the background).
- `myphp run other.php a b`: the app writes `json_encode($argv)` to a file:
  `["other.php","a","b"]`.
- `myphp run` in an empty dir: exit 1, stderr has `app.php not found`.
- An app that throws: exit 1, stderr has the message.
- `PHPUI_SCREENSHOTS=$tmp/shots myphp demo todo`: exit 0, `todo-1-start.png` exists.
- `myphp demo nope`: exit 2.

Accept: the tests pass; `grep -n 'PHP_BINARY' cli/` finds nothing (the binary never
starts another PHP).

### C6. `myphp init <name>`

Files: `cli/main.php` (or `cli/init.php`), `cli/template/app.php`,
`cli/template/composer.json`, `cli/template/gitignore`, `cli/template/README.md`,
`tests/cli.php`.

- `<name>` must match `^[a-z0-9][a-z0-9._-]*$` (case-insensitive); it is the directory
  name. Missing name: usage error 2. The directory must not exist or must be empty,
  else `myphp: "hello" already exists and is not empty`, exit 1.
- Copies the template, replacing `{{name}}` (and `{{constraint}}`, see below) in
  `composer.json`, `README.md` and `app.php`. The template file `gitignore` is written
  as `.gitignore` (no dotfiles in the template, so no packing rule can drop it).
- Does **not** run composer or git. Prints:

      Created hello.

        cd hello
        myphp run            start the app
        myphp build          make one executable in build/
        composer install     optional: editor autocomplete

- Template:
  - `app.php`: `declare(strict_types=1)`, `use PhpUi\App;`, `use function PhpUi\{...};`,
    a small counter (a `card` with a title, a count `text` and a `button` that
    increments a state object), `(new App('{{name}}', 480, 320, $render))->run();`.
    No `require`. A one-line comment: run with `myphp run`.
  - `composer.json`:

        {
            "name": "app/{{name}}",
            "description": "A MyPHP desktop app",
            "type": "project",
            "require-dev": {
                "crabphp/myphp": "{{constraint}}",
                "phpgl/ide-stubs": "^1.0"
            }
        }

    `{{constraint}}` is `^X.Y` from the CLI version, or `dev-main` when the version is
    `dev`. (`name` is lowercased; Composer rejects uppercase.)
  - `gitignore`: `/vendor/` and `/build/`.
  - `README.md`: the three commands and a link to
    `https://github.com/crabphp/myphp/blob/main/docs/getting-started.md`.

Tests: `myphp init hello` in a temp dir: exit 0; the four files exist; `app.php`
passes `php84 -l`; `composer.json` decodes and names `phpgl/ide-stubs`; no `{{` left
in any file. Then in `hello`: `PHPUI_SCREENSHOT=... myphp run` exits 0 with a PNG
(no `vendor/` present). `myphp init hello` again: exit 1. `myphp init` and
`myphp init 'bad name'`: exit 2.

Accept: the tests pass; `~/.local/bin/composer validate` in the new folder reports no
errors (run by hand; needs no network).

### C7. `myphp build [file]`

Files: `cli/main.php` (or `cli/build.php`), `tests/cli.php`.

- `build [file] [-o output]`: file defaults to `app.php`. The project is the file's
  directory. Output defaults to `<project>/build/<project directory name>`.
- Sfx bytes: if `PHP_SAPI === 'micro'`: the first `(int) file_get_contents(MYPHP_ROOT . '/sfx-size')`
  bytes of `/proc/self/exe` (facts 4-6; never `micro_get_self_filename()`); check the
  length read and that it starts with `"\x7fELF"`. Otherwise (source run): the file
  in env `MYPHP_SFX`, and `phar.readonly` must be 0; if not, exit 1 with
  `myphp: build needs the myphp binary (or MYPHP_SFX and -d phar.readonly=0)`.
- App phar, written to a temp file ending in `.phar` (`sys_get_temp_dir()`), removed
  afterwards:
  - `src/` and `assets/` copied from `MYPHP_ROOT`.
  - The project under `app/`, recursively, skipping `vendor/`, `build/`, `.git/` and
    any path with a component starting with `.`. Skip the output file itself.
  - Stub: `<?php Phar::mapPhar('app.phar'); require 'phar://app.phar/src/autoload.php'; require 'phar://app.phar/app/<entry>'; __HALT_COMPILER();`
- Write sfx + phar to the output (create `build/`), `chmod 0755`, print
  `Built build/hello (12.1 MB)`.
- App assets: a path like `__DIR__ . '/logo.png'` is a `phar://` path in the built app.
  PHP file functions read it; for native APIs and child programs use
  `PhpUi\realFile()` (docs, C9).

Tests: in the `hello` project from C6: `myphp build` exits 0; `build/hello` exists and
is executable; its first `filesize(micro.sfx)` bytes equal `micro.sfx`. Copy
`build/hello` alone to another temp dir and run it with `env -i DISPLAY=:99 HOME=$tmp
XDG_CACHE_HOME=$tmp/cache PHPUI_SCREENSHOT=$tmp/built.png`: exit 0, and the PNG's
probed pixels equal those of the `myphp run` screenshot. Add `data.txt` to the project
and read it with `file_get_contents(__DIR__ . '/data.txt')` in `app.php`: the built
app sees it. `vendor/x.php` and `.env` are not in the phar (a second build with those
files present has no such entries: list with `php84 -r` and `Phar` on the temp copy of
the payload, or have the app report `file_exists(__DIR__ . '/vendor/x.php')`).
`myphp build -o $tmp/out/app2`: written there. `myphp build missing.php`: exit 1.

Accept: `myphp build` works with the network off and no PHP on `PATH`; the built file
is about 12 MB; `tests/run.sh` passes.

### C8. Composer package for editor autocomplete

Files: `composer.json` (new, repo root), `.gitattributes` (new).

- `composer.json`:

      {
          "name": "crabphp/myphp",
          "description": "Native GPU desktop apps in plain PHP",
          "type": "library",
          "require": { "php": ">=8.2" },
          "require-dev": { "phpgl/ide-stubs": "^1.0" },
          "suggest": { "ext-glfw": "Needed to run without the myphp binary" },
          "autoload": { "files": ["src/autoload.php"] }
      }

  No `ext-glfw` in `require`: Composer would refuse to install on a machine whose PHP
  has no glfw, and this package is for the editor. No `license` field until the user
  chooses one (`composer validate` warns, does not fail).
- `.gitattributes`: `export-ignore` for `/demo`, `/tests`, `/build`, `/docs`,
  `/examples`, `/cli`, `/bin`, `/install.sh` (the dist zip keeps `src/`, `assets/`,
  `composer.json`, `README.md`).
- No `vendor/` or `composer.lock` is committed; add `/vendor/` and `/composer.lock` to
  `.gitignore`.

Test (by hand, needs network for `phpgl/ide-stubs`; not in `tests/run.sh`):

    ~/.local/bin/composer validate
    cd "$(mktemp -d)" && /path/to/build/myphp init ed && cd ed
    ~/.local/bin/composer config repositories.myphp path /home/daudau/Code/crabphp/myphp
    ~/.local/bin/composer install
    ls vendor/crabphp/myphp/src/App.php vendor/phpgl/ide-stubs
    DISPLAY=:99 PHPUI_SCREENSHOT=/tmp/ed.png /path/to/build/myphp run   # vendor/ present, still runs

The `path` repository stands in for Packagist until the user publishes the package.
With the system `php` (8.5, no glfw) the install must still succeed.

Accept: `composer validate` has no errors; the install above works; the app runs with
and without `vendor/`.

### C9. `install.sh`

Files: `install.sh` (new, repo root, POSIX `sh`), `tests/install.php` (new).

- `set -eu`. Linux and `x86_64` only (`uname -s`, `uname -m`); otherwise
  `myphp: only Linux x86-64 is supported for now` and exit 1.
- Variables:
  - `MYPHP_INSTALL_DIR`, default `$HOME/.local/bin`.
  - `MYPHP_VERSION`, default empty = latest. URL:
    `https://github.com/crabphp/myphp/releases/latest/download/myphp-linux-x86_64`, or
    `.../releases/download/v$MYPHP_VERSION/myphp-linux-x86_64`.
  - `MYPHP_DOWNLOAD_URL` overrides the URL (tests use `file://`).
- Download with `curl -fsSL` (or `wget -qO` if there is no curl) into `mktemp` in the
  install dir; download `<url>.sha256` too and compare with `sha256sum` when both are
  available (compare only the first word of the `.sha256` file, the hash); a mismatch
  deletes the file and exits 1. A missing `.sha256` or missing
  `sha256sum` prints a warning and continues.
- `chmod 755`, `mv` to `$MYPHP_INSTALL_DIR/myphp` (atomic replace), run
  `"$MYPHP_INSTALL_DIR/myphp" version` and print it. If the dir is not in `PATH`, print:
  `Add to your shell profile: export PATH="$HOME/.local/bin:$PATH"`.
- A `trap` removes the temp file on any exit.

Tests (`tests/install.php`; build `build/myphp` first as in C4, write
`myphp.sha256` next to a copy in a temp dir):
- `MYPHP_DOWNLOAD_URL=file://$tmp/src/myphp MYPHP_INSTALL_DIR=$tmp/bin sh install.sh`:
  exit 0; `$tmp/bin/myphp version` works; output has the PATH hint.
- With `$tmp/bin` in `PATH`: no hint.
- Wrong checksum file: exit 1, no `myphp` left in the dir, no temp files.
- No checksum file: exit 0 with a warning.
- Running twice replaces the binary (exit 0).
- `sh -n install.sh` passes; `shellcheck install.sh` too if installed.

Accept: the tests pass; nothing is written under the real `~/.local/bin`.

### C10. Release script

Files: `build/release.sh` (new).

Usage: `build/release.sh [--dry-run] X.Y.Z`. Steps, stopping at the first failure:

1. Version matches `^[0-9]+\.[0-9]+\.[0-9]+$`. Branch is `main`, the tree is clean,
   `git fetch origin` and `HEAD` equals `origin/main`. Tag `vX.Y.Z` exists neither
   locally nor on origin. `gh auth status` succeeds.
2. `tests/run.sh`.
3. `MYPHP_VERSION=X.Y.Z build/make-myphp.sh`; `build/myphp version` must start with
   `myphp X.Y.Z`.
4. `build/release/myphp-linux-x86_64` = copy of `build/myphp`;
   `(cd build/release && sha256sum myphp-linux-x86_64 > myphp-linux-x86_64.sha256)`.
5. Smoke test: `MYPHP_DOWNLOAD_URL=file://$PWD/build/release/myphp-linux-x86_64
   MYPHP_INSTALL_DIR=$(mktemp -d) sh install.sh`.
6. With `--dry-run`: print the tag and the two asset paths with sizes, and stop here.
7. `git tag vX.Y.Z && git push origin vX.Y.Z`, then
   `gh release create vX.Y.Z build/release/myphp-linux-x86_64 build/release/myphp-linux-x86_64.sha256 --repo crabphp/myphp --title "myphp X.Y.Z" --generate-notes`.
8. Print the release URL and the install line.

Test here: `build/release.sh --dry-run 0.1.0` on a clean, pushed `main`: exit 0, both
assets exist, no tag is created (`git tag -l v0.1.0` is empty). With a dirty tree, a
bad version (`1.0`) or an existing tag (make a local tag in a throwaway clone): exit 1
with a clear message before anything is built.

Accept: only `--dry-run` is ever run by the implementer. The real run is user step 3.

### C11. Docs, README, remove `demo/`

Files: `docs/getting-started.md`, `docs/cli.md`, `docs/components.md`,
`docs/layout.md`, `docs/desktop.md` (new); `README.md`, `docs/handover.md`; delete
`demo/desktop` and `demo/myphp-demo`.

American spelling, short pages, every command and snippet actually run.

- `docs/getting-started.md`: install (the `curl` line; needs x86-64 Linux, glibc
  2.17+, X11 or XWayland, OpenGL; no PHP), `myphp init hello`, the files it creates,
  `myphp run`, the counter `app.php` explained in ten lines (state, `$render`, `App`),
  `myphp build` and what the output needs to run, optional `composer install` for
  autocomplete, `myphp demo`. Links to the other pages.
- `docs/cli.md`: one section per command (`init`, `run`, `build`, `demo`, `version`,
  `help`), exit codes, environment (`PHPUI_SCREENSHOT`, `PHPUI_DESKTOP`,
  `PHPUI_RENDERER`, `MYPHP_INSTALL_DIR`, `MYPHP_VERSION`), what `build` packs and
  skips (`vendor/`, `build/`, dotfiles), assets and `PhpUi\realFile()`, limits (Linux
  x86-64 only, no hot reload, Composer packages are not packed).
- `docs/components.md`: the components in `src/components.php` and `src/charts.php`,
  one signature and one line each, taken from the source.
- `docs/layout.md`: `div/row/col`, sizes, gap, padding, grow, align/justify, `hover`,
  `when`, ids, `paragraph()`; the limits from the README's "Not yet".
- `docs/desktop.md`: move the README's "Desktop (Linux)" section here unchanged in
  content (API list, fallbacks table, limits, `PHPUI_DESKTOP`).
- `README.md`: title `MyPHP`, the three-command quick start, the first code sample,
  a docs list, "How it works" with the file table (add `bin/myphp`, `cli/`), a short
  "Develop" section (`php84`, `tests/run.sh`, `build/make-myphp.sh`,
  `build/release.sh`, the static-php-cli recipe). Remove "Try the demo" and the
  moved desktop text.
- `docs/handover.md`: replace `demo/` and `PHPUI_EXAMPLE=desktop build/dashboard` in
  the manual checklist with `myphp demo desktop`; add the CLI to "What exists" and
  facts 4-6 of this plan to "Traps and limits".
- `git rm -r demo`.

Tests: `tests/run.sh` passes. `grep -rn 'demo/' README.md docs/ --exclude=plan*.md --exclude=next.md`
finds nothing. Each fenced `sh` command in `docs/getting-started.md` and `docs/cli.md`
was run here against `build/myphp` (the `curl` line through
`MYPHP_DOWNLOAD_URL=file://...`). British spellings: `grep -rniE 'colour|behaviour|centre|initialis' docs/*.md README.md` finds nothing in the new pages.

Accept: a reader can go from nothing to a built app using `docs/getting-started.md`
alone; `git ls-files demo` is empty; the working tree has no tracked binary.

---

## Final check (after C11)

    tests/run.sh
    build/make-myphp.sh
    T=$(mktemp -d) && cd "$T"
    MYPHP_DOWNLOAD_URL=file://$OLDPWD/build/myphp MYPHP_INSTALL_DIR=$T/bin sh $OLDPWD/install.sh
    export PATH=$T/bin:$PATH DISPLAY=:99
    myphp init hello && cd hello
    PHPUI_SCREENSHOT=$T/run.png myphp run
    myphp build
    PHPUI_SCREENSHOT=$T/built.png build/hello
    cd - && build/release.sh --dry-run 0.1.0

All exit 0 and both PNGs exist. Then tell the user that user steps 1-5 are open.

## Not in this plan

Hot reload / `myphp dev`; packing Composer dependencies; app icon, name and `.desktop`
file; `.deb`/`.rpm`/AppImage; self-update; `myphp doctor`; macOS, Windows, arm64;
CI-built releases; renaming the `PhpUi` namespace or `PHPUI_*` variables; history
rewrite to drop the old demo binary.
