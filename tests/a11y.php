<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';
require __DIR__ . '/../src/autoload.php';
use PhpUi\App;
use function PhpUi\{button, checkbox, col, dialog, label, row, select, slider, state, switchToggle, tabs, text, textInput};

// Keyboard access to every control, focus ring, Escape, dialogs, zoom, reduced motion
// and the accessibility tree.
$s = state(['clicks' => 0, 'agree' => false, 'on' => false, 'tab' => 'a', 'level' => 50, 'size' => 'm', 'dialog' => false, 'name' => '']);
$app = new App('A11y', 600, 400, fn () => col()->full()->p(10)->gap(8)
    ->child(button('Save', fn () => $s->clicks++)->id('save'))
    ->child(row()->gap(8)->child(checkbox($s->agree, fn () => $s->agree = !$s->agree)->id('agree'))->child(label('Agree')))
    ->child(switchToggle($s->on, fn () => $s->on = !$s->on)->id('switch'))
    ->child(tabs(['a' => 'Alpha', 'b' => 'Beta', 'c' => 'Gamma'], $s->tab, fn ($v) => $s->tab = $v))
    ->child(slider($s->level, fn ($v) => $s->level = $v)->w(200)->id('slider'))
    ->child(select('size', $s->size, ['s' => 'Small', 'm' => 'Medium', 'l' => 'Large'], fn ($v) => $s->size = $v)->w(160))
    ->child(button('', fn () => $s->dialog = true, 'ghost', 'icon', 'settings')->label('Settings')->id('open-dialog'))
    ->when($s->dialog, fn ($e) => $e->child(dialog('Rename', 'Pick a new name.',
        textInput('dialog-name', $s->name, fn ($v) => $s->name = $v),
        row()->gap(8)->child(button('Cancel', fn () => $s->dialog = false, 'outline')->id('cancel'))->child(button('OK', fn () => $s->dialog = false)->id('ok')),
        fn () => $s->dialog = false))));
$app->frame();
$focused = fn () => (function () { return $this->focusId; })->call($app);

// Tab reaches every control in order; Enter and Space activate.
$app->key(GLFW_KEY_TAB);
same('save', $focused(), 'Tab starts at the first control');
check($app->find('save')->focusVisible, 'keyboard focus shows a ring');
$app->key(GLFW_KEY_ENTER);
same(1, $s->clicks, 'Enter presses a button');
$app->key(GLFW_KEY_TAB);
same('agree', $focused(), 'Tab moves to the checkbox');
$app->key(GLFW_KEY_SPACE);
same(true, $s->agree, 'Space toggles a checkbox');
$app->key(GLFW_KEY_TAB);
$app->key(GLFW_KEY_SPACE);
same(true, $s->on, 'Space toggles a switch');
$app->key(GLFW_KEY_TAB);
same('tab-a', $focused(), 'Tab reaches the tabs');
$app->key(GLFW_KEY_RIGHT);
same('tab-b', $focused(), 'Right arrow moves to the next tab');
$app->key(GLFW_KEY_ENTER);
same('b', $s->tab, 'Enter picks the tab');
$app->key(GLFW_KEY_TAB, GLFW_MOD_SHIFT);
same('switch', $focused(), 'Shift+Tab goes back');

// Slider keys.
$app->focusElement('slider');
$app->key(GLFW_KEY_RIGHT);
same(55.0, (float) $s->level, 'Right arrow raises a slider by 5');
$app->key(GLFW_KEY_HOME);
same(0.0, (float) $s->level, 'Home moves a slider to 0');

// Select: Down opens and enters the menu, arrows move, Enter picks, focus returns.
$app->focusElement('size-trigger');
$app->key(GLFW_KEY_DOWN);
check($app->find('size-menu') !== null, 'Down opens a select');
same('size-menu-0', $focused(), 'and focuses its first option');
$app->key(GLFW_KEY_DOWN);
$app->key(GLFW_KEY_DOWN);
$app->key(GLFW_KEY_ENTER);
same('l', $s->size, 'arrows and Enter choose an option');
same(null, $app->find('size-menu'), 'choosing closes the menu');
same('size-trigger', $focused(), 'focus returns to the select');
$app->key(GLFW_KEY_SPACE);
check($app->find('size-menu') !== null, 'Space opens it too');
$app->key(GLFW_KEY_ESCAPE);
same(null, $app->find('size-menu'), 'Escape closes the menu');

// A dialog: Escape closes it, and Tab stays inside while it is open.
$app->clickId('open-dialog');
same(true, $s->dialog, 'the icon button opens a dialog');
$seen = [];
for ($i = 0; $i < 6; $i++) { $app->key(GLFW_KEY_TAB); $seen[] = $focused(); }
same(['dialog-close', 'dialog-name', 'cancel', 'ok', 'dialog-close', 'dialog-name'], $seen, 'Tab cycles inside an open dialog');
$app->key(GLFW_KEY_ESCAPE);
same(false, $s->dialog, 'Escape closes a dialog');

// Mouse clicks focus without a ring.
$app->clickId('save');
check(!$app->find('save')->focusVisible, 'a mouse click shows no focus ring');

// Zoom: the interface scales; the pointer still lands on the right control.
$mod = PHP_OS_FAMILY === 'Darwin' ? GLFW_MOD_SUPER : GLFW_MOD_CONTROL;
$app->key(GLFW_KEY_EQUAL, $mod);
same(1.1, $app->zoom(), 'Ctrl/Cmd + zooms in');
$app->frame();
same(600 / 1.1, $app->find('0')->w, 'zoom lays out a smaller logical window');
$app->key(GLFW_KEY_0, $mod);
same(1.0, $app->zoom(), 'Ctrl/Cmd 0 resets zoom');

// Reduced motion turns transitions off.
$app->reduceMotion(true);
check((function () { return $this->painter->reduceMotion; })->call($app), 'reduceMotion() reaches the painter');

// The accessibility tree.
$tree = $app->accessibilityTree();
$flat = [];
$walk = function (array $nodes) use (&$walk, &$flat) { foreach ($nodes as $n) { $flat[] = $n; $walk($n['children']); } };
$walk($tree);
$find = fn (string $role, string $name) => array_values(array_filter($flat, fn ($n) => $n['role'] === $role && $n['name'] === $name))[0] ?? null;
check($find('button', 'Save') !== null, 'buttons are named by their text');
same(['checked' => true], $find('checkbox', '')['state'] ?? null, 'a checkbox reports whether it is checked');
same(true, $find('tab', 'Beta')['state']['selected'] ?? null, 'the selected tab is marked');
check($find('button', 'Settings') !== null, 'an icon button is named by its label');
same(0.0, (float) ($find('slider', '')['state']['value'] ?? -1), 'a slider reports its value');
same('Large', $find('combobox', 'Large')['state']['value'] ?? null, 'a select reports its value');
