<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';
require __DIR__ . '/../src/autoload.php';
use PhpUi\{App, Desktop, DBus};
$app = new App('Notify', 200, 100, fn () => PhpUi\col()->full());
putenv('PATH=' . __DIR__ . '/fakes/bin:' . getenv('PATH'));
if (getenv('PHPUI_TEST_MODE') === 'real-portal') {
    // This private session has no notification daemon; do not notify the user's desktop.
    putenv('PHPUI_DESKTOP=fallback');
    $action = null;
    Desktop::notify('Fallback', 'Body', function ($value) use (&$action) { $action = $value; });
    until(function () use (&$action) { return $action !== null; });
    same('default', $action, 'notification fallback click works');
    exit;
}
Desktop::$appName = 'MyPHP tests';
$actions = [];
$id = Desktop::notify('T', 'B', function ($key) use (&$actions, $app) { $actions[] = $key; $app->focus(); }, actions: ['open' => 'Open file']);
check($id > 0, 'notify returns local ID immediately');
until(fn () => count(fakeCalls('Notify')) > 0, poll: $app->step(...));
$call = array_slice(fakeCalls('Notify'), -1)[0];
same('MyPHP tests', $call['app'], 'notification app name');
same('T', $call['title'], 'notification title');
same('B', $call['body'], 'notification body');
same(['default', 'Open', 'open', 'Open file'], $call['actions'], 'default and named actions are encoded');
$invoke = function ($member, $signature, $args) use ($app) {
    $done = false;
    DBus::session()->call('org.myphp.Test', '/org/myphp/Test', 'org.myphp.Test', $member, $signature, $args, function ($value, $error) use (&$done) { same(null, $error, 'fake notification control succeeds'); $done = true; });
    until(function () use (&$done) { return $done; }, poll: $app->step(...));
};
$invoke('Invoke', 'us', [$call['id'], 'default']);
same(['default'], $actions, 'body click invokes callback once');
$invoke('Invoke', 'us', [$call['id'], 'open']);
same(['default', 'open'], $actions, 'named action reports key');
same(.25, Desktop::waitHint(), 'live click handler keeps signal wake rate');
$invoke('CloseNote', 'u', [$call['id']]);
$invoke('Invoke', 'us', [$call['id'], 'default']);
same(['default', 'open'], $actions, 'closed notification cannot invoke callback');
Desktop::notify('Quiet', actions: ['ignored' => 'Ignored']);
until(fn () => count(fakeCalls('Notify')) === 2, poll: $app->step(...));
same([], array_slice(fakeCalls('Notify'), -1)[0]['actions'], 'no click handler sends no actions');
$id = Desktop::notify('Close immediately');
Desktop::closeNotification($id);
until(fn () => count(fakeCalls('CloseNotification')) > 0, poll: $app->step(...));
same(3, array_slice(fakeCalls('CloseNotification'), -1)[0]['id'], 'early close uses eventual server ID');
$closedBefore = count(fakeCalls('CloseNotification'));
Desktop::shutdown();
same($closedBefore, count(fakeCalls('CloseNotification')), 'shutdown leaves delivered notifications alive');

fakeSet('notifications', 'off');
$action = null;
Desktop::notify('Fallback', 'Body', function ($key) use (&$action) { $action = $key; }, 'info');
until(function () use (&$action) { return $action !== null; });
same('default', $action, 'missing service falls back and returns click action');
$args = array_slice(fakeCalls('notify-send'), -1)[0]['argv'];
same(['--app-name=MyPHP tests', '--icon=info', '--action=default=Open', '--', 'Fallback', 'Body'], $args, 'notify-send receives safe argv');
putenv('PHPUI_DESKTOP=fallback'); putenv('FAKE_NOTIFY_SLEEP=30');
$id = Desktop::notify('Pending', onClick: fn () => check(false, 'closed fallback must not click'));
Desktop::closeNotification($id);
until(fn () => Desktop::waitHint() === .5);
same(.5, Desktop::waitHint(), 'closing fallback reaps waiting helper');
for ($i = 0; $i < 65; $i++) Desktop::notify("Bounded $i");
check(count((new ReflectionProperty(Desktop::class, 'notifications'))->getValue()) <= 64, 'notification registry is bounded');
Desktop::shutdown();
