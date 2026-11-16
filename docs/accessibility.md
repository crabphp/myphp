# Accessibility

MyPHP draws its own interface, so it has to provide what native controls get for
free. This is what works today, on every platform.

## Keyboard

- **Tab / Shift+Tab** move through every control: inputs, buttons, checkboxes,
  switches, selects, sliders and anything with `onClick`, `onDrag` or `onKeyDown`.
  Keyboard focus shows a ring; mouse clicks do not move focus to buttons, so app
  shortcuts keep working after a click.
- **Enter** or **Space** presses the focused control.
- **Arrow keys** move between the tabs of a tab bar, the items of a toggle group and
  the options of a menu; each such group is one Tab stop. On a slider they change the
  value by 5 (Page Up/Down by 10, Home/End to the ends). **Down** opens a `select()`.
- **Escape** closes the newest open menu or dialog, otherwise leaves the input.
- While a `dialog()` is open, Tab stays inside it.
- **Ctrl/Cmd + / − / 0** zoom the whole interface (`$app->zoom(1.25)` from code).

Your own controls take part with:

```php
row()->onClick($open)->label('Open file');            // focusable, named
div()->onKeyDown(fn (int $key, int $mods) => ...);    // return true when you used the key
col()->onEscape($close)->trapFocus();                 // a custom dialog
row()->role('radiogroup');                            // arrows move between its items
$item->tabStop(false);                                // reached by arrows, not Tab
$element->focusable(false);                           // never focused
$app->focusElement('search');                         // move focus from code
```

## Motion

Transitions and fade-ins follow the system's reduce-motion setting (GNOME's
animations switch, macOS Reduce motion, Windows window animations), read when the app
starts. `$app->reduceMotion(true)` turns them off yourself; `PHPUI_REDUCE_MOTION=1`
forces it for testing.

## Color

Text colors in the built-in light and dark themes meet WCAG AA contrast (4.5:1), and
the focus ring meets 3:1. `tests/contrast.php` checks every text pair. Two shadcn
colors are slightly darker for this: `muted-foreground` and `ring` in light mode.

## Names and roles

Components describe themselves: buttons, checkboxes and switches (with checked
state), tabs (selected), selects (value, expanded), sliders and progress bars (value),
menus, dialogs and text boxes. Give icon-only buttons a name with
`->label('Settings')`, and your own controls a role with
`->role('checkbox', ['checked' => $on])`.

`$app->accessibilityTree()` returns that information as nested
`[role, name, state, children]` arrays, which is useful in tests.

## Screen readers

**macOS (VoiceOver), experimental.** The macOS `myphp` includes
[AccessKit](https://accesskit.dev) and loads it with PHP's FFI. Every window publishes
the tree above to VoiceOver: each control's role, name, state, position and the
keyboard focus. VoiceOver can then read the window, follow focus as you Tab, and act:
press buttons, check boxes, pick tabs and menu items, and step sliders. Apps built
with `myphp build` on a Mac include it too. Turn it off with `PHPUI_ACCESSKIT=0`.

Linux (Orca, through AT-SPI) and Windows (Narrator and NVDA, through UI Automation)
are next; the bridge in `src/AccessKit.php` already has the tree they need. On those
systems MyPHP apps are still invisible to screen readers.
