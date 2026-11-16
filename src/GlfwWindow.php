<?php

declare(strict_types=1);

namespace PhpUi;

/** A window on PHP-GLFW: committed text only, no input-method composition. */
final class GlfwWindow extends Window
{
    public readonly \GLFWwindow $handle;
    private array $cursors = [];

    public function __construct(string $title, int $width, int $height)
    {
        if (!glfwInit()) throw new \RuntimeException('GLFW could not start');
        glfwWindowHint(GLFW_CONTEXT_VERSION_MAJOR, 3);
        glfwWindowHint(GLFW_CONTEXT_VERSION_MINOR, 3);
        glfwWindowHint(GLFW_OPENGL_PROFILE, GLFW_OPENGL_CORE_PROFILE);
        glfwWindowHint(GLFW_OPENGL_FORWARD_COMPAT, GL_TRUE);
        glfwWindowHint(GLFW_SCALE_TO_MONITOR, GL_TRUE);
        glfwWindowHint(GLFW_STENCIL_BITS, 8);
        $window = glfwCreateWindow($width, $height, $title);
        if (!$window) {
            glfwTerminate();
            throw new \RuntimeException('GLFW could not create the window');
        }
        $this->handle = $window;
        glfwMakeContextCurrent($window);
        glfwSwapInterval(1);
        $this->cursors = [
            'default' => glfwCreateStandardCursor(GLFW_ARROW_CURSOR),
            'pointer' => glfwCreateStandardCursor(GLFW_HAND_CURSOR),
            'text' => glfwCreateStandardCursor(GLFW_IBEAM_CURSOR),
        ];
        glfwSetCursorPosCallback($window, fn (float $x, float $y) => $this->emit('pointer', $x, $y));
        glfwSetMouseButtonCallback($window, fn (int $button, int $action) => $this->emit('button', $button, $action === GLFW_PRESS));
        glfwSetScrollCallback($window, fn (float $dx, float $dy) => $this->emit('scroll', $dy));
        // PHP-GLFW 2.2 passes (count, paths), without the native window argument.
        glfwSetDropCallback($window, fn (int $count, array $paths) => $this->emit('drop', $paths));
        glfwSetCharCallback($window, fn (int $codepoint) => $this->emit('text', mb_chr($codepoint, 'UTF-8')));
        glfwSetKeyCallback($window, function (int $key, int $scancode, int $action, int $mods) {
            if ($action !== GLFW_RELEASE) $this->emit('key', $key, $mods);
        });
        glfwSetWindowRefreshCallback($window, fn () => $this->emit('refresh'));
    }

    public function size(): array
    {
        glfwGetWindowSize($this->handle, $w, $h);
        return [$w, $h];
    }

    public function framebufferSize(): array
    {
        glfwGetFramebufferSize($this->handle, $w, $h);
        return [$w, $h];
    }

    public function setSize(int $width, int $height): void { glfwSetWindowSize($this->handle, $width, $height); }
    public function setTitle(string $title): void { glfwSetWindowTitle($this->handle, $title); }
    public function shouldClose(): bool { return (bool) glfwWindowShouldClose($this->handle); }
    public function waitEvents(float $timeout): void { glfwWaitEventsTimeout($timeout); }
    public function swap(): void { glfwSwapBuffers($this->handle); }
    public function vsync(bool $on): void { glfwSwapInterval($on ? 1 : 0); }
    public function time(): float { return glfwGetTime(); }
    public function focus(): void { glfwFocusWindow($this->handle); }
    // Polled: PHP-GLFW's focus callback breaks later layout.
    public function focused(): bool { return (bool) glfwGetWindowAttrib($this->handle, GLFW_FOCUSED); }
    public function setCursor(string $cursor): void { glfwSetCursor($this->handle, $this->cursors[$cursor] ?? $this->cursors['default']); }

    public function clipboard(): string
    {
        try { return glfwGetClipboardString($this->handle) ?? ''; } catch (\Throwable) { return ''; }
    }

    public function setClipboard(string $text): void { glfwSetClipboardString($this->handle, $text); }

    // Keep GLFW's GL entry points loaded until PHP releases GPU objects: terminating
    // here makes NanoVG destructors crash at script shutdown.
    public function close(): void { glfwDestroyWindow($this->handle); }
}
