<?php

declare(strict_types=1);

namespace PhpUi;

use GL\Buffer\UByteBuffer;
use GL\Texture\Texture2D;

/**
 * The window and its loop: render() builds a fresh tree, Layout places it,
 * Painter draws it, and input is routed by hit-testing the last frame's boxes
 * in paint order (overlays first).
 */
final class App
{
    public readonly \GLFWwindow $window;
    private Painter $painter;
    private Layout $layout;
    private \Closure $render;
    private ?Element $tree = null;
    /** @var array<array{Element, ?array}> Elements in paint order with their clip rectangle. */
    private array $order = [];
    private float $mouseX = -1, $mouseY = -1;
    private ?Element $pressed = null;
    private ?string $focusId = null;
    private string $cursor = 'default';
    private array $cursors = [];
    private bool $dirty = true;
    private bool $stale = true;
    private ?\Closure $onKey = null;
    private static ?self $current = null;
    private array $pollers = [], $waitHints = [], $shutdownHandlers = [];
    private array $modals = [];
    private ?Element $modalTree = null;

    public function __construct(string $title, int $width, int $height, callable $render)
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
        $this->window = $window;
        self::$current = $this;
        glfwMakeContextCurrent($this->window);
        glfwSwapInterval(1);

        $this->render = $render(...);
        $this->painter = new Painter();
        $this->layout = new Layout($this->painter);
        $this->cursors = [
            'default' => glfwCreateStandardCursor(GLFW_ARROW_CURSOR),
            'pointer' => glfwCreateStandardCursor(GLFW_HAND_CURSOR),
            'text' => glfwCreateStandardCursor(GLFW_IBEAM_CURSOR),
        ];

