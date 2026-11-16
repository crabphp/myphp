#!/bin/sh
# Regenerate phpsdl's OpenGL, NanoVG, texture, buffer, math and audio bindings
# from PHP-GLFW (MIT, Mario Döring) without GLFW, and copy them with the C
# libraries they use into ext/phpsdl/gl and ext/phpsdl/vendor.
#   tools/generate.sh [php-glfw checkout]
set -eu
EXT=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
PHP=${PHP:-$(command -v php84 || command -v php)}
REV=6bb4e64
WORK=$(mktemp -d)
trap 'rm -rf "$WORK"' 0
if [ -n "${1:-}" ]; then cp -r "$1" "$WORK/pg"; else git clone -q https://github.com/mario-deluna/php-glfw "$WORK/pg"; fi
git -C "$WORK/pg" checkout -q "$REV" 2>/dev/null || true
cd "$WORK/pg/generator"
"$PHP" "$EXT/tools/patch-generator.php" .
composer update --no-interaction -q
"$PHP" build > "$WORK/generate.log" 2>&1 || { tail -20 "$WORK/generate.log"; exit 1; }
cd "$WORK/pg"
"$PHP" "$EXT/tools/strip-stub.php" phpglfw.stub.php
"$PHP" "${GEN_STUB:-$HOME/.cache/spc/source/php-src/build/gen_stub.php}" phpglfw.stub.php
rm -rf "$EXT/gl" "$EXT/vendor"
mkdir -p "$EXT/gl" "$EXT/vendor"
for f in arginfo.h audio.c audio.h buffer.c buffer.h constants.c constants.h functions.c functions.h math.c math.h \
    svgparser.c svgparser.h texture.c texture.h vg.c vg.h; do cp "phpglfw_$f" "$EXT/gl/"; done
cp phpglfw.stub.php linmath.h "$EXT/gl/"
cp LICENSE "$EXT/gl/LICENSE-php-glfw"
for d in glad nanovg nanosvg stb miniaudio cvector; do cp -r "vendor/$d" "$EXT/vendor/"; done
find "$EXT/vendor" \( -name '*.o' -o -name '*.lo' -o -name '*.dep' \) -delete
echo "Generated from php-glfw $REV into $EXT/gl and $EXT/vendor"
