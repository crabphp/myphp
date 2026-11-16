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
    private ?\Closure $onDrop = null;
    private static ?self $current = null;
    private array $pollers = [], $waitHints = [], $shutdownHandlers = [];
    private array $modals = [];
    private ?Element $modalTree = null;
    private bool $building = false;
    /** Caret, selection and scroll of each input by id, with the text they belong to. */
    private array $edits = [];
    private ?string $dragId = null;
    /** Id of the element whose onDrag follows the pointer until release. */
    private ?string $dragging = null;
    private array $timers = [];
    private int $timerId = 0;
    /** Undo and redo stacks of each input by id. */
    private array $history = [];
    /** Selection in selectable text: [id, anchor, caret] character offsets. */
    private ?array $textSelection = null;
    private bool $selectingText = false;
    private array $lastPress = [0.0, null, 0.0, 0.0];

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
            if ($button === GLFW_MOUSE_BUTTON_RIGHT && $action === GLFW_PRESS) $this->rightClick($this->mouseX, $this->mouseY);
            if ($button !== GLFW_MOUSE_BUTTON_LEFT) return;
            $action === GLFW_PRESS ? $this->press($this->mouseX, $this->mouseY) : $this->release($this->mouseX, $this->mouseY);
        });
        glfwSetScrollCallback($this->window, fn (float $dx, float $dy) => $this->wheel($dy));
        // PHP-GLFW 2.2 passes (count, paths), without the native window argument.
        glfwSetDropCallback($this->window, fn (int $count, array $paths) => $this->drop($paths));
        glfwSetCharCallback($this->window, fn (int $codepoint) => $this->type(mb_chr($codepoint, 'UTF-8')));
        glfwSetKeyCallback($this->window, function (int $key, int $scancode, int $action, int $mods) {
            if ($action !== GLFW_RELEASE) $this->key($key, $mods);
        });
        glfwSetWindowRefreshCallback($this->window, fn () => $this->dirty = true);
        // Keep drawing at display rate while something is animating.
        $this->waitHint(fn () => $this->painter->animating ? 1 / 120 : 0.5);
        $this->waitHint(fn () => $this->nextTimer());
        $this->poller(Desktop::poll(...));
        $this->waitHint(Desktop::waitHint(...));
        $this->onShutdown(Desktop::shutdown(...));
    }

    /** Ask for a new frame, e.g. after state changed outside an event handler. */
    public function invalidate(): void
    {
        // Reading state while rendering is not a change.
        if ($this->building) return;
        $this->stale = $this->dirty = true;
    }

    /** Handle keys no input took, e.g. Escape to close a dialog. */
    public function onKey(callable $fn): self
    {
        $this->onKey = $fn(...);
        return $this;
    }

    public function focus(): void
    {
        glfwFocusWindow($this->window);
    }

    public function onDrop(callable $fn): self
    {
        $this->onDrop = $fn(...);
        return $this;
    }

    public function drop(array $paths, ?float $x = null, ?float $y = null): void
    {
        $this->fresh();
        if ($x !== null || $y !== null) $this->pointer($x ?? $this->mouseX, $y ?? $this->mouseY);
        $target = $this->handler($this->top($this->mouseX, $this->mouseY), fn (Element $e) => $e->onDrop !== null);
        if ($target) ($target->onDrop)($paths);
        elseif (!$this->modals && $this->onDrop) ($this->onDrop)($paths, $this->mouseX, $this->mouseY);
        $this->invalidate();
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
        $this->building = true;
        try {
            $tree = ($this->render)($this);
            $box = $this->modals[0] ?? null;
            $this->modalTree = $box instanceof \Closure ? $box() : $box;
        } finally {
            $this->building = false;
        }
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
            $path = getenv('PHPUI_SCREENSHOT');
            if ($path !== false && $path !== '') {
                glfwGetWindowSize($this->window, $w, $h);
                $this->screenshot($path, $w, $h, 1);
                return;
            }
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
        // Timers keep a press in progress: their changes redraw without cancelling it.
        if ($this->runTimers()) $this->stale = $this->dirty = true;
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
        $this->dirty = $this->painter->animating;
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
        $this->painter->instant = true;
        try {
            $this->draw($w, $h, $scale, true);
        } finally {
            [$this->painter->target, $this->painter->instant] = [0, false];
        }

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
        if ($this->dragId !== null && ($input = $this->find($this->dragId))) {
            $this->moveCaret($input, $this->offsetAt($input, $x, $y), true);
        }
        if ($this->selectingText && $this->textSelection && ($text = $this->find($this->textSelection[0]))) {
            $this->textSelection[2] = $this->textOffsetAt($text, $x, $y);
        }
        if ($this->dragging !== null && ($target = $this->find($this->dragging)) && $target->onDrag) {
            ($target->onDrag)($x, $y, $target);
            $this->stale = true;
        }
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
        $drag = $this->handler($top, fn (Element $e) => $e->onDrag !== null);
        if ($drag) {
            $this->dragging = $drag->id;
            ($drag->onDrag)($x, $y, $drag);
            $this->stale = true;
        }
        $input = $this->handler($top, fn (Element $e) => $e->kind === 'input');
        $this->focusId = $input?->id;
        $selectable = $input ? null : $this->handler($top, fn (Element $e) => $e->kind === 'text' && $e->selectable);
        $this->textSelection = null;
        if ($selectable) {
            $offset = $this->textOffsetAt($selectable, $x, $y);
            [$time, $id, $px, $py] = $this->lastPress;
            $double = $id === $selectable->id && microtime(true) - $time < 0.4 && abs($x - $px) < 4 && abs($y - $py) < 4;
            $this->lastPress = [$double ? 0.0 : microtime(true), $selectable->id, $x, $y];
            if ($double) {
                [$from, $to] = $this->wordAt($selectable->text, $offset);
                $this->textSelection = [$selectable->id, $from, $to];
            } else {
                $this->textSelection = [$selectable->id, $offset, $offset];
                $this->selectingText = true;
            }
        }
        if ($input) {
            $offset = $this->offsetAt($input, $x, $y);
            [$time, $id, $px, $py] = $this->lastPress;
            $double = $id === $input->id && microtime(true) - $time < 0.4 && abs($x - $px) < 4 && abs($y - $py) < 4;
            $this->lastPress = [$double ? 0.0 : microtime(true), $input->id, $x, $y];
            if ($double) {
                [$from, $to] = $this->wordAt($input->text, $offset);
                $this->moveCaret($input, $from, false);
                $this->moveCaret($input, $to, true);
            } else {
                $this->moveCaret($input, $offset, false);
                $this->dragId = $input->id;
            }
        }
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
        $this->dragId = $this->dragging = null;
        $this->selectingText = false;
        $this->dirty = true;
    }

    /** A right click at a point: the nearest onRightClick handler under it. */
    public function rightClick(float $x, float $y): void
    {
        $this->fresh();
        $this->pointer($x, $y);
        $target = $this->handler($this->top($x, $y), fn (Element $e) => $e->onRightClick !== null);
        if ($target) {
            ($target->onRightClick)($x, $y, $target);
            $this->stale = true;
        }
        $this->dirty = true;
    }

    // Timers and window ---------------------------------------------------

    /** Call $fn every $seconds while the app runs; returns an id for cancel(). */
    public function every(float $seconds, callable $fn): int
    {
        $this->timers[++$this->timerId] = [max(0.001, $seconds), microtime(true) + $seconds, $fn(...), true];
        return $this->timerId;
    }

    /** Call $fn once after $seconds; returns an id for cancel(). */
    public function after(float $seconds, callable $fn): int
    {
        $this->timers[++$this->timerId] = [max(0.0, $seconds), microtime(true) + $seconds, $fn(...), false];
        return $this->timerId;
    }

    public function cancel(int $timer): void
    {
        unset($this->timers[$timer]);
    }

    /** Run timers that are due; the window redraws after any of them ran. */
    private function runTimers(): bool
    {
        $now = microtime(true);
        $ran = false;
        foreach ($this->timers as $id => [$every, $due, $fn, $repeat]) {
            if ($now < $due) continue;
            if ($repeat) $this->timers[$id][1] = max($due + $every, $now);
            else unset($this->timers[$id]);
            $fn();
            $ran = true;
        }
        return $ran;
    }

    private function nextTimer(): float
    {
        $next = 0.5;
        foreach ($this->timers as [, $due]) $next = min($next, max(0.0001, $due - microtime(true)));
        return $next;
    }

    public function title(string $title): self
    {
        glfwSetWindowTitle($this->window, $title);
        return $this;
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
        $area = $this->handler($this->top($this->mouseX, $this->mouseY), fn (Element $e) => $e->kind === 'input' && $e->get('multiline'));
        if ($area && count($this->painter->inputLines($area)) * $this->painter->lineHeight($area->size) > $this->innerH($area)) {
            $edit = $this->edit($area);
            $edit['sy'] -= $dy * 48;
            $edit['follow'] = false;
            $this->edits[$area->id] = $edit;
            $this->stale = $this->dirty = true;
            return;
        }
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
        if ($input) {
            [$from, $to] = $this->selection($input);
            $this->replace($input, $from, $to, $text);
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
        if ($key === GLFW_KEY_TAB && $this->focusNext(($mods & GLFW_MOD_SHIFT) ? -1 : 1)) {
            $this->stale = $this->dirty = true;
            return;
        }
        $input = $this->focused();
        $used = $input !== null && $this->editKey($input, $key, $mods);
        if (!$used && $this->textSelection && ($mods & (PHP_OS_FAMILY === 'Darwin' ? GLFW_MOD_SUPER : GLFW_MOD_CONTROL))) {
            $used = $this->textKey($key);
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
        $e->selection = null;
        if ($e->kind === 'text' && $this->textSelection && $this->textSelection[0] === $e->id && $this->textSelection[1] !== $this->textSelection[2]) {
            $e->selection = [min($this->textSelection[1], $this->textSelection[2]), max($this->textSelection[1], $this->textSelection[2])];
        }
        if ($e->kind === 'input') $this->syncEdit($e);
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

    /** Move focus to the next (or previous) input in the tree, selecting its text. */
    private function focusNext(int $direction): bool
    {
        $inputs = [];
        $collect = function (Element $e) use (&$collect, &$inputs) {
            if (!empty($e->style['hidden'])) return;
            if ($e->kind === 'input' && $e->id !== null) $inputs[] = $e;
            foreach ($e->children as $c) $collect($c);
        };
        $collect($this->modalTree ?? $this->tree);
        if (!$inputs) return false;
        $ids = array_map(fn (Element $e) => $e->id, $inputs);
        $at = array_search($this->focusId, $ids, true);
        $next = $inputs[$at === false ? ($direction > 0 ? 0 : count($inputs) - 1) : (($at + $direction + count($inputs)) % count($inputs))];
        $this->focusId = $next->id;
        $this->moveCaret($next, 0, false);
        $this->moveCaret($next, mb_strlen($next->text), true);
        return true;
    }

    // Selectable text -----------------------------------------------------

    /** The selected text, or '' when nothing is selected. */
    public function selectedText(): string
    {
        if (!$this->textSelection || !($e = $this->find($this->textSelection[0]))) return '';
        [, $a, $b] = $this->textSelection;
        return mb_substr($e->text, min($a, $b), abs($b - $a));
    }

    /** Ctrl/Cmd+C copies selected text; Ctrl/Cmd+A selects all of it. */
    private function textKey(int $key): bool
    {
        if ($key === GLFW_KEY_C && ($text = $this->selectedText()) !== '') {
            Desktop::setClipboard($text);
            return true;
        }
        if ($key === GLFW_KEY_A && ($e = $this->find($this->textSelection[0]))) {
            $this->textSelection = [$e->id, 0, mb_strlen($e->text)];
            return true;
        }
        return false;
    }

    /** The character offset under a point in selectable text. */
    private function textOffsetAt(Element $e, float $x, float $y): int
    {
        $font = [$e->font, $e->size, $e->get('tracking', 0)];
        if ($e->get('wrap') && $e->ranges) {
            [$pl, $pr, $pt] = [$e->get('pl', 0), $e->get('pr', 0), $e->get('pt', 0)];
            $lh = $this->painter->lineHeight($e->size);
            $i = (int) max(0, min(count($e->ranges) - 1, floor(($y - $e->y - $pt) / $lh)));
            [$start, $len] = $e->ranges[$i];
            $line = mb_substr($e->text, $start, $len);
            $left = $this->painter->lineStart($e, rtrim($line, ' '), $e->x + $pl, $e->w - $pl - $pr);
            return $start + $this->painter->offsetAt($line, $x - $left, ...$font);
        }
        $left = $this->painter->lineStart($e, $e->text, $e->x, $e->w);
        return $this->painter->offsetAt($e->text, $x - $left, ...$font);
    }

    // Text editing ---------------------------------------------------------

    /** A key for the focused input; false when the input does not use it. */
    private function editKey(Element $input, int $key, int $mods): bool
    {
        $mac = PHP_OS_FAMILY === 'Darwin';
        $primary = (bool) ($mods & ($mac ? GLFW_MOD_SUPER : GLFW_MOD_CONTROL));
        $byWord = (bool) ($mods & ($mac ? GLFW_MOD_ALT : GLFW_MOD_CONTROL));
        $shift = (bool) ($mods & GLFW_MOD_SHIFT);
        $multi = (bool) $input->get('multiline');
        $text = $input->text;
        $len = mb_strlen($text);
        $edit = $this->edit($input);
        [$from, $to] = $this->selection($input);
        $hasSelection = $from !== $to;

        switch (true) {
            case $primary && ($key === GLFW_KEY_Z || (!$mac && $key === GLFW_KEY_Y)):
                $this->undo($input, $key === GLFW_KEY_Y || $shift);
                return true;
            case $primary && $key === GLFW_KEY_A:
                $this->moveCaret($input, 0, false);
                $this->moveCaret($input, $len, true);
                return true;
            case $primary && in_array($key, [GLFW_KEY_C, GLFW_KEY_X], true):
                // With nothing selected, copy and cut take the whole value.
                if (!$hasSelection) [$from, $to] = [0, $len];
                Desktop::setClipboard(mb_substr($text, $from, $to - $from));
                if ($key === GLFW_KEY_X) $this->replace($input, $from, $to, '');
                return true;
            case $primary && $key === GLFW_KEY_V:
                $this->replace($input, $from, $to, Desktop::clipboard());
                return true;
            case $key === GLFW_KEY_LEFT || $key === GLFW_KEY_RIGHT:
                $left = $key === GLFW_KEY_LEFT;
                if ($mac && $primary) {
                    $target = $this->lineEdge($input, $edit['caret'], $left);
                } elseif ($hasSelection && !$shift) {
                    $target = $left ? $from : $to;
                } elseif ($byWord) {
                    $target = $left ? $this->wordLeft($text, $edit['caret']) : $this->wordRight($text, $edit['caret']);
                } else {
                    $target = max(0, min($len, $edit['caret'] + ($left ? -1 : 1)));
                }
                $this->moveCaret($input, $target, $shift);
                return true;
            case $key === GLFW_KEY_HOME || $key === GLFW_KEY_END:
                $start = $key === GLFW_KEY_HOME;
                $this->moveCaret($input, $primary || !$multi ? ($start ? 0 : $len) : $this->lineEdge($input, $edit['caret'], $start), $shift);
                return true;
            case $multi && ($key === GLFW_KEY_UP || $key === GLFW_KEY_DOWN):
                $this->moveLine($input, $key === GLFW_KEY_UP ? -1 : 1, $shift);
                return true;
            case $key === GLFW_KEY_BACKSPACE || $key === GLFW_KEY_DELETE:
                if (!$hasSelection) {
                    $back = $key === GLFW_KEY_BACKSPACE;
                    $c = $edit['caret'];
                    [$from, $to] = $back
                        ? [$byWord ? $this->wordLeft($text, $c) : max(0, $c - 1), $c]
                        : [$c, $byWord ? $this->wordRight($text, $c) : min($len, $c + 1)];
                }
                $this->replace($input, $from, $to, '');
                return true;
            case in_array($key, [GLFW_KEY_ENTER, GLFW_KEY_KP_ENTER], true):
                if ($multi && !$primary) {
                    $this->replace($input, $from, $to, "\n");
                    return true;
                }
                if (!$input->onSubmit) return false;
                ($input->onSubmit)($text);
                return true;
            case $key === GLFW_KEY_ESCAPE:
                $this->focusId = null;
                return false;
        }
        return false;
    }

    /** The edit state of an input; a value changed by the app puts the caret at its end. */
    private function edit(Element $e): array
    {
        $len = mb_strlen($e->text);
        $edit = $this->edits[$e->id] ?? null;
        if ($edit === null || $edit['text'] !== $e->text) {
            // The app changed the value itself: earlier edits no longer apply.
            if ($edit !== null) unset($this->history[$e->id]);
            $edit = ['text' => $e->text, 'caret' => $len, 'anchor' => $len, 'sx' => $edit['sx'] ?? 0.0,
                'sy' => $edit['sy'] ?? 0.0, 'goal' => null, 'follow' => true];
        }
        $edit['caret'] = max(0, min($len, $edit['caret']));
        $edit['anchor'] = max(0, min($len, $edit['anchor']));
        return $edit;
    }

    /** Selection as [from, to], from <= to. */
    private function selection(Element $e): array
    {
        $edit = $this->edit($e);
        return [min($edit['caret'], $edit['anchor']), max($edit['caret'], $edit['anchor'])];
    }

    private function moveCaret(Element $e, int $offset, bool $extend, bool $keepGoal = false): void
    {
        $edit = $this->edit($e);
        $edit['caret'] = max(0, min(mb_strlen($e->text), $offset));
        if (!$extend) $edit['anchor'] = $edit['caret'];
        if (!$keepGoal) $edit['goal'] = null;
        $edit['follow'] = true;
        $this->edits[$e->id] = $edit;
        $this->syncEdit($e);
        $this->dirty = true;
    }

    /** Replace characters [$from, $to) with $insert through the input's onInput. */
    private function replace(Element $e, int $from, int $to, string $insert): void
    {
        if (!$e->onInput) return;
        $insert = $e->get('multiline')
            ? str_replace(["\r\n", "\r"], "\n", $insert)
            : preg_replace('/\r\n|[\r\n]/', ' ', $insert);
        $text = mb_substr($e->text, 0, $from) . $insert . mb_substr($e->text, $to);
        if ($text === $e->text) return;
        $caret = $from + mb_strlen($insert);
        $edit = $this->edit($e);
        $this->remember($e, $edit, $from === $to && mb_strlen($insert) === 1 && $from === $edit['caret'] && !ctype_space($insert));
        ($e->onInput)($text);
        $this->edits[$e->id] = ['text' => $text, 'caret' => $caret, 'anchor' => $caret, 'goal' => null, 'follow' => true] + $edit;
        $this->stale = $this->dirty = true;
    }

    /** Save the value before an edit; typing runs of characters undo together. */
    private function remember(Element $e, array $edit, bool $typing): void
    {
        $h = $this->history[$e->id] ?? ['undo' => [], 'redo' => [], 'typing' => false, 'at' => 0.0];
        $now = microtime(true);
        if (!($typing && $h['typing'] && $now - $h['at'] < 1.0)) {
            $h['undo'][] = [$e->text, $edit['caret'], $edit['anchor']];
            if (count($h['undo']) > 200) array_shift($h['undo']);
        }
        [$h['redo'], $h['typing'], $h['at']] = [[], $typing, $now];
        $this->history[$e->id] = $h;
    }

    /** Undo (or redo) the last edit of an input. */
    private function undo(Element $e, bool $redo): void
    {
        if (!$e->onInput) return;
        $h = $this->history[$e->id] ?? null;
        [$from, $to] = $redo ? ['redo', 'undo'] : ['undo', 'redo'];
        if (!$h || !$h[$from]) return;
        $edit = $this->edit($e);
        [$text, $caret, $anchor] = array_pop($h[$from]);
        $h[$to][] = [$e->text, $edit['caret'], $edit['anchor']];
        $h['typing'] = false;
        $this->history[$e->id] = $h;
        ($e->onInput)($text);
        $this->edits[$e->id] = ['text' => $text, 'caret' => $caret, 'anchor' => $anchor, 'goal' => null, 'follow' => true] + $edit;
        $this->stale = $this->dirty = true;
    }

    /** Copy the edit state onto the element for painting, scrolling the caret into view. */
    private function syncEdit(Element $e): void
    {
        $edit = $this->edit($e);
        $multi = (bool) $e->get('multiline');
        $iw = $e->w - $e->get('pl', 0) - $e->get('pr', 0);
        if ($e->id !== $this->focusId) {
            $edit['caret'] = $edit['anchor'] = mb_strlen($e->text);
            $edit['sx'] = 0.0;
            $edit['follow'] = false;
        }
        $font = [$e->font, $e->size, $e->get('tracking', 0)];
        if (!$multi) {
            $full = $this->painter->textWidth($e->text, ...$font);
            if ($edit['follow']) {
                $cx = $this->painter->textWidth(mb_substr($e->text, 0, $edit['caret']), ...$font);
                if ($cx - $edit['sx'] > $iw - 2) $edit['sx'] = $cx - $iw + 2;
                if ($cx < $edit['sx']) $edit['sx'] = $cx;
            }
            $edit['sx'] = max(0.0, min($edit['sx'], $full - $iw + 2));
        } else {
            $lh = $this->painter->lineHeight($e->size);
            $lines = $this->painter->inputLines($e);
            $ih = $this->innerH($e);
            if ($edit['follow']) {
                $cy = Painter::lineOf($lines, $edit['caret']) * $lh;
                if ($cy < $edit['sy']) $edit['sy'] = $cy;
                if ($cy + $lh > $edit['sy'] + $ih) $edit['sy'] = $cy + $lh - $ih;
            }
            $edit['sy'] = max(0.0, min($edit['sy'], count($lines) * $lh - $ih));
        }
        $edit['follow'] = false;
        $this->edits[$e->id] = $edit;
        [$e->caret, $e->anchor, $e->scrollX, $e->scrollY] = [$edit['caret'], $edit['anchor'], $edit['sx'], $multi ? $edit['sy'] : 0.0];
    }

    private function innerH(Element $e): float
    {
        return max(1.0, $e->h - $e->get('pt', 0) - $e->get('pb', 0));
    }

    /** The character offset under a point in an input. */
    private function offsetAt(Element $e, float $x, float $y): int
    {
        $edit = $this->edit($e);
        $font = [$e->font, $e->size, $e->get('tracking', 0)];
        $local = $x - $e->x - $e->get('pl', 0);
        if (!$e->get('multiline')) return $this->painter->offsetAt($e->text, $local + $edit['sx'], ...$font);
        $lines = $this->painter->inputLines($e);
        $lh = $this->painter->lineHeight($e->size);
        $i = (int) max(0, min(count($lines) - 1, floor(($y - $e->y - $e->get('pt', 0) + $edit['sy']) / $lh)));
        [$start, $len] = $lines[$i];
        $offset = $start + $this->painter->offsetAt(mb_substr($e->text, $start, $len), $local, ...$font);
        // Stay on this visual line rather than jumping to the start of the next wrapped one.
        if ($len > 0 && $offset === $start + $len && isset($lines[$i + 1]) && $lines[$i + 1][0] === $offset) $offset--;
        return $offset;
    }

    /** Start or end of the visual line holding $offset. */
    private function lineEdge(Element $e, int $offset, bool $start): int
    {
        if (!$e->get('multiline')) return $start ? 0 : mb_strlen($e->text);
        $lines = $this->painter->inputLines($e);
        $i = Painter::lineOf($lines, $offset);
        [$from, $len] = $lines[$i];
        if ($start) return $from;
        $end = $from + $len;
        // A soft-wrapped line ends before its trailing space, so the caret stays on it.
        return isset($lines[$i + 1]) && $lines[$i + 1][0] === $end && $len > 0 ? $end - 1 : $end;
    }

    /** Move one visual line up or down, keeping the caret's horizontal position. */
    private function moveLine(Element $e, int $direction, bool $extend): void
    {
        $edit = $this->edit($e);
        $lines = $this->painter->inputLines($e);
        $font = [$e->font, $e->size, $e->get('tracking', 0)];
        $i = Painter::lineOf($lines, $edit['caret']);
        [$start] = $lines[$i];
        $goal = $edit['goal'] ?? $this->painter->textWidth(mb_substr($e->text, $start, $edit['caret'] - $start), ...$font);
        $target = $i + $direction;
        if ($target < 0) $offset = 0;
        elseif ($target >= count($lines)) $offset = mb_strlen($e->text);
        else {
            [$s, $len] = $lines[$target];
            $offset = $s + $this->painter->offsetAt(mb_substr($e->text, $s, $len), $goal, ...$font);
            if ($len > 0 && $offset === $s + $len && isset($lines[$target + 1]) && $lines[$target + 1][0] === $offset) $offset--;
        }
        $this->moveCaret($e, $offset, $extend, true);
        $this->edits[$e->id]['goal'] = $goal;
    }

    private function wordLeft(string $text, int $offset): int
    {
        $before = mb_substr($text, 0, $offset);
        return preg_match('/(?:\S+\s*|\s+)$/u', $before, $m) ? $offset - mb_strlen($m[0]) : 0;
    }

    private function wordRight(string $text, int $offset): int
    {
        $after = mb_substr($text, $offset);
        return preg_match('/^(?:\s*\S+|\s+)/u', $after, $m) ? $offset + mb_strlen($m[0]) : mb_strlen($text);
    }

    /** The word (letters, digits, underscore) around $offset, for double-click. */
    private function wordAt(string $text, int $offset): array
    {
        preg_match('/[\p{L}\p{N}_]*$/u', mb_substr($text, 0, $offset), $l);
        preg_match('/^[\p{L}\p{N}_]*/u', mb_substr($text, $offset), $r);
        $from = $offset - mb_strlen($l[0]);
        $to = $offset + mb_strlen($r[0]);
        return $from === $to ? [$offset, min(mb_strlen($text), $offset + 1)] : [$from, $to];
    }

    private function updateCursor(): void
    {
        $over = $this->handler($this->top($this->mouseX, $this->mouseY), fn (Element $e) => isset($e->style['cursor']) || $e->kind === 'input' || ($e->kind === 'text' && $e->selectable));
        $cursor = $over ? ($over->style['cursor'] ?? 'text') : 'default';
        if ($cursor === $this->cursor) return;
        $this->cursor = $cursor;
        glfwSetCursor($this->window, $this->cursors[$cursor] ?? $this->cursors['default']);
    }
}
