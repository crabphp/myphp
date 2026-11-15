#!/bin/sh
# Publishing is an explicit maintainer action. --dry-run never creates a tag/release.
set -eu
fail() { echo "myphp: $*" >&2; exit 1; }
DRY=0
if [ "${1:-}" = --dry-run ]; then DRY=1; shift; fi
[ "$#" = 1 ] || fail 'usage: build/release.sh [--dry-run] X.Y.Z'
VERSION=$1
printf '%s\n' "$VERSION" | grep -Eq '^[0-9]+\.[0-9]+\.[0-9]+$' || fail 'version must be X.Y.Z'
ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
cd "$ROOT"
[ "$(git branch --show-current)" = main ] || fail 'release needs branch main'
[ -z "$(git status --porcelain)" ] || fail 'release needs a clean working tree'
git fetch origin
[ "$(git rev-parse HEAD)" = "$(git rev-parse origin/main)" ] || fail 'HEAD must equal origin/main'
TAG=v$VERSION
if git show-ref --verify --quiet "refs/tags/$TAG"; then fail "tag $TAG already exists locally"; fi
REMOTE=$(git ls-remote --tags origin "refs/tags/$TAG")
[ -z "$REMOTE" ] || fail "tag $TAG already exists on origin"
gh auth status >/dev/null 2>&1 || fail 'sign in with gh auth login first'
tests/run.sh
MYPHP_VERSION=$VERSION MYPHP_BUILD_DIR=$ROOT/build build/make-myphp.sh
ACTUAL=$(build/myphp version)
case $ACTUAL in
    "myphp $VERSION (PHP "*) ;;
    *) fail "unexpected built version: $ACTUAL" ;;
esac
mkdir -p build/release
cp build/myphp build/release/myphp-linux-x86_64
(cd build/release && sha256sum myphp-linux-x86_64 > myphp-linux-x86_64.sha256)
SMOKE=$(mktemp -d)
trap 'rm -rf "$SMOKE"' 0
trap 'exit 1' HUP INT TERM
MYPHP_DOWNLOAD_URL=file://$ROOT/build/release/myphp-linux-x86_64 MYPHP_INSTALL_DIR=$SMOKE sh install.sh
if [ "$DRY" = 1 ]; then
    echo "Dry run: $TAG (no tag or release created)"
    ls -lh build/release/myphp-linux-x86_64 build/release/myphp-linux-x86_64.sha256
    exit 0
fi
git tag "$TAG"
git push origin "$TAG"
gh release create "$TAG" build/release/myphp-linux-x86_64 build/release/myphp-linux-x86_64.sha256 \
    --repo crabphp/myphp --verify-tag --title "myphp $VERSION" --generate-notes
echo "https://github.com/crabphp/myphp/releases/tag/$TAG"
echo 'curl -fsSL https://raw.githubusercontent.com/crabphp/myphp/main/install.sh | sh'
