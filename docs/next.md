# Next: the myphp CLI and app workflow

Goal: like MyGo (~/.cache/mygo: docs/getting-started.md, docs/cli.md, docs/distribution.md,
cmd/), a user installs one binary, creates an app, runs it, and ships it. No PHP install needed.

    curl -fsSL https://raw.githubusercontent.com/crabphp/myphp/main/install.sh | sh
    myphp init hello && cd hello && myphp run
    myphp build          # one executable to send to people

Agreed with the user:

- **Same repo.** CLI source in `bin/myphp` (+ whatever it needs under src/ or cli/).
- **`myphp` binary** = static PHP 8.4 with glfw (static-php-cli build, ~/.cache/spc,
  `buildroot/bin/php` and `micro.sfx`) + the CLI + toolkit code + assets. Built by a
  script; attached to GitHub releases (crabphp/myphp), **not committed to git**.
  Prefer embedding micro.sfx in (or downloading it next to) the CLI so `build` works offline.
- **`myphp init <name>`**: minimal folder: `app.php`, `composer.json`, `.gitignore`
  (and a short README). App code must run with `myphp run` without `vendor/`.
- **`myphp run [file]`**: runs the app with the toolkit built in. Hot reload is a
  nice-to-have, not required now.
- **`myphp build [file]`**: one executable (micro.sfx + phar of app + toolkit + assets),
  like build/make-binary.sh does for the dashboard. Fonts/assets from phar via realFile().
- **Composer only for editor autocomplete**: package name `crabphp/myphp` (toolkit
  sources, autoload files/classmap) plus GLFW stubs (`phpgl/ide-stubs`, verify the real
  package name) as require-dev. Optional: skipping composer must still work.
- **`install.sh`**: downloads the latest release binary for linux x86-64 into
  ~/.local/bin (prints PATH hint). Other OS later.
- **Docs**: docs/getting-started.md (install, init, run, build), then a page per topic
  (components, layout, desktop APIs), README points at them. American spelling, short.
- Also: a version command, `myphp help`. The prebuilt `demo/` folder can go once
  releases exist (decide in the plan).

Machine facts: Fedora 44, no desktop session, Xvfb :99, `php84` has glfw, tests via
`tests/run.sh`. Releases need `gh` (logged in as bangnokia, owner of crabphp org).
