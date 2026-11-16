<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';

// The mail example: reading, folders, search, archive, delete, star, reply and compose.
putenv('PHPUI_NO_RUN=1');
putenv('PHPUI_SCREENSHOTS');
require __DIR__ . '/../examples/mail.php';
$app->frame();

$unread = fn () => count(array_filter($state->mail, fn ($m) => $m['folder'] === 'inbox' && $m['unread']));
same(1, $unread(), 'opening the first message marks it read');
$app->clickId('mail-1');
same([1, 0], [$state->open, $unread()], 'clicking a message opens it and marks it read');
same('[crabphp/myphp] v0.2.0 released', $app->find('subject')->text, 'the reader shows the subject');

$app->key(GLFW_KEY_J);
same(2, $state->open, 'j opens the next message');
$app->key(GLFW_KEY_K);
same(1, $state->open, 'k goes back');

$app->clickId('star');
same(true, $state->mail[1]['starred'], 'the star button stars a message');
$app->clickId('archive');
same('archive', $state->mail[1]['folder'], 'archive moves the message');
same(2, $state->open, 'and opens the next one');
check($app->find('toast') !== null, 'a toast confirms it');
$app->clickId('delete');
same('trash', $state->mail[2]['folder'], 'delete moves to Trash');

$app->clickId('folder-starred');
same('starred', $state->folder, 'folders switch');
check($app->find('mail-1') !== null, 'starred mail shows in Starred, wherever it is');
$app->clickId('folder-inbox');
$app->clickId('search');
$app->type('moth');
check($app->find('mail-2') === null && count($visible()) === 0, 'search filters the list (the moth mail is in Trash)');
$app->type('');
$state->search = '';
$app->frame();

$app->clickId('mail-4');
$app->clickId('reply');
same(true, $state->composing, 'Reply opens the compose dialog');
same(['alan@bletchley.uk', 'Re: Lunch on Thursday?'], [$state->to, $state->subject], 'with the address and subject filled in');
check(str_contains($state->body, '> Are you free for lunch'), 'and the original message quoted');
$app->clickId('send');
same(false, $state->composing, 'Send closes the dialog');
same('Re: Lunch on Thursday?', end($state->mail)['subject'], 'the reply lands in Sent');

$app->clickId('compose');
check($app->find('send')->get('focusable') === false, 'Send is disabled until there is a recipient');
$app->clickId('to');
$app->type('dennis@bell-labs.com');
$app->clickId('discard');
same(false, $state->composing, 'Discard closes without sending');
