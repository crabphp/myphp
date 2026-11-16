<?php

declare(strict_types=1);

namespace PhpUi;

use FFI;
use FFI\CData;

/**
 * Screen reader support through AccessKit (https://accesskit.dev), loaded with FFI.
 * The app's accessibility tree (roles, names, states, bounds, focus) is published
 * to the platform; screen reader actions come back as clicks, focus and value steps.
 *
 * macOS (VoiceOver) for now. It needs the FFI extension and libaccesskit, which the
 * macOS myphp binary includes; elsewhere attach() returns null and nothing changes.
 */
final class AccessKit
{
    private const ROLES = [
        'button' => 18, 'checkbox' => 15, 'switch' => 29, 'tab' => 120, 'tablist' => 121, 'radiogroup' => 102,
        'radio' => 16, 'menu' => 30, 'menuitem' => 11, 'combobox' => 56, 'slider' => 113, 'progressbar' => 101,
        'dialog' => 66, 'textbox' => 17, 'text' => 3, 'image' => 4, 'alert' => 44, 'list' => 24,
    ];
    private const ROLE_MULTILINE = 31, ROLE_MENU_CHECK = 94, ROLE_CONTAINER = 14, ROLE_WINDOW = 133;
    private const CLICK = 0, FOCUS = 1, COLLAPSE = 3, EXPAND = 4, DECREMENT = 6, INCREMENT = 7;
    private const WINDOW_ID = 1;

    private const HEADER = <<<'C'
        typedef uint64_t accesskit_node_id;
        typedef uint8_t accesskit_role;
        typedef uint8_t accesskit_action;
        typedef uint8_t accesskit_toggled;
        typedef struct accesskit_node accesskit_node;
        typedef struct accesskit_tree_update accesskit_tree_update;
        typedef struct accesskit_tree_info accesskit_tree_info;
        typedef struct accesskit_macos_queued_events accesskit_macos_queued_events;
        typedef struct accesskit_macos_subclassing_adapter accesskit_macos_subclassing_adapter;
        typedef struct accesskit_rect { double x0; double y0; double x1; double y1; } accesskit_rect;
        typedef struct accesskit_affine { double _0[6]; } accesskit_affine;
        typedef struct accesskit_tree_id { uint8_t bytes[16]; } accesskit_tree_id;
        /* Only the leading fields are read; the library frees the whole request. */
        typedef struct accesskit_action_request { accesskit_action action; accesskit_tree_id target_tree; accesskit_node_id target_node; } accesskit_action_request;
        typedef accesskit_tree_update *(*accesskit_tree_update_factory)(void *userdata);
        typedef void (*accesskit_action_handler_callback)(accesskit_action_request *request, void *userdata);

        accesskit_node *accesskit_node_new(accesskit_role role);
        void accesskit_node_set_label(accesskit_node *node, const char *value);
        void accesskit_node_set_value(accesskit_node *node, const char *value);
        void accesskit_node_set_placeholder(accesskit_node *node, const char *value);
        void accesskit_node_set_bounds(accesskit_node *node, accesskit_rect value);
        void accesskit_node_set_transform(accesskit_node *node, accesskit_affine value);
        void accesskit_node_set_children(accesskit_node *node, size_t length, const accesskit_node_id *values);
        void accesskit_node_add_action(accesskit_node *node, accesskit_action action);
        void accesskit_node_set_toggled(accesskit_node *node, accesskit_toggled value);
        void accesskit_node_set_selected(accesskit_node *node, bool value);
        void accesskit_node_set_expanded(accesskit_node *node, bool value);
        void accesskit_node_set_modal(accesskit_node *node);
        void accesskit_node_set_numeric_value(accesskit_node *node, double value);
        void accesskit_node_set_min_numeric_value(accesskit_node *node, double value);
        void accesskit_node_set_max_numeric_value(accesskit_node *node, double value);
        void accesskit_node_set_numeric_value_step(accesskit_node *node, double value);
        accesskit_tree_info *accesskit_tree_info_new(accesskit_node_id root);
        void accesskit_tree_info_set_toolkit_name(accesskit_tree_info *tree, const char *value);
        accesskit_tree_update *accesskit_tree_update_with_capacity_and_focus(size_t capacity, accesskit_node_id focus);
        void accesskit_tree_update_set_tree_info(accesskit_tree_update *update, accesskit_tree_info *tree);
        void accesskit_tree_update_push_node(accesskit_tree_update *update, accesskit_node_id id, accesskit_node *node);
        void accesskit_tree_update_free(accesskit_tree_update *update);
        void accesskit_action_request_free(accesskit_action_request *request);
        void accesskit_macos_add_focus_forwarder_to_window_class(const char *class_name);
        accesskit_macos_subclassing_adapter *accesskit_macos_subclassing_adapter_for_window(void *window,
            accesskit_tree_update_factory activation_handler, void *activation_handler_userdata,
            accesskit_action_handler_callback action_handler, void *action_handler_userdata);
        accesskit_macos_queued_events *accesskit_macos_subclassing_adapter_update_if_active(
            accesskit_macos_subclassing_adapter *adapter, accesskit_tree_update_factory update_factory, void *update_factory_userdata);
        accesskit_macos_queued_events *accesskit_macos_subclassing_adapter_update_view_focus_state(
            accesskit_macos_subclassing_adapter *adapter, bool is_focused);
        void accesskit_macos_queued_events_raise(accesskit_macos_queued_events *events);
        C;

