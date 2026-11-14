#!/bin/sh
# Builds build/dashboard: one executable holding PHP, the glfw extension, the
# code and the assets. It only needs X11 and an OpenGL driver on the machine.
# Needs micro.sfx from static-php-cli (see README).
# Usage: build/make-binary.sh [path/to/micro.sfx]
#
# No OPcache/JIT settings are embedded: micro crashes with opcache.enable_cli=1,
# and the dashboard runs at the same frame rate without JIT.
set -e
cd "$(dirname "$0")"
SFX=${1:-$HOME/.cache/spc/buildroot/bin/micro.sfx}
php -d phar.readonly=0 make-phar.php
cat "$SFX" dashboard.phar > dashboard
chmod +x dashboard
ls -lh dashboard
