<?php

declare(strict_types=1);

namespace PhpUi;

/**
 * The native window under an App: events in, a GPU context to draw into. The toolkit
 * talks only to this, so the window system can change underneath (SdlWindow, on
 * phpsdl). Key codes and modifiers use the GLFW_KEY_* and GLFW_MOD_* numbering.
 */
abstract class Window
{
    /** @var array<string, \Closure> Event handlers set by the App: see Window::on(). */
    protected array $handlers = [];

    public static function open(string $title, int $width, int $height): self
    {
        return new SdlWindow($title, $width, $height);
    }

    /**
     * Events: pointer(x, y), button(button, pressed), scroll(dy), drop(paths), text(string),
     * preedit(text, caret), key(key, mods), refresh(). Coordinates are window points.
     */
    public function on(string $event, callable $fn): static
    {
        $this->handlers[$event] = $fn(...);
        return $this;
    }

    protected function emit(string $event, mixed ...$args): void
    {
        if (isset($this->handlers[$event])) ($this->handlers[$event])(...$args);
    }

    /** @return array{int, int} Size in window points. */
    abstract public function size(): array;
    /** @return array{int, int} Size in pixels. */
    abstract public function framebufferSize(): array;
    abstract public function setSize(int $width, int $height): void;
    abstract public function setTitle(string $title): void;
    abstract public function shouldClose(): bool;
    /** Wait up to $timeout seconds for events and dispatch them. */
    abstract public function waitEvents(float $timeout): void;
    abstract public function swap(): void;
    /** Wait for the display before showing each frame (on by default); off for benchmarks. */
    abstract public function vsync(bool $on): void;
    abstract public function time(): float;
    abstract public function focus(): void;
    abstract public function focused(): bool;
    /** 'default', 'pointer' or 'text'. */
    abstract public function setCursor(string $cursor): void;
    abstract public function clipboard(): string;
    abstract public function setClipboard(string $text): void;
    /**
     * Where text is being typed, in window points: input methods show the text being
     * composed and their candidate list there. Null when no text input has focus.
     */
    public function textInput(?array $rect): void {}
    abstract public function close(): void;
}