    private FFI $ak;
    private ?CData $adapter = null;
    /** @var array<string, int> Node ids by element id, stable for the app's lifetime. */
    private array $ids = [];
    private array $elements = [];
    private int $next = 2;
    private string $published = '';
    private ?bool $windowFocused = null;
    private array $actions = [];
    /** Callbacks are kept here so PHP never frees one the library still holds. */
    private array $callbacks = [];

    private function __construct(private App $app, string $library)
    {
        $this->ak = FFI::cdef(self::HEADER, $library);
    }

    /** The bridge for this app, or null where screen readers are not supported (yet). */
    public static function attach(App $app): ?self
    {
        $off = match (true) {
            getenv('PHPUI_ACCESSKIT') === '0' => 'PHPUI_ACCESSKIT=0',
            PHP_OS_FAMILY !== 'Darwin' => 'only macOS is supported so far',
            !extension_loaded('ffi') => 'this runtime has no FFI extension (update myphp)',
            self::library() === null => 'libaccesskit is not bundled in this myphp (update myphp)',
            !in_array(ini_get('ffi.enable'), ['1', 'On', 'on', 'true'], true) && PHP_SAPI !== 'cli' => 'ffi.enable is ' . var_export(ini_get('ffi.enable'), true),
            default => null,
        };
        if ($off !== null) { self::debug("off: $off"); return null; }
        try {
            $bridge = new self($app, self::library());
            if (!$bridge->connectMacOS()) { self::debug('off: no window to attach to'); return null; }
            self::debug('attached to the window; waiting for VoiceOver to ask for the tree');
            return $bridge;
        } catch (\Throwable $e) {
            fwrite(STDERR, 'myphp: screen reader support is off: ' . $e->getMessage() . "\n");
            return null;
        }
    }

    /** PHPUI_DEBUG=1 prints what the bridge does, for checking it with a screen reader. */
    public static function debug(string $message): void
    {
        if (getenv('PHPUI_DEBUG')) fwrite(STDERR, "myphp: accessibility: $message\n");
    }

    /** libaccesskit for this system: MYPHP_ACCESSKIT, or the copy bundled in assets/native. */
    public static function library(): ?string
    {
        if ($path = getenv('MYPHP_ACCESSKIT')) return is_file($path) ? $path : null;
        $arch = in_array(strtolower(php_uname('m')), ['arm64', 'aarch64'], true) ? 'arm64' : 'x86_64';
        $file = match (PHP_OS_FAMILY) {
            'Darwin' => "macos-$arch/libaccesskit.dylib",
            'Windows' => "windows-$arch/accesskit.dll",
            default => "linux-$arch/libaccesskit.so",
        };
        $bundled = dirname(__DIR__) . "/assets/native/$file";
        // The dynamic loader needs a real file, not one inside the phar.
        return is_file($bundled) ? realFile($bundled) : null;
    }

