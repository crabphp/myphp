<?php

// Editor stubs for phpsdl, myphp's runtime extension (ext/phpsdl). Its OpenGL, NanoVG (canvases),
// texture, buffer and audio API is PHP-GLFW's, covered by phpgl/ide-stubs; GLFW_KEY_* constants too.
namespace PhpSdl {
    /** A window with the OpenGL 3.3 context, shared by all windows; the newest draws. */
    final class Window
    {
        public function __construct(string $title, int $width, int $height) {}
        /** @return array{int, int} Size in window points. */
        public function size(): array {}
        /** @return array{int, int} Size in pixels. */
        public function pixelSize(): array {}
        public function setSize(int $width, int $height): void {}
        public function setTitle(string $title): void {}
        public function swap(): void {}
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
        public function image(string $path, float $x, float $y, float $width, float $height, array $box, array $radii, float $opacity = 1, ?array $clip = null): void {}
        public function flush(): void {}
        /** @return array{int, int} */
        public static function imageSize(string $path): array {}
    }

    final class Sdl
    {
        /** Wait up to $timeout seconds, then return every pending event as an array with a 'type'. */
        public static function events(float $timeout): array {}
        public static function time(): float {}
        public static function clipboard(): string {}
        public static function setClipboard(string $text): void {}
        public static function version(): string {}
    }
}
