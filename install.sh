#!/bin/sh
set -eu

fail() { echo "myphp: $*" >&2; exit 1; }
warn() { echo "myphp: warning: $*" >&2; }
case "$(uname -s)-$(uname -m)" in
    Linux-x86_64) ASSET=myphp-linux-x86_64 ;;
    Darwin-arm64) ASSET=myphp-macos-arm64 ;;
    Darwin-x86_64) ASSET=myphp-macos-x86_64 ;;
    *) fail 'only Linux x86-64 and macOS are supported for now' ;;
esac
DIR=${MYPHP_INSTALL_DIR:-$HOME/.local/bin}
VERSION=${MYPHP_VERSION:-}
# `sh -s -- --nightly` (or MYPHP_VERSION=nightly) installs the newest build from main.
if [ "${1:-}" = --nightly ]; then VERSION=nightly; fi
BASE=https://github.com/crabphp/myphp/releases
if [ "$VERSION" = nightly ]; then
    URL=$BASE/download/nightly/$ASSET
elif [ -n "$VERSION" ]; then
    URL=$BASE/download/v$VERSION/$ASSET
else
    URL=$BASE/latest/download/$ASSET
fi
URL=${MYPHP_DOWNLOAD_URL:-$URL}
if command -v curl >/dev/null 2>&1; then
    download() { curl -fsSL "$1" -o "$2"; }
elif command -v wget >/dev/null 2>&1; then
    download() { wget -qO "$2" "$1"; }
else
    fail 'install needs curl or wget'
fi
mkdir -p "$DIR" || fail "cannot create $DIR"
TEMP=$(mktemp "$DIR/.myphp.XXXXXX")
SUM=$TEMP.sha256
trap 'rm -f "$TEMP" "$SUM"' 0
trap 'exit 1' HUP INT TERM
download "$URL" "$TEMP" || fail "download failed: $URL"
if command -v sha256sum >/dev/null 2>&1; then
    sha256() { sha256sum "$1"; }
elif command -v shasum >/dev/null 2>&1; then
    sha256() { shasum -a 256 "$1"; }
else
    sha256() { return 1; }
fi
if sha256 "$TEMP" >/dev/null 2>&1; then
    if download "$URL.sha256" "$SUM" 2>/dev/null; then
        read -r EXPECTED _rest < "$SUM" || [ -n "${EXPECTED:-}" ] || fail 'empty checksum file'
        ACTUAL=$(sha256 "$TEMP")
        ACTUAL=${ACTUAL%% *}
        [ "$EXPECTED" = "$ACTUAL" ] || fail 'checksum mismatch'
    else
        warn 'checksum unavailable; installing without verification'
    fi
else
    warn 'sha256sum and shasum unavailable; installing without verification'
fi
chmod 755 "$TEMP" || fail 'cannot make download executable'
mv -f "$TEMP" "$DIR/myphp" || fail 'cannot install myphp'
"$DIR/myphp" version
case :${PATH:-}: in
    *:"$DIR":*) ;;
    *) if [ "$DIR" = "$HOME/.local/bin" ]; then
           printf 'Add to your shell profile: export PATH="$HOME/.local/bin:$PATH"\n'
       else
           printf 'Add to your shell profile: export PATH="%s:$PATH"\n' "$DIR"
       fi ;;
esac