    /** Attach to the app's NSWindow (SDL's or GLFW's): AccessKit subclasses its content view. */
    private function connectMacOS(): bool
    {
        $objc = FFI::cdef('void *objc_getClass(const char *name); void *sel_registerName(const char *name); void *objc_msgSend(void *self, void *op); const char *object_getClassName(void *obj);', '/usr/lib/libobjc.A.dylib');
        $at = FFI::cdef('void *objc_msgSend(void *self, void *op, unsigned long index);', '/usr/lib/libobjc.A.dylib');
        $send = fn ($target, string $selector) => $objc->objc_msgSend($target, $objc->sel_registerName($selector));
        $nsApp = $send($objc->objc_getClass('NSApplication'), 'sharedApplication');
        $window = $send($nsApp, 'keyWindow');
        if (FFI::isNull($window)) {
            $windows = $send($nsApp, 'windows');
            $count = FFI::cast('uintptr_t', $send($windows, 'count'))->cdata;
            if ($count < 1) return false;
            $window = $at->objc_msgSend($windows, $objc->sel_registerName('objectAtIndex:'), 0);
        }
        // The window system focuses its window, not the view: forward VoiceOver's focus
        // question to the view, on the window's own class (SDL3Window, GLFWWindow).
        $class = $objc->object_getClassName($window);
        $this->ak->accesskit_macos_add_focus_forwarder_to_window_class(is_string($class) ? $class : \FFI::string($class));
        $this->callbacks = [
            'activate' => function ($userdata) {
                self::debug('VoiceOver connected: sending the tree');
                return $this->update(true);
            },
            'action' => function ($request, $userdata) {
                // Queue it: the app handles it on its next loop turn, never in the middle of a frame.
                $this->actions[] = [(int) $request->action, (int) $request->target_node];
                self::debug('action ' . (int) $request->action . ' on node ' . (int) $request->target_node);
                $this->ak->accesskit_action_request_free($request);
            },
            'update' => fn ($userdata) => $this->update(false),
        ];
        $this->adapter = $this->ak->accesskit_macos_subclassing_adapter_for_window(
            $window, $this->callbacks['activate'], null, $this->callbacks['action'], null);
        return !FFI::isNull($this->adapter);
    }

    /** After a frame: publish the tree if it changed (only while a screen reader listens). */
    public function sync(): void
    {
        if (!$this->adapter) return;
        // Polled rather than a GLFW focus callback (that callback breaks later layout in PHP-GLFW).
        $focused = $this->app->window->focused();
        if ($focused !== $this->windowFocused) $this->windowFocus($this->windowFocused = $focused);
        $signature = serialize([$this->app->accessibilityTree(), $this->app->zoom(), $this->app->title]);
        if ($signature === $this->published) return;
        $this->published = $signature;
        $events = $this->ak->accesskit_macos_subclassing_adapter_update_if_active($this->adapter, $this->callbacks['update'], null);
        $active = !FFI::isNull($events);
        if ($active) $this->ak->accesskit_macos_queued_events_raise($events);
        self::debug('tree changed: ' . ($active ? 'sent ' . (count($this->elements)) . ' known controls' : 'no screen reader listening'));
    }

    public function windowFocus(bool $focused): void
    {
        if (!$this->adapter) return;
        $events = $this->ak->accesskit_macos_subclassing_adapter_update_view_focus_state($this->adapter, $focused);
        if (!FFI::isNull($events)) $this->ak->accesskit_macos_queued_events_raise($events);
    }

    /** Run queued screen reader actions; true when one ran. */
    public function runActions(): bool
    {
        $ran = false;
        while ([$action, $node] = array_shift($this->actions) ?? [null, null]) {
            $id = $this->elements[$node] ?? null;
            if ($id === null || !$this->app->find($id)) continue;
            match ($action) {
                self::FOCUS => $this->app->focusElement($id),
                self::CLICK, self::EXPAND, self::COLLAPSE => $this->app->clickId($id),
                self::INCREMENT, self::DECREMENT => $this->step($id, $action === self::INCREMENT),
                default => null,
            };
            $ran = true;
        }
        return $ran;
    }

    private function step(string $id, bool $up): void
    {
        $this->app->focusElement($id);
        $this->app->key($up ? GLFW_KEY_RIGHT : GLFW_KEY_LEFT);
    }

    /** A tree update for AccessKit; the library takes ownership of it. */
    public function update(bool $initial, ?array $tree = null, ?float $scale = null, ?string $title = null): CData
    {
        $tree ??= $this->app->accessibilityTree();
        $scale ??= $this->app->scale();
        $title ??= $this->app->title;
        $nodes = [];
        $focus = self::WINDOW_ID;
        $children = $this->collect($tree, $nodes, $focus);
        $update = $this->ak->accesskit_tree_update_with_capacity_and_focus(count($nodes) + 1, $focus);
        if ($initial) {
            $info = $this->ak->accesskit_tree_info_new(self::WINDOW_ID);
            $this->ak->accesskit_tree_info_set_toolkit_name($info, 'MyPHP');
            $this->ak->accesskit_tree_update_set_tree_info($update, $info);
        }
        $window = $this->ak->accesskit_node_new(self::ROLE_WINDOW);
        $this->ak->accesskit_node_set_label($window, $title);
        // Bounds are in layout units: scale them to the window's pixels (Retina, zoom).
        $transform = $this->ak->new('accesskit_affine');
        [$transform->_0[0], $transform->_0[3]] = [$scale, $scale];
        $this->ak->accesskit_node_set_transform($window, $transform);
        $this->children($window, $children);
        $this->ak->accesskit_tree_update_push_node($update, self::WINDOW_ID, $window);
        foreach ($nodes as [$id, $node]) $this->ak->accesskit_tree_update_push_node($update, $id, $node);
        return $update;
    }