        glfwSetCursorPosCallback($this->window, fn (float $x, float $y) => $this->pointer($x, $y));
        glfwSetMouseButtonCallback($this->window, function (int $button, int $action) {
            if ($button !== GLFW_MOUSE_BUTTON_LEFT) return;
            $action === GLFW_PRESS ? $this->press($this->mouseX, $this->mouseY) : $this->release($this->mouseX, $this->mouseY);
        });
        glfwSetScrollCallback($this->window, fn (float $dx, float $dy) => $this->wheel($dy));
        glfwSetCharCallback($this->window, fn (int $codepoint) => $this->type(mb_chr($codepoint, 'UTF-8')));
        glfwSetKeyCallback($this->window, function (int $key, int $scancode, int $action, int $mods) {
            if ($action !== GLFW_RELEASE) $this->key($key, $mods);
        });
        glfwSetWindowRefreshCallback($this->window, fn () => $this->dirty = true);
        $this->poller(Desktop::poll(...));
        $this->waitHint(Desktop::waitHint(...));
        $this->onShutdown(Desktop::shutdown(...));
    }

    /** Ask for a new frame, e.g. after state changed outside an event handler. */
    public function invalidate(): void
    {
        $this->stale = $this->dirty = true;
    }

    /** Handle keys no input took, e.g. Escape to close a dialog. */
    public function onKey(callable $fn): self
    {
        $this->onKey = $fn(...);
        return $this;
    }

    /** Queue a modal overlay; a builder keeps its colors and state fresh. */
    public function modal(Element|callable $box): void
    {
        $this->modals[] = $box instanceof Element ? $box : $box(...);
        $this->focusId = null;
        $this->pressed = null;
        $this->invalidate();
    }

    public function closeModal(): void
    {
        array_shift($this->modals);
        $this->pressed = null;
        $this->invalidate();
    }

    private function build(): Element
    {
        $tree = ($this->render)($this);
        $box = $this->modals[0] ?? null;
        $this->modalTree = $box instanceof \Closure ? $box() : $box;
        if ($this->modalTree) $tree->child($this->modalTree);
        return $tree;
    }

    public static function current(): ?App
    {
        return self::$current;
    }

    public function poller(callable $fn): self
    {
        $this->pollers[] = $fn(...);
        return $this;
    }

    public function waitHint(callable $fn): self
    {
        $this->waitHints[] = $fn(...);
        return $this;
    }

    public function onShutdown(callable $fn): self
    {
        $this->shutdownHandlers[] = $fn(...);
        return $this;
    }

    public function run(): void
    {
        try {
            while (!glfwWindowShouldClose($this->window)) {
                $wait = 0.5;
                foreach ($this->waitHints as $hint) $wait = min($wait, max(0.0001, $hint()));
                glfwWaitEventsTimeout($wait);
                $this->step();
            }
        } finally {
            try {
                // A failing shutdown handler must not skip the remaining cleanup.
                $error = null;
                foreach ($this->shutdownHandlers as $fn) {
                    try { $fn(); } catch (\Throwable $e) { $error ??= $e; }
                }
                if ($error) throw $error;
            } finally {
                // Keep GLFW's GL entry points loaded until PHP releases GPU objects.
                // Terminating here makes NanoVG destructors crash at script shutdown.
                glfwDestroyWindow($this->window);
                if (self::$current === $this) self::$current = null;
            }
        }
    }

    /** One loop turn, without waiting. */
    public function step(): void
    {
        foreach ($this->pollers as $poll) {
            if ($poll()) {
                $this->stale = $this->dirty = true;
                $this->pressed = null;
            }
        }
        if ($this->dirty || $this->focusId !== null) $this->frame();
    }

    /** Build, lay out and draw one frame to the window. */
    public function frame(): void
    {
        glfwGetWindowSize($this->window, $w, $h);
        glfwGetFramebufferSize($this->window, $fbw, $fbh);
        $this->draw($w, $h, $w > 0 ? $fbw / $w : 1, fmod(glfwGetTime(), 1.0) < 0.5);
        glfwSwapBuffers($this->window);
        $this->dirty = false;
    }

    /**
     * Render a frame of any size off screen and save it as a PNG: $scale 2
     * gives a sharp, retina-like image. Input then hit-tests this frame.
     */
    public function screenshot(string $path, int $w, int $h, float $scale = 2): void
    {
        [$pw, $ph] = [(int) ($w * $scale), (int) ($h * $scale)];
        glGenFramebuffers(1, $fbo);
        glGenRenderbuffers(2, $color, $stencil);
        glBindRenderbuffer(GL_RENDERBUFFER, $color);
        glRenderbufferStorage(GL_RENDERBUFFER, GL_RGBA8, $pw, $ph);
        glBindRenderbuffer(GL_RENDERBUFFER, $stencil);
        glRenderbufferStorage(GL_RENDERBUFFER, GL_DEPTH24_STENCIL8, $pw, $ph);
        glBindFramebuffer(GL_FRAMEBUFFER, $fbo);
        glFramebufferRenderbuffer(GL_FRAMEBUFFER, GL_COLOR_ATTACHMENT0, GL_RENDERBUFFER, $color);
        glFramebufferRenderbuffer(GL_FRAMEBUFFER, GL_DEPTH_STENCIL_ATTACHMENT, GL_RENDERBUFFER, $stencil);
        if (glCheckFramebufferStatus(GL_FRAMEBUFFER) !== GL_FRAMEBUFFER_COMPLETE) throw new \RuntimeException('Framebuffer incomplete');

        $this->painter->target = $fbo;
        $this->draw($w, $h, $scale, true);
        $this->painter->target = 0;

        $pixels = new UByteBuffer();
        glReadPixels(0, 0, $pw, $ph, GL_RGBA, GL_UNSIGNED_BYTE, $pixels);
        glBindFramebuffer(GL_FRAMEBUFFER, 0);
        glDeleteFramebuffers(1, $fbo);
        glDeleteRenderbuffers(2, $color, $stencil);

        Texture2D::fromBuffer($pw, $ph, $pixels, Texture2D::CHANNEL_RGBA)->writePNG($path);
        // OpenGL rows start at the bottom; reload flipped to get an upright image.
        Texture2D::fromDisk($path, 4, true)->writePNG($path);
    }

    private function draw(float $w, float $h, float $ratio, bool $caretOn): void
    {
        // Build twice when hover changed what the tree looks like is not needed: hover is paint-only.
        $this->tree = $this->build();
        $this->layout->compute($this->tree, $w, $h);
        $this->markState($this->tree, '0', null);
        $this->stale = false;
        $this->updateCursor();

        glViewport(0, 0, (int) ($w * $ratio), (int) ($h * $ratio));
        $bg = $this->painter->color($this->tree->get('bg', t('background')));
        glClearColor($bg->r, $bg->g, $bg->b, 1);
        glClear(GL_COLOR_BUFFER_BIT | GL_STENCIL_BUFFER_BIT);
        $this->painter->render($this->tree, $w, $h, $ratio, $caretOn);
    }

    // Input --------------------------------------------------------------

    public function pointer(float $x, float $y): void
    {
        [$this->mouseX, $this->mouseY] = [$x, $y];
        if ($this->tree) $this->markHover();
        $this->dirty = true;
    }

    public function press(float $x, float $y): void
    {
        $this->fresh();
        $this->pointer($x, $y);
        $top = $this->top($x, $y);
        // A press on the menu's anchor (its parent) counts as inside, so the anchor can toggle it.
        foreach ($this->order as [$e]) {
            if (!$this->modals && $e->onClickOutside && !$this->contains($e->parent ?? $e, $top)) {
                ($e->onClickOutside)();
                $this->stale = true;
            }
        }
        $this->pressed = $this->handler($top, fn (Element $e) => $e->onClick !== null);
        $input = $this->handler($top, fn (Element $e) => $e->kind === 'input');
        $this->focusId = $input?->id;
        $this->dirty = true;
    }

    public function release(float $x, float $y): void
    {
        $this->fresh();
        $target = $this->handler($this->top($x, $y), fn (Element $e) => $e->onClick !== null);
        if ($target && $this->pressed && $target->id === $this->pressed->id) {
            ($target->onClick)($x, $y, $target);
            $this->stale = true;
        }
        $this->pressed = null;
        $this->dirty = true;
    }

    /** Click at a point, as the mouse would, and redraw. */
    public function click(float $x, float $y): void
    {
        $this->press($x, $y);
        $this->release($x, $y);
        $this->fresh();
    }

    /** Click the middle of an element, by id. */
    public function clickId(string $id): void
    {
        $e = $this->find($id) ?? throw new \RuntimeException("No element with id $id");
        $this->click($e->x + $e->w / 2, $e->y + $e->h / 2);
    }

    /** Move the pointer onto an element, by id. */
    public function hoverId(string $id): void
    {
        $e = $this->find($id) ?? throw new \RuntimeException("No element with id $id");
        $this->pointer($e->x + $e->w / 2, $e->y + $e->h / 2);
    }

    public function wheel(float $dy): void
    {
        $this->fresh();
        $box = $this->handler($this->top($this->mouseX, $this->mouseY), fn (Element $e) => !empty($e->style['scroll']));
        if (!$box) return;
        $this->layout->scroll[$box->id] = ($this->layout->scroll[$box->id] ?? 0) - $dy * 48;
        $this->stale = $this->dirty = true;
    }

    /** Type text into the focused input. */
    public function type(string $text): void
    {
        $this->fresh();
        $input = $this->focused();
        if ($input?->onInput) {
            ($input->onInput)($input->text . $text);
            $this->stale = true;
        }
        $this->dirty = true;
    }

    public function key(int $key, int $mods = 0): void
    {
        $this->fresh();
        if ($this->modalTree) {
            $index = match ($key) {
                GLFW_KEY_ENTER, GLFW_KEY_KP_ENTER => $this->modalTree->get('modalDefault'),
                GLFW_KEY_ESCAPE => $this->modalTree->get('modalCancel'),
                default => null,
            };
            if ($index !== null) $this->clickId('message-button-' . $index);
            return;
        }
        $input = $this->focused();
        $used = false;
        if ($input) {
            if (($mods & GLFW_MOD_CONTROL) && in_array($key, [GLFW_KEY_V, GLFW_KEY_C, GLFW_KEY_X], true)) {
                if ($key === GLFW_KEY_V) $this->type(preg_replace('/\r\n|[\r\n]/', ' ', Desktop::clipboard()));
                else {
                    Desktop::setClipboard($input->text);
                    if ($key === GLFW_KEY_X && $input->onInput) ($input->onInput)('');
                }
                $used = true;
            } elseif ($key === GLFW_KEY_BACKSPACE && $input->onInput) {
                ($input->onInput)(mb_substr($input->text, 0, -1));
                $used = true;
            } elseif (in_array($key, [GLFW_KEY_ENTER, GLFW_KEY_KP_ENTER], true) && $input->onSubmit) {
                ($input->onSubmit)($input->text);
                $used = true;
            } elseif ($key === GLFW_KEY_ESCAPE) {
                $this->focusId = null;
            }
        }
        if (!$used && $this->onKey) ($this->onKey)($key, $mods);
        $this->stale = $this->dirty = true;
    }

    /** Find an element by id in the last frame, e.g. to click it in a test. */
    public function find(string $id, ?Element $from = null): ?Element
    {
        $this->fresh();
        $from ??= $this->tree;
        if (!$from) return null;
        if ($from->id === $id) return $from;
        foreach ($from->children as $c) if ($found = $this->find($id, $c)) return $found;
        return null;
    }

    // Internals -----------------------------------------------------------

    private function fresh(): void
    {
        if ($this->stale) $this->relayout();
    }

    /** Rebuild and lay out without drawing, so the next hit-test sees new state. */
    private function relayout(): void
    {
        glfwGetWindowSize($this->window, $w, $h);
        $size = $this->tree ? [$this->tree->w, $this->tree->h] : [$w, $h];
        $this->tree = $this->build();
        $this->layout->compute($this->tree, ...$size);
        $this->markState($this->tree, '0', null);
        $this->stale = false;
    }

    /** Give every element an id (its path, unless set) and a parent; then list paint order and mark hover. */
    private function markState(Element $e, string $path, ?Element $parent): void
    {
        $e->id ??= $path;
        $e->parent = $parent;
        $e->focused = $e->kind === 'input' && $e->id === $this->focusId;
        foreach ($e->children as $i => $c) $this->markState($c, "$path.$i", $e);
        if ($parent === null) $this->markHover();
    }

    private function markHover(): void
    {
        // Paint order without hover-only elements, find what is under the pointer, then show its tooltips.
        $this->order = [];
        $this->listOrder($this->tree, null, false);
        $top = $this->top($this->mouseX, $this->mouseY);
        foreach ($this->order as [$e]) $e->hovered = false;
        for ($e = $top; $e; $e = $e->parent) $e->hovered = true;
        $this->setHoverOnly($this->tree);
        $this->order = [];
        $this->listOrder($this->tree, null, true);
    }

    private function setHoverOnly(Element $e): void
    {
        if (!empty($e->style['hoverOnly'])) $e->style['hidden'] = !($e->parent?->hovered);
        foreach ($e->children as $c) $this->setHoverOnly($c);
    }

    /** Elements in the order the painter draws them: absolute ones after the rest, unclipped. */
    private function listOrder(Element $root, ?array $clip, bool $withHoverOnly): void
    {
        $queue = [[$root, null]];
        for ($i = 0; $i < count($queue); $i++) {
            $walk = function (Element $e, ?array $clip, bool $first) use (&$walk, &$queue, $withHoverOnly) {
                if (!$first && $e->isAbsolute()) { $queue[] = [$e, null]; return; }
                if (!empty($e->style['hidden']) || (!$withHoverOnly && !empty($e->style['hoverOnly']))) return;
                $this->order[] = [$e, $clip];
                if (!empty($e->style['clip'])) $clip = $this->intersect($clip, [$e->x, $e->y, $e->w, $e->h]);
                foreach ($e->children as $c) $walk($c, $clip, false);
            };
            $walk($queue[$i][0], $queue[$i][1], true);
        }
    }

    /** The topmost element under a point, in paint order. */
    private function top(float $x, float $y): ?Element
    {
        for ($i = count($this->order) - 1; $i >= 0; $i--) {
            [$e, $clip] = $this->order[$i];
            if ($this->modalTree && !$this->contains($this->modalTree, $e)) continue;
            if ($this->inside([$e->x, $e->y, $e->w, $e->h], $x, $y) && (!$clip || $this->inside($clip, $x, $y))) return $e;
        }
        return null;
    }

    /** The nearest element from $e up that matches, stopping at elements that stop clicks. */
    private function handler(?Element $e, \Closure $match): ?Element
    {
        for (; $e; $e = $e->parent) {
            if ($match($e)) return $e;
            if (!empty($e->style['stop'])) return null;
        }
        return null;
    }

    private function contains(Element $ancestor, ?Element $e): bool
    {
        for (; $e; $e = $e->parent) if ($e === $ancestor) return true;
        return false;
    }

    private function inside(array $r, float $x, float $y): bool
    {
        return $x >= $r[0] && $y >= $r[1] && $x < $r[0] + $r[2] && $y < $r[1] + $r[3];
    }

    private function intersect(?array $a, array $b): array
    {
        if (!$a) return $b;
        $x = max($a[0], $b[0]);
        $y = max($a[1], $b[1]);
        return [$x, $y, max(0, min($a[0] + $a[2], $b[0] + $b[2]) - $x), max(0, min($a[1] + $a[3], $b[1] + $b[3]) - $y)];
    }

    private function focused(): ?Element
    {
        $this->fresh();
        return $this->focusId === null ? null : $this->find($this->focusId);
    }

    private function updateCursor(): void
    {
        $over = $this->handler($this->top($this->mouseX, $this->mouseY), fn (Element $e) => isset($e->style['cursor']) || $e->kind === 'input');
        $cursor = $over ? ($over->style['cursor'] ?? 'text') : 'default';
        if ($cursor === $this->cursor) return;
        $this->cursor = $cursor;
        glfwSetCursor($this->window, $this->cursors[$cursor] ?? $this->cursors['default']);
    }
}
