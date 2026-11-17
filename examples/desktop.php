<?php

declare(strict_types=1);

require __DIR__ . '/../src/autoload.php';

use PhpUi\{App, Desktop, Key, Theme};
use function PhpUi\{button, card, col, row, state, t, text, textInput};

Desktop::$appName = 'MyPHP Desktop';
$state = state(['log' => [], 'clipboard' => 'Hello from PHP ✓', 'target' => 'https://example.com', 'notification' => null]);
$log = function (string $event, mixed $value = null) use ($state) {
    $state->log[] = $event . ': ' . json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($value === null && Desktop::lastError()) $state->log[] = 'Error: ' . Desktop::lastError();
    $state->log = array_slice($state->log, -20);
};
$actions = [
    'open-file' => ['Open file', fn () => Desktop::openFile(fn ($paths) => $log('Open', $paths))],
    'open-many' => ['Open images', fn () => Desktop::openFile(fn ($paths) => $log('Images', $paths), 'Open Images', ['Images' => ['png', 'jpg', 'webp']], true)],
    'save-file' => ['Save file', fn () => Desktop::saveFile(fn ($path) => $log('Save', $path), name: 'note.txt')],
    'pick-folder' => ['Pick folder', fn () => Desktop::pickFolder(fn ($path) => $log('Folder', $path))],
    'message' => ['Message', fn () => Desktop::message('Hello', 'A native PHP message box. The frame loop keeps running while this message is open.', done: fn ($i) => $log('Message', $i))],
    'confirm' => ['Confirm', fn () => Desktop::confirm('Continue?', 'Choose OK to continue or Cancel to leave things unchanged.', fn ($ok) => $log('Confirm', $ok))],
    'error' => ['Error', fn () => Desktop::error('Example error', 'Nothing went wrong. This is the error message component.', fn ($i) => $log('Error box', $i))],
    'notify' => ['Notify', function () use ($state, $log) {
        $state->notification = Desktop::notify('MyPHP Desktop', 'Click to focus the window.', function ($action) use ($log) {
            App::current()?->focus(); $log('Notification action', $action);
        }, icon: __DIR__ . '/../assets/icons/bell.svg', actions: ['open' => 'Open']);
        $log('Notification', $state->notification);
    }],
    'close-notification' => ['Close notification', function () use ($state, $log) {
        if ($state->notification !== null) Desktop::closeNotification($state->notification);
        $log('Close notification', $state->notification);
    }],
    'follow-theme' => ['System theme', function () use ($log) {
        Desktop::followSystemTheme(fn ($dark) => $log('System dark', $dark));
        $log('Theme source', Theme::$source);
    }],
    'theme' => ['Toggle theme', function () use ($log) { Theme::toggle(); $log('Theme source', Theme::$source); }],
];
$render = fn () => col()->full()->p(24)->bg(t('muted'))->textColor(t('foreground'))->center()->schema([
    card()->heading('Desktop')->w(720)->schema([
        col()->gap(8)->schema(array_map(
            fn ($group) => row()->gap(8)->schema(array_map(
                fn ($id, $action) => button($action[0])->outlined()->onClick($action[1])->flex1()->id($id),
                array_keys($group), $group,
            )),
            array_chunk($actions, 3, true),
        )),
        row()->gap(8)->schema([
            textInput('clipboard-text')->bind($state->clipboard)->flex1(),
            button('Copy')->outlined()->id('copy')
                ->onClick(function () use ($state, $log) { Desktop::setClipboard($state->clipboard); $log('Copy', $state->clipboard); }),
            button('Paste')->outlined()->id('paste')
                ->onClick(function () use ($state, $log) { $state->clipboard = Desktop::clipboard(); $log('Paste', $state->clipboard); }),
        ]),
        row()->gap(8)->schema([
            textInput('open-target')->placeholder('URL or file path')->bind($state->target)->flex1(),
            button('Open URL/file')->outlined()->id('open-url')
                ->onClick(fn () => Desktop::open($state->target, fn ($ok) => $log('Open target', $ok))),
        ]),
        col()->h(220)->scroll('log')->gap(4)->schema(array_map(fn ($line) => text($line)->textSize(12)->truncate(), array_reverse($state->log))),
    ]),
]);
$app = new App('MyPHP Desktop', 800, 700, $render);
$app->onDrop(fn ($paths) => $log('Drop', $paths));

if ($dir = getenv('PHPUI_SCREENSHOTS')) {
    @mkdir($dir, 0777, true);
    $shot = fn ($name) => $app->screenshot("$dir/$name.png", 800, 700, 2);
    $shot('desktop-start');
    $app->clickId('message'); $shot('desktop-message'); $app->key(Key::ENTER);
    $app->clickId('confirm'); $shot('desktop-confirm'); $app->clickId('message-button-1');
    $app->clickId('copy'); $app->clickId('paste');
    $app->drop(['/tmp/first file.txt', '/tmp/é.png'], 10, 10);
    $shot('desktop-results');
    $app->clickId('theme'); $shot('desktop-dark');
    echo json_encode(['log' => $state->log, 'clipboard' => $state->clipboard, 'runtime' => [
        'proc_open' => function_exists('proc_open'), 'stream_socket_client' => function_exists('stream_socket_client'), 'posix_geteuid' => function_exists('posix_geteuid'),
    ]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
    exit;
}
if (!getenv('PHPUI_NO_RUN')) $app->run();
