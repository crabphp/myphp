dnl phpsdl: SDL3 window and input, SDL_GPU drawing, canvas paths, textures, buffers and audio for myphp.

PHP_ARG_ENABLE([phpsdl], [whether to enable phpsdl],
  [AS_HELP_STRING([--enable-phpsdl], [Enable phpsdl (needs SDL3)])])

PHP_ARG_WITH([sdl3-dir], [SDL3 installation],
  [AS_HELP_STRING([[--with-sdl3-dir[=DIR]]], [SDL3 install prefix (default: pkg-config sdl3)])], [no], [no])

if test "$PHP_PHPSDL" != "no"; then
  PKG_PROG_PKG_CONFIG
  if test "$PHP_SDL3_DIR" != "no"; then
    export PKG_CONFIG_PATH="$PHP_SDL3_DIR/lib64/pkgconfig:$PHP_SDL3_DIR/lib/pkgconfig:$PKG_CONFIG_PATH"
  fi
  PKG_CHECK_MODULES_STATIC([SDL3], [sdl3 >= 3.2.0])
  PKG_CHECK_MODULES_STATIC([HARFBUZZ], [harfbuzz-raster >= 13.0.0])
  dnl SDL3 and HarfBuzz are linked statically: take their private libraries too.
  SDL3_STATIC_LIBS=`$PKG_CONFIG --static --libs sdl3 harfbuzz-raster harfbuzz`
  PHP_EVAL_INCLINE($SDL3_CFLAGS)
  PHP_EVAL_INCLINE($HARFBUZZ_CFLAGS)
  PHP_EVAL_LIBLINE($SDL3_STATIC_LIBS, PHPSDL_SHARED_LIBADD)

  PHPSDL_SOURCES="phpsdl.c \
    render.c \
    path.c \
    raster.c \
    gl/phpglfw_math.c \
    gl/phpglfw_buffer.c \
    gl/phpglfw_texture.c \
    gl/phpglfw_audio.c"

  dnl stb is implemented once, in gl/phpglfw_texture.c; the generated arginfo has
  dnl class names that trip -Wunicode.
  PHPSDL_CFLAGS="-Wno-unicode -Wno-unknown-warning-option"

  PHP_NEW_EXTENSION(phpsdl, $PHPSDL_SOURCES, $ext_shared,, $PHPSDL_CFLAGS)
  PHP_ADD_BUILD_DIR($ext_builddir/gl)
  PHP_ADD_INCLUDE([$ext_srcdir])
  PHP_ADD_INCLUDE([$ext_srcdir/gl])
  PHP_ADD_INCLUDE([$ext_srcdir/vendor/cvector])
  PHP_ADD_INCLUDE([$ext_srcdir/vendor/stb])
  PHP_ADD_INCLUDE([$ext_srcdir/vendor/nanosvg/src])
  PHP_ADD_INCLUDE([$ext_srcdir/vendor/miniaudio])
  PHP_ADD_LIBRARY(m,, PHPSDL_SHARED_LIBADD)
  PHP_SUBST(PHPSDL_SHARED_LIBADD)
fi
