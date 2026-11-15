#!/bin/sh
# Build the standalone CLI. MYPHP_BUILD_DIR keeps test artifacts outside the repo.
set -eu
ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
SFX=${1:-$HOME/.cache/spc/buildroot/bin/micro.sfx}
PHP=${PHP:-$(command -v php84 || command -v php)}
if [ ! -f "$SFX" ]; then
    echo "myphp: micro.sfx not found: $SFX (build it with static-php-cli)" >&2
    exit 1
fi
"$PHP" -d phar.readonly=0 "$ROOT/build/make-myphp.php" "$SFX"
ls -lh "${MYPHP_BUILD_DIR:-$ROOT/build}/myphp"
