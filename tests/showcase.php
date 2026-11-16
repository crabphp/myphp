<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';

// The showcase's component pages: every section renders in light and dark, and its examples respond.
putenv('PHPUI_NO_RUN=1');
putenv('PHPUI_SCREENSHOTS');
require __DIR__ . '/../examples/showcase.php';
use PhpUi\Theme;

$tmp = getenv('PHPUI_TEST_TMP');
foreach (['light', 'dark'] as $mode) {
    Theme::set($mode);
    foreach (array_keys($sections) as $key) {
        $app->clickId("nav-$key");
        same($key, $state->page, "the $key section opens ($mode)");
        $app->screenshot("$tmp/gallery-$key-$mode.png", 1100, 760, 1);
        check(filesize("$tmp/gallery-$key-$mode.png") > 5000, "the $key section renders ($mode)");
    }
}
Theme::set('light');

$app->clickId('nav-buttons');
$app->clickId('click-me');
same(1, $state->clicks, 'buttons respond');
$app->clickId('nav-choices');
$app->clickId('agree');
same(false, $state->agree, 'the checkbox toggles');
$app->clickId('plan-trigger');
check($app->find('plan-menu') !== null, 'the select opens');
$app->clickId('nav-overlays');
$app->clickId('open-dialog');
check($app->find('dialog-save') !== null, 'the dialog opens');
$app->clickId('dialog-save');
same(false, $state->editing, 'the dialog closes');
$app->clickId('open-menu');
check($app->find('menu') !== null, 'the dropdown menu opens');
$app->key(GLFW_KEY_ESCAPE);
same(false, $state->menu, 'Escape closes the menu');
// The toast example is below the fold: scroll the page first.
$app->pointer(600, 400);
$app->wheel(-20);
$app->clickId('show-toast');
same('Saved', $state->toast[0] ?? null, 'the toast shows');
$app->clickId('nav-motion');
$app->clickId('toggle-lamp');
same(true, $state->lit, 'the transition example toggles');
$app->clickId('nav-display');
$app->clickId('nav-buttons');
$copy = $app->find('0.0');
check($copy !== null, 'the showcase tree has stable ids');
$app->clickId('nav-dashboard');
same('dashboard', $state->page, 'the dashboard page opens from the sidebar');
check($app->find('add-section') !== null, 'the dashboard shows its table');
// The dashboard scrolls: its card is no taller than the window, so there is room to scroll.
$app->pointer(800, 500);
$main = $app->find('main');
check($main !== null && $main->contentH > $main->h, 'the dashboard is taller than its scroll area');
$app->wheel(-3);
check($app->find('main')->scrollY > 0, 'the wheel scrolls the dashboard');
