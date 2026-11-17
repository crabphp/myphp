<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';
require __DIR__ . '/../src/autoload.php';
use PhpUi\{App, Desktop, Key, Theme};
use function PhpUi\{col, textInput, t};
$state = (object) ['text' => '', 'keys' => 0];
$app = new App('Messages', 640, 560, fn () => col()->full()->bg(t('background'))->child(textInput('input', $state->text, fn ($v) => $state->text = $v)));
$app->onKey(fn () => $state->keys++);
foreach (['light', 'dark'] as $mode) {
    Theme::use($mode);
    $app->clickId('input');
    $result = null;
    Desktop::confirm('Proceed?', str_repeat('A long message stays inside the panel. ', 11), function ($ok) use (&$result) { $result = $ok; });
    $shot = getenv('PHPUI_TEST_TMP') . "/message-$mode.png";
    $app->screenshot($shot, 640, 560, 1);
    $panel = $app->find('message-panel');
    same(96.0, $panel->x, "$mode panel centered horizontally");
    check(abs($panel->y + $panel->h / 2 - 280) < 1, "$mode panel centered vertically");
    same(t('background'), pixel($shot, 104, (int) ($panel->y + $panel->h / 2)), "$mode panel background pixels");
    check(pixel($shot, 0, 0) !== t('background'), "$mode overlay dims background");
    foreach ($app->find('message-text')->children as $line) check($line->w <= 400, "$mode message line fits");
    $app->click(0, 0);
    check($app->find('message-panel') !== null, 'outside click does not dismiss message');
    $app->type('hidden'); $app->key(Key::F1);
    same('', $state->text, 'message clears input focus');
    same(0, $state->keys, 'modal swallows other keys');
    $app->clickId('message-button-1');
    same(true, $result, 'confirm OK reports true');
    same(null, $app->find('message-panel'), 'confirm disappears after click');
    Desktop::confirm('Enter', 'Default', function ($ok) use (&$result) { $result = $ok; });
    $app->key(Key::ENTER); same(true, $result, 'Enter activates default');
    Desktop::confirm('Escape', 'Cancel', function ($ok) use (&$result) { $result = $ok; });
    $app->key(Key::ESCAPE); same(false, $result, 'Escape activates cancel');
    $queue = [];
    Desktop::message('One', 'First', done: function ($i) use (&$queue) { $queue[] = 1; });
    Desktop::message('Two', 'Second', done: function ($i) use (&$queue) { $queue[] = 2; });
    $app->key(Key::ENTER);
    same([1], $queue, 'first queued message alone completes');
    check($app->find('message-panel') !== null, 'next queued message is shown');
    $app->key(Key::ESCAPE);
    same([1, 2], $queue, 'second queued message completes');
}
