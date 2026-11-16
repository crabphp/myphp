<?php

/** @generate-class-entries */

namespace PhpSdl {
    /** The application window with its OpenGL 3.3 context. One per process. */
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
