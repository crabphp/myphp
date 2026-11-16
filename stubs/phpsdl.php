<?php

// Editor stubs for phpsdl, myphp's runtime extension (ext/phpsdl). Its texture, buffer, math and
// audio API is PHP-GLFW's, covered by phpgl/ide-stubs (phpsdl has no gl*() functions and no
// GL\VectorGraphics: canvases draw with PhpUi\Canvas); GLFW_KEY_* constants too.
namespace PhpSdl {
    /** A window, drawn with SDL_GPU (Vulkan, Metal or Direct3D 12). */
    final class Window
    {
        public function __construct(string $title, int $width, int $height) {}
        /** @return array{int, int} Size in window points. */
        public function size(): array {}
        /** @return array{int, int} Size in pixels. */
        public function pixelSize(): array {}
        public function setSize(int $width, int $height): void {}
        public function setTitle(string $title): void {}
        /** Start a frame in the window, cleared to $clear (RGBA, 0 to 1); false when there is nothing to draw into. */
        public function begin(array $clear): bool {}
        /** Show the frame. */
        public function swap(): void {}
        /** Draw the next frame off screen at $width×$height pixels... */
        public function beginCapture(int $width, int $height, array $clear): void {}
        /** ...and save it as a PNG. */
        public function endCapture(string $path): void {}
        public function vsync(bool $on): void {}
        public function raise(): void {}
        public function focused(): bool {}
        /** 'default', 'pointer' or 'text'. */
        public function setCursor(string $cursor): void {}
        /** Receive text, and let input methods compose at this rectangle (window points). */
        public function startTextInput(int $x, int $y, int $width, int $height): void {}
        public function stopTextInput(): void {}
        public function close(): void {}
    }

    /** Fonts, shaping and measuring: HarfBuzz, with fallback fonts per character. */
    final class Text
    {
        public static function addFont(string $name, string $path, int $index = 0): bool {}
        /** A font tried, in order, for characters the requested font lacks. */
        public static function addFallback(string $path, int $index = 0): bool {}
        public static function width(string $text, string $font, float $size, float $tracking = 0): float {}
        /** The x of every character boundary: one more than the characters. */
        public static function carets(string $text, string $font, float $size, float $tracking = 0): array {}
        /** [ascender, descender, line gap] at $size; the descender is negative. */
        public static function metrics(string $font, float $size): array {}
        /** Canvas measureText: ['width', 'left', 'right', 'ascent', 'descent'] of the ink around the pen. */
        public static function measure(string $text, string $font, float $size, float $tracking = 0): array {}
    }

    /**
     * One frame's boxes, shadows, text, icons and images, drawn in order by flush().
     * Colors are [r, g, b, a] from 0 to 1; radii [top-left, top-right, bottom-right,
     * bottom-left]; clips [x, y, width, height]. Units are logical pixels.
     */
    final class Renderer
    {
        public function begin(float $width, float $height, float $ratio): void {}
        public function box(float $x, float $y, float $width, float $height, array $radii, array $top, ?array $bottom = null, ?array $border = null, float $borderWidth = 0, ?array $clip = null): void {}
        public function shadow(float $x, float $y, float $width, float $height, array $radii, array $color, float $blur, float $spread = 0, float $offsetY = 0, ?array $clip = null): void {}
        /** A line of text with its baseline at $baseline. */
        public function text(string $text, string $font, float $size, float $x, float $baseline, array $color, float $tracking = 0, ?array $clip = null): void {}
        /** An SVG drawn as a mask in $color: its own colors are ignored. */
        public function icon(string $svg, float $x, float $y, float $size, array $color, ?array $clip = null): void {}
        /** A picture at x, y, w, h, cut to $box with its corner $radii. */
        public function image(string $path, float $x, float $y, float $width, float $height, array $box, array $radii, float $opacity = 1, ?array $clip = null, ?array $source = null): void {}
        /**
         * A canvas path (in the canvas's pixels, placed by 'transform'; 'ctm' is the current transform, which
         * strokes and paints follow), filled, or stroked with 'stroke' => [width, join, cap, miterLimit, dashes, dashOffset]
         * (joins 0 miter, 1 round, 2 bevel; caps 0 butt, 1 round, 2 square). Also: 'rule' (0 non-zero, 1 even-odd),
         * 'transform' => [a, b, c, d, e, f], 'paint' => [r, g, b, a] or ['linear', x0, y0, x1, y1, stops],
         * ['radial', x0, y0, r0, x1, y1, r1, stops], ['conic', angle, x, y, stops], ['pattern', file, repetition, matrix]
         * (stops [[offset, [r, g, b, a]], ...]), 'alpha', 'smooth', 'clip' => [x, y, w, h],
         * 'clipPaths' => [[Path, matrix, rule], ...], 'shadow' => [[r, g, b, a], blur, offsetX, offsetY].
         */
        public function path(Path $path, array $style): void {}
        public function flush(): void {}
        /** @return array{int, int} */
        public static function imageSize(string $path): array {}
    }

    /** A vector path, as canvas's Path2D. Angles in radians. */
    final class Path
    {
        public function moveTo(float $x, float $y): void {}
        public function lineTo(float $x, float $y): void {}
        public function quadraticCurveTo(float $cpx, float $cpy, float $x, float $y): void {}
        public function bezierCurveTo(float $cp1x, float $cp1y, float $cp2x, float $cp2y, float $x, float $y): void {}
        public function arc(float $x, float $y, float $radius, float $startAngle, float $endAngle, bool $counterclockwise = false): void {}
        public function arcTo(float $x1, float $y1, float $x2, float $y2, float $radius): void {}
        public function ellipse(float $x, float $y, float $radiusX, float $radiusY, float $rotation, float $startAngle, float $endAngle, bool $counterclockwise = false): void {}
        public function rect(float $x, float $y, float $width, float $height): void {}
        /** Radii: a number, or 1 to 4 of them as canvas takes them. */
        public function roundRect(float $x, float $y, float $width, float $height, float|array $radii = 0): void {}
        public function closePath(): void {}
        /** Another path, under [a, b, c, d, e, f]. */
        public function addPath(Path $path, ?array $transform = null): void {}
        /** The outlines of text (shaped, with fallback fonts) at the pen ($x, $y on the baseline). */
        public function text(string $text, string $font, float $size, float $x, float $y, float $tracking = 0): void {}
        /** Points added from now on go through [a, b, c, d, e, f], as a canvas's current transform does. */
        public function setTransform(?array $matrix = null): void {}
        public function clear(): void {}
        public function isEmpty(): bool {}
        /** Rule 0 non-zero, 1 even-odd. */
        public function contains(float $x, float $y, int $rule = 0): bool {}
        /** $stroke as Renderer::path() takes it. */
        public function containsStroke(float $x, float $y, array $stroke): bool {}
        /** [x0, y0, x1, y1], control points included. */
        public function bounds(): ?array {}
    }

    final class Sdl
    {
        /** Wait up to $timeout seconds, then return every pending event as an array with a 'type'. */
        public static function events(float $timeout): array {}
        public static function time(): float {}
        public static function clipboard(): string {}
        public static function setClipboard(string $text): void {}
        public static function version(): string {}
        /** SDL_GPU's driver: "vulkan", "metal" or "direct3d12" ("none" before the first window). */
        public static function backend(): string {}
    }
}
