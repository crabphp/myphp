# phpsdl

myphp's native layer, compiled into the myphp runtime. It replaces PHP-GLFW, and
draws with SDL_GPU: Vulkan, Metal or Direct3D 12. There is no OpenGL.

- **SDL3** (`phpsdl.c`): window, mouse, keyboard, clipboard, cursors, and input
  methods. Text being composed arrives as `preedit` events, and the window tells the
  input method where the caret is, so candidate lists open beside it.
- **Renderer and text** (`render.c`): the toolkit's drawing. `PhpSdl\Renderer` queues
  boxes (rounded corners, borders, gradients, shadows from signed distances), text,
  icons and images in order and draws them in as few calls as textures allow.
  `PhpSdl\Text` shapes text with HarfBuzz (ligatures, marks, complex scripts), picks a
  fallback font per character (variable fallbacks follow the requested weight) and
  rasterizes glyphs with HarfBuzz into an atlas shared with icons, which are SVG masks
  tinted when drawn. Frames start and end here too, on screen or off (screenshots).
- **Canvas paths** (`path.c`, `raster.c`): what `PhpUi\Canvas` (the HTML canvas 2D API)
  draws. `PhpSdl\Path` is a Path2D (lines, Béziers, arcs, arcTo, ellipses, rounded
  rectangles, text outlines). `Renderer::path()` fills or strokes one: a lone
  rectangle, rounded rectangle or circle in a color is an exact box; anything else is
  rasterized on the CPU (non-zero with HarfBuzz's exact-area rasterizer, even-odd with
  16 sub-scanlines), clipped (rectangles and any path), painted (linear, radial and
  conic gradients with any number of stops, patterns), shadowed (Gaussian blur), and
  kept as a texture while it is drawn. Strokes: miter, round and bevel joins, butt,
  round and square caps, dashes; they follow the transform.
- **SDL_GPU** (`render.c`, `shaders/`): everything draws with SDL_GPU, on Vulkan, Metal
  or Direct3D 12. `shaders/ui.hlsl` is compiled to SPIR-V, DXIL and MSL by
  `shaders/build.sh` (DXC and SPIRV-Cross) into `shaders/ui_shaders.h`, which is
  committed.
- **Textures, buffers, math and audio** (`gl/`, `vendor/`): PHP-GLFW's modules,
  generated without GLFW, OpenGL or NanoVG by `tools/generate.sh` (MIT, see
  `gl/LICENSE-php-glfw`). The PHP API is the same: `GL\Texture\Texture2D`,
  `GL\Buffer\FloatBuffer`, `GL\Math\Vec2`, `GL\Audio\Engine`. There are no `gl*()`
  functions and no `GL\VectorGraphics`: canvases draw with `PhpUi\Canvas`.

Keys, modifiers and mouse buttons use GLFW's numbering, and the `GLFW_KEY_*`,
`GLFW_MOD_*` and `GLFW_MOUSE_BUTTON_*` constants exist, so apps written for
PHP-GLFW keep working.

## PHP API

```php
$window = new PhpSdl\Window('Title', 800, 600);   // claims the GPU device (made by the first window)
$window->size(); $window->pixelSize(); $window->vsync(false);
$window->begin([1, 1, 1, 1]);                      // a frame, cleared to white...
$window->swap();                                   // ...shown
$window->beginCapture(800, 600, [1, 1, 1, 1]);     // or drawn off screen...
$window->endCapture('frame.png');                  // ...and saved
PhpSdl\Sdl::backend();                             // "vulkan", "metal" or "direct3d12"
$window->startTextInput($x, $y, $w, $h);           // text and composition at this caret
$window->stopTextInput();
PhpSdl\Sdl::events(0.5);  // [['type' => 'key', 'key' => GLFW_KEY_A, 'mods' => 0], ...]
PhpSdl\Sdl::clipboard(); PhpSdl\Sdl::setClipboard('text'); PhpSdl\Sdl::time();

PhpSdl\Text::addFont('regular', 'Geist-Regular.ttf');
PhpSdl\Text::addFallback('/usr/share/fonts/.../NotoSansCJK.ttc');
PhpSdl\Text::width('Tiếng Việt', 'regular', 14);        // shaped width
PhpSdl\Text::carets('office', 'regular', 14);           // x of each character boundary
$r = new PhpSdl\Renderer();
$r->begin($width, $height, $pixelRatio);
$r->box(10, 10, 200, 80, [8, 8, 8, 8], [1, 1, 1, 1], null, [0.9, 0.9, 0.9, 1], 1);
$r->text('Hello 日本語', 'regular', 14, 20, 50, [0, 0, 0, 1]);
$p = new PhpSdl\Path();
$p->arc(60, 60, 40, 0, M_PI);
$r->path($p, ['stroke' => [4, 1, 1], 'paint' => ['linear', 20, 0, 100, 0, [[0, [1, 0, 0, 1]], [1, [0, 0, 1, 1]]]]]);
$r->flush();
```

Event types: `quit`, `pointer` (x, y), `button` (button, pressed), `scroll` (dx, dy),
`text` (text), `preedit` (text, caret, length), `key` (key, mods, repeat), `drop`
(path), `refresh`, `focus` (focused). `src/SdlWindow.php` maps them to the toolkit.

## Building

The runtime recipes build it with static-php-cli from this folder. To build it as a
shared extension for development:

```sh
# HarfBuzz 13+, static, with its rasterizer
cmake -S harfbuzz-x.y.z -B hb -DBUILD_SHARED_LIBS=OFF -DCMAKE_POSITION_INDEPENDENT_CODE=ON -DHB_BUILD_RASTER=ON \
  -DHB_BUILD_SUBSET=OFF -DCMAKE_INSTALL_PREFIX=$HOME/.cache/phpsdl/sdl3 && cmake --build hb && cmake --install hb
# SDL3, static, with SDL_GPU and IME (IBus and Fcitx need the D-Bus and IBus headers)
cmake -S SDL3-x.y.z -B build -DSDL_SHARED=OFF -DSDL_STATIC=ON -DCMAKE_POSITION_INDEPENDENT_CODE=ON \
  -DSDL_GPU=ON -DSDL_OPENGL=OFF -DSDL_OPENGLES=OFF -DSDL_AUDIO=OFF -DCMAKE_INSTALL_PREFIX=$HOME/.cache/phpsdl/sdl3 && cmake --build build && cmake --install build
phpize && ./configure --enable-phpsdl --with-sdl3-dir=$HOME/.cache/phpsdl/sdl3 && make
php -d extension=modules/phpsdl.so ...   # without the glfw extension loaded
# No GPU (a VM, CI)? Mesa's lavapipe is a software Vulkan: mesa-vulkan-drivers
```

After changing a shader in `shaders/`, run `shaders/build.sh`. After changing
`phpsdl.stub.php`, regenerate `phpsdl_arginfo.h` with PHP's `build/gen_stub.php`.