    /** Make AccessKit nodes for $tree; returns the top-level node ids. */
    private function collect(array $tree, array &$nodes, int &$focus): array
    {
        $ids = [];
        foreach ($tree as $n) {
            $id = $this->ids[$n['id']] ??= $this->next++;
            $this->elements[$id] = $n['id'];
            $state = $n['state'];
            $role = match (true) {
                $n['role'] === 'textbox' && !empty($state['multiline']) => self::ROLE_MULTILINE,
                $n['role'] === 'menuitem' && array_key_exists('checked', $state) => self::ROLE_MENU_CHECK,
                default => self::ROLES[$n['role']] ?? self::ROLE_CONTAINER,
            };
            $node = $this->ak->accesskit_node_new($role);
            if ($n['name'] !== '' && $n['role'] !== 'textbox') $this->ak->accesskit_node_set_label($node, $n['name']);
            if ($n['role'] === 'textbox') {
                $this->ak->accesskit_node_set_value($node, (string) ($state['value'] ?? ''));
                if ($n['name'] !== '') $this->ak->accesskit_node_set_placeholder($node, $n['name']);
            }
            [$x, $y, $w, $h] = $n['bounds'];
            $rect = $this->ak->new('accesskit_rect');
            [$rect->x0, $rect->y0, $rect->x1, $rect->y1] = [$x, $y, $x + $w, $y + $h];
            $this->ak->accesskit_node_set_bounds($node, $rect);
            if (array_key_exists('checked', $state) || array_key_exists('pressed', $state)) {
                $this->ak->accesskit_node_set_toggled($node, ($state['checked'] ?? $state['pressed']) ? 1 : 0);
            }
            if (array_key_exists('selected', $state)) $this->ak->accesskit_node_set_selected($node, (bool) $state['selected']);
            if (array_key_exists('expanded', $state)) $this->ak->accesskit_node_set_expanded($node, (bool) $state['expanded']);
            if ($n['role'] === 'dialog') $this->ak->accesskit_node_set_modal($node);
            if (in_array($n['role'], ['slider', 'progressbar'], true) && isset($state['value'])) {
                $this->ak->accesskit_node_set_numeric_value($node, (float) $state['value']);
                $this->ak->accesskit_node_set_min_numeric_value($node, 0.0);
                $this->ak->accesskit_node_set_max_numeric_value($node, 100.0);
                $this->ak->accesskit_node_set_numeric_value_step($node, 5.0);
            }
            if ($n['focusable']) $this->ak->accesskit_node_add_action($node, self::FOCUS);
            if ($n['clickable']) $this->ak->accesskit_node_add_action($node, self::CLICK);
            if ($n['role'] === 'slider') {
                $this->ak->accesskit_node_add_action($node, self::INCREMENT);
                $this->ak->accesskit_node_add_action($node, self::DECREMENT);
            }
            if (!empty($state['focused'])) $focus = $id;
            $this->children($node, $this->collect($n['children'], $nodes, $focus));
            $nodes[] = [$id, $node];
            $ids[] = $id;
        }
        return $ids;
    }

    private function children(CData $node, array $ids): void
    {
        if (!$ids) return;
        $list = $this->ak->new('accesskit_node_id[' . count($ids) . ']');
        foreach ($ids as $i => $id) $list[$i] = $id;
        $this->ak->accesskit_node_set_children($node, count($ids), $list);
    }

    /**
     * Load the library and build (then free) one tree update from $tree: checks the
     * bindings without a window. Used by CI on macOS.
     */
    public static function selfTest(array $tree, string $library): int
    {
        $bridge = (new \ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $bridge->ak = FFI::cdef(self::HEADER, $library);
        $update = $bridge->update(true, $tree, 2.0, 'Self test');
        $count = count($bridge->ids) + 1;
        $bridge->ak->accesskit_tree_update_free($update);
        return $count;
    }
}
