<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';

// The notes example as a user would use it: edit, search, create, delete, and it saves.
$notesDir = getenv('PHPUI_TEST_TMP') . '/notes';
mkdir($notesDir);
putenv("MYPHP_NOTES_DIR=$notesDir");
putenv('PHPUI_NO_RUN=1');
require __DIR__ . '/../examples/notes.php';
use PhpUi\{Key, Mod};
$mod = PHP_OS_FAMILY === 'Darwin' ? Mod::SUPER : Mod::CONTROL;

$app->frame();
$app->clickId('new');
same('Untitled', $state->notes[0]['title'], 'the plus button adds a note at the top');
$app->clickId('title');
$app->key(Key::A, $mod);
$app->type('Groceries');
$app->clickId('body');
$app->type("Eggs\nMilk");
same(['Groceries', "Eggs\nMilk"], [$state->notes[0]['title'], $state->notes[0]['body']], 'editing changes the selected note');

$end = microtime(true) + 1.5;
while (microtime(true) < $end && !is_file("$notesDir/notes.json")) { $app->step(); usleep(20000); }
$saved = json_decode((string) @file_get_contents("$notesDir/notes.json"), true);
same('Groceries', $saved[0]['title'] ?? null, 'notes save to disk on their own');
check(str_starts_with($app->find('saved')->text, 'Saved '), 'the footer shows when it saved');

$app->clickId('search');
$app->type('coffee');
check($app->find('note-0') === null && $app->find('note-2') !== null, 'search filters the list');
$app->key(Key::A, $mod);
$app->key(Key::BACKSPACE);
$app->clickId('note-2');
same(2, $state->selected, 'clicking a note selects it');
$app->clickId('delete');
same(2, count($state->notes), 'delete removes the selected note');
