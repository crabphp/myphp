#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
export PHP="${PHP:-$(command -v php84 || command -v php)}"
if ! "$PHP" -r 'exit(extension_loaded("glfw") ? 0 : 1);'; then
    echo "Tests need a PHP with the glfw extension (try PHP=php84). Selected: $PHP" >&2
    exit 1
fi
mode=fake
if [[ ${1:-} == fake || ${1:-} == real-portal ]]; then mode=$1; shift; fi
export PHPUI_TEST_MODE=$mode
export DISPLAY=:99
logs=$(mktemp -d /tmp/myphp-tests.XXXXXX)
xvfb=''
group=''
cleanup() {
    [[ -z $group ]] || kill -- -"$group" 2>/dev/null || true
    # Wait for the server to exit, so a run right after this one can start its own.
    [[ -z $xvfb ]] || { kill "$xvfb" 2>/dev/null; wait "$xvfb" 2>/dev/null; } || true
}
trap cleanup EXIT
if ! xdpyinfo -display "$DISPLAY" >/dev/null 2>&1; then
    env -u DISPLAY Xvfb :99 -screen 0 1600x1000x24 >"$logs/xvfb.log" 2>&1 & xvfb=$!
    for i in {1..100}; do xdpyinfo -display "$DISPLAY" >/dev/null 2>&1 && break; sleep .02; done
fi
export PHPUI_TEST_TMP=$logs
export FAKE_LOG="$logs/fake.jsonl"
export XDG_CONFIG_HOME="$logs/config" XDG_CACHE_HOME="$logs/cache" XDG_DATA_HOME="$logs/data"
export GSETTINGS_BACKEND=memory GIO_USE_VFS=local GIO_USE_VOLUME_MONITOR=unix
if (( $# == 0 )); then set -- tests/*.php examples/bench*.php; fi
export PHPUI_BENCH_RUNS=2 PHPUI_BENCH_SMALL=1
# Animations on, and no system query that would show up in fake call logs.
export PHPUI_REDUCE_MOTION=0
status=0
for file in "$@"; do
    [[ $file == tests/lib.php ]] && continue
    echo "== $file ($mode) =="
    setsid dbus-run-session -- bash -c '
        set -eo pipefail
        if [[ $PHPUI_TEST_MODE == fake && -f tests/fakes/desktop.py ]]; then
            python3 tests/fakes/desktop.py >"$PHPUI_TEST_TMP/service.log" 2>&1 &
            for i in {1..100}; do
                [[ -f "$PHPUI_TEST_TMP/ready" ]] && break
                sleep .02
            done
            test -f "$PHPUI_TEST_TMP/ready"
        elif [[ $PHPUI_TEST_MODE == real-portal ]]; then
            export GDK_BACKEND=x11 XDG_CURRENT_DESKTOP=gnome
            export XDG_CONFIG_HOME="$PHPUI_TEST_TMP/config"
            mkdir -p "$XDG_CONFIG_HOME/xdg-desktop-portal"
            printf "[preferred]\ndefault=gtk\n" >"$XDG_CONFIG_HOME/xdg-desktop-portal/portals.conf"
            /usr/libexec/xdg-desktop-portal-gtk >"$PHPUI_TEST_TMP/gtk.log" 2>&1 &
            /usr/libexec/xdg-desktop-portal >"$PHPUI_TEST_TMP/portal.log" 2>&1 &
            sleep 3
        fi
        # A pipe also avoids PHP child descriptors reopening a shared log at offset 0.
        if [[ $1 == examples/bench.php ]]; then "$PHP" "$1" 10; else "$PHP" "$1"; fi 2>&1 | tee /dev/null
    ' bash "$file" >"$logs/$(basename "$file").log" 2>&1 & group=$!
    wait "$group" || status=1
    kill -- -"$group" 2>/dev/null || true
    group=''
    rm -f "$logs/ready"
    while IFS= read -r line; do printf '%s\n' "$line"; done <"$logs/$(basename "$file").log"
done
echo "Logs: $logs"
exit "$status"
