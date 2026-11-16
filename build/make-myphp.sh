#!/bin/sh
# Build the standalone CLIs: myphp from the core runtime and, when a full runtime is
# given, myphp-full. MYPHP_BUILD_DIR keeps test artifacts outside the repo.
#   make-myphp.sh [core micro.sfx] [full micro.sfx]
# With MYPHP_FLAVOR=core or full, builds only that CLI, from the first argument.
set -eu
ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
SFX=${1:-$HOME/.cache/spc/runtimes/core/micro.sfx}
FULL=${2:-${MYPHP_FULL_SFX:-$HOME/.cache/spc/runtimes/full/micro.sfx}}
PHP=${PHP:-$(command -v php84 || command -v php)}
if [ ! -f "$SFX" ]; then
    echo "myphp: micro.sfx not found: $SFX (build it with static-php-cli)" >&2
    exit 1
fi
if [ -n "${MYPHP_FLAVOR:-}" ]; then
    exec "$PHP" -d phar.readonly=0 "$ROOT/build/make-myphp.php" "$SFX"
fi
MYPHP_FLAVOR=core "$PHP" -d phar.readonly=0 "$ROOT/build/make-myphp.php" "$SFX"
if [ -f "$FULL" ]; then
    MYPHP_FLAVOR=full "$PHP" -d phar.readonly=0 "$ROOT/build/make-myphp.php" "$FULL"
fi
