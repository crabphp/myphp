# phpsdl

myphp's native layer, compiled into the myphp runtime. It replaces PHP-GLFW.

- **SDL3** (`phpsdl.c`): window, mouse, keyboard, clipboard, cursors, and input
  methods. Text being composed arrives as `preedit` events, and the window tells the
  input method where the caret is, so candidate lists open beside it.
- **OpenGL 3.3, NanoVG, SVG, textures, buffers, math and audio** (`gl/`, `vendor/`):
  PHP-GLFW's modules, generated without GLFW by `tools/generate.sh` (MIT, see
  `gl/LICENSE-php-glfw`). The PHP API is the same: `GL\VectorGraphics\VGContext`,
  `GL\Texture\Texture2D`, `GL\Buffer\FloatBuffer`, `GL\Audio\Engine`, `gl*()`.

Keys, modifiers and mouse buttons use GLFW's numbering, and the `GLFW_KEY_*`,
`GLFW_MOD_*` and `GLFW_MOUSE_BUTTON_*` constants exist, so apps written for
PHP-GLFW keep working.

## PHP API

```php
$window = new PhpSdl\Window('Title', 800, 600);   // with an OpenGL 3.3 core context
$window->size(); $window->pixelSize(); $window->swap(); $window->vsync(false);
$window->startTextInput($x, $y, $w, $h);           // text and composition at this caret
$window->stopTextInput();
PhpSdl\Sdl::events(0.5);  // [['type' => 'key', 'key' => GLFW_KEY_A, 'mods' => 0], ...]
PhpSdl\Sdl::clipboard(); PhpSdl\Sdl::setClipboard('text'); PhpSdl\Sdl::time();
```

Event types: `quit`, `pointer` (x, y), `button` (button, pressed), `scroll` (dx, dy),
`text` (text), `preedit` (text, caret, length), `key` (key, mods, repeat), `drop`
(path), `refresh`, `focus` (focused). `src/SdlWindow.php` maps them to the toolkit.

## Building

The runtime recipes build it with static-php-cli from this folder. To build it as a
shared extension for development:

```sh
# SDL3, static, with IME (IBus and Fcitx need the D-Bus and IBus headers)
cmake -S SDL3-x.y.z -B build -DSDL_SHARED=OFF -DSDL_STATIC=ON -DCMAKE_POSITION_INDEPENDENT_CODE=ON \
  -DSDL_AUDIO=OFF -DCMAKE_INSTALL_PREFIX=$HOME/.cache/phpsdl/sdl3 && cmake --build build && cmake --install build
phpize && ./configure --enable-phpsdl --with-sdl3-dir=$HOME/.cache/phpsdl/sdl3 && make
php -d extension=modules/phpsdl.so ...   # without the glfw extension loaded
```

After changing `phpsdl.stub.php`, regenerate `phpsdl_arginfo.h` with PHP's
`build/gen_stub.php`.
