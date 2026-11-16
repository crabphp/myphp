<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';
require __DIR__ . '/../src/autoload.php';

// Screen reader bindings: on a runtime with FFI and a bundled AccessKit (the macOS binary),
// build one tree update from a typical tree. Elsewhere the bridge stays off.
use PhpUi\AccessKit;

$library = AccessKit::library();
if (!extension_loaded('ffi') || $library === null) {
    check(true, 'screen reader support is not built into this runtime: skipped');
    return;
}
$node = fn (string $id, string $role, string $name, array $state = [], array $children = [], bool $click = false) => [
    'id' => $id, 'role' => $role, 'name' => $name, 'state' => $state, 'children' => $children,
    'bounds' => [10, 10, 100, 30], 'focusable' => $click || $role === 'textbox', 'clickable' => $click,
];
$tree = [
    $node('save', 'button', 'Save', ['focused' => true], [], true),
    $node('agree', 'checkbox', 'Agree', ['checked' => true], [], true),
    $node('tabs', 'tablist', '', [], [$node('t1', 'tab', 'One', ['selected' => true], [], true), $node('t2', 'tab', 'Two', ['selected' => false], [], true)]),
    $node('volume', 'slider', 'Volume', ['value' => 40.0], [], false),
    $node('name', 'textbox', 'Your name', ['value' => 'Ada', 'multiline' => false]),
    $node('dialog', 'dialog', 'Settings', [], [$node('note', 'text', 'Pick one')]),
];
same(10, AccessKit::selfTest($tree, $library), 'AccessKit builds a tree update with every node and the window');
check(ini_get('ffi.enable') === '1' || PHP_SAPI === 'cli', 'FFI is enabled for apps');
