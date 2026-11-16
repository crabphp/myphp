#!/bin/sh
# Real input methods typing into a myphp app: IBus in a private D-Bus session on Xvfb,
# keys pressed through XTest. Needs ibus with the m17n (Vietnamese), anthy (Japanese)
# and libpinyin (Chinese) engines. Not part of tests/run.sh.
#   tests/real/ime.sh [path to myphp]    (default: build/myphp)
set -u
ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/../.." && pwd)
MYPHP=${1:-$ROOT/build/myphp}
WORK=$(mktemp -d)
trap 'kill $XV 2>/dev/null; rm -rf "$WORK"' 0
export DISPLAY=:93 WAYLAND_DISPLAY=
Xvfb :93 -screen 0 1024x768x24 >/dev/null 2>&1 & XV=$!
sleep 2
cat > "$WORK/app.php" <<'PHP'
<?php
use function PhpUi\{col, state, textInput};
$s = state(['v' => '']);
$app = new PhpUi\App('IME', 400, 120, fn () => col()->p(20)->child(textInput('in', $s->v, function ($v) use ($s) {
    $s->v = $v; file_put_contents(getenv('IME_OUT'), $v);
})->w(300)));
$app->after(0.3, fn () => $app->focusElement('in'));
$app->after(9, fn () => exit(0));
$app->run();
PHP
fail=0
check() { # engine keys expected
    out=$(timeout 60 dbus-run-session -- sh -c "
      ibus-daemon --xim --panel=disable >/dev/null 2>&1 & IB=\$!
      sleep 3
      timeout 5 ibus engine '$1' >/dev/null 2>&1
      (SDL_IM_MODULE=ibus XMODIFIERS=@im=ibus IME_OUT='$WORK/out' timeout 11 '$MYPHP' run '$WORK/app.php' < /dev/null > '$WORK/log' 2>&1 &)
      sleep 3
      timeout 10 python3 '$ROOT/tests/real/keys.py' '$2'
      sleep 2
      cat '$WORK/out' 2>/dev/null; rm -f '$WORK/out'
      sleep 6; kill \$IB
    " 2>/dev/null)
    if [ "$out" = "$3" ]; then echo "ok - $1: $3"; else echo "FAIL - $1: expected [$3], got [$out]"; fail=1; fi
}
check xkb:us::eng 'hello ' 'hello '
check m17n:vi:telex 'vieetj ' 'việt '
check anthy 'nihongo \n' '日本語'
check libpinyin 'nihao \n' '你好'
exit $fail
