<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';
require __DIR__ . '/../src/autoload.php';
use PhpUi\{App, Desktop, DBus, Theme};
$app = new App('Theme', 200, 100, fn () => PhpUi\col()->full()->bg(PhpUi\t('background')));
if (getenv('PHPUI_TEST_MODE') === 'real-portal') {
    $reply = null; $error = null;
    $bus = DBus::session();
    $bus->call('org.freedesktop.portal.Desktop', '/org/freedesktop/portal/desktop', 'org.freedesktop.portal.Settings', 'ReadOne', 'ss', ['org.freedesktop.appearance', 'color-scheme'], function ($value, $why) use (&$reply, &$error) { $reply = $value; $error = $why; });
    until(function () use (&$reply, &$error) { return $reply !== null || $error !== null; }, poll: $bus->poll(...));
    same(null, $error, 'real portal Settings.ReadOne succeeds');
    check(in_array($reply[0][1] ?? null, [0, 1, 2], true), 'real portal returns valid color scheme');
    exit;
}
fakeSet('scheme', '1');
$changes = [];
Desktop::followSystemTheme(function ($dark) use (&$changes) { $changes[] = $dark; });
check(until(fn () => Theme::dark(), poll: $app->step(...)), 'initial portal scheme turns app dark');
$shot = getenv('PHPUI_TEST_TMP') . '/theme.png'; $app->screenshot($shot, 200, 100, 1);
same('#0a0a0a', pixel($shot, 0, 0), 'system dark mode paints the dark background');
until(fn () => Desktop::waitHint() === .25);
same(.25, Desktop::waitHint(), 'signal watcher wakes each quarter second');
$changes = [];
fakeSet('scheme', '2');
check(until(fn () => !Theme::dark(), .5, $app->step(...)), 'system light change appears within half a second');
same([false], $changes, 'theme change callback fires once');
Theme::toggle(); // explicit dark
fakeSet('scheme', '2');
until(fn () => false, .1, $app->step(...));
check(Theme::dark(), 'explicit toggle stops following system changes');
same('dark', Theme::$source, 'source stays explicit');
fakeSet('scheme', '0');
Desktop::followSystemTheme();
until(fn () => Desktop::systemDark() === null, poll: $app->step(...));
check(Theme::dark(), 'no preference preserves the current mode');
same(null, Desktop::systemDark(), 'no preference is exposed as unknown');
Theme::use('dark');
fakeSet('settings', 'legacy'); fakeSet('scheme', '2');
Desktop::followSystemTheme();
check(until(fn () => !Theme::dark(), poll: $app->step(...)), 'legacy nested Read variant works');
check(count(fakeCalls('Read')) > 0, 'UnknownMethod triggers legacy Read');
Desktop::shutdown();
putenv('PHPUI_DESKTOP=fallback');
putenv('PATH=' . __DIR__ . '/fakes/bin:' . getenv('PATH'));
Desktop::followSystemTheme();
check(until(fn () => Theme::dark(), poll: $app->step(...)), 'gsettings fallback applies prefer-dark');
same(['get', 'org.gnome.desktop.interface', 'color-scheme'], array_slice(fakeCalls('gsettings'), -1)[0]['argv'], 'fallback only reads settings');
same(.5, Desktop::waitHint(), 'fallback has no live watcher');
