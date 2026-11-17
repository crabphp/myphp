<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';

// The music player: demo tracks, playback, seeking, next/previous, repeat, volume, adding files.
$home = getenv('PHPUI_TEST_TMP') . '/player';
putenv("MYPHP_PLAYER_DIR=$home");
putenv('PHPUI_NO_RUN=1');
require __DIR__ . '/../examples/player.php';
use PhpUi\Key;
use PhpSdl\Sound;

if (!Sound::available()) {
    $app->clickId('play');
    check(str_starts_with($state->notice, 'No audio device'), 'without an audio device the player says so');
    return;
}
Sound::masterVolume(0);
$app->frame();
same(3, count($state->tracks), 'three demo tracks are made on first run');
check($state->tracks[0]['length'] > 10, 'track lengths are read from the files');
same(['Night Drive', 'PHP Ensemble'], [$state->tracks[0]['title'], $state->tracks[0]['artist']], 'artist and title come from the file name');

$app->clickId('play');
check($state->playing && $audio->sound->playing(), 'play starts the first track');
same(140, count($state->peaks), 'the waveform is computed from the audio');
$wave = $app->find('wave');
$app->click($wave->x + $wave->w / 2, $wave->y + $wave->h / 2);
check(abs($audio->sound->position() - $state->tracks[0]['length'] / 2) < 1, 'clicking the waveform seeks');
$app->clickId('play');
check(!$state->playing && !$audio->sound->playing(), 'pause stops playback');
$at = $audio->sound->position();
$app->clickId('play');
check($state->playing && abs($audio->sound->position() - $at) < 0.5, 'play resumes where it paused');

$app->clickId('next');
same(1, $state->current, 'next plays the following track');
$app->clickId('previous');
same(0, $state->current, 'previous goes back');
$app->clickId('repeat');
same('one', $state->repeat, 'repeat cycles to one');
$app->clickId('shuffle');
same(true, $state->shuffle, 'shuffle turns on');

// The end of a track moves on: with repeat one, the same track starts again.
$audio->sound->seek($state->tracks[0]['length'] - 0.05);
$ending = $audio->sound;
$end = microtime(true) + 2;
while (microtime(true) < $end && $audio->sound === $ending) { $app->step(); usleep(20000); }
same(0, $state->current, 'repeat one replays the track when it ends');
check($audio->sound->playing(), 'and keeps playing');

$app->key(Key::DOWN);
same(75.0, $state->volume, 'the Down key lowers the volume');
$app->key(Key::SPACE);
same(false, $state->playing, 'Space pauses');

// Dropping a folder adds its audio files once.
mkdir("$home/more");
copy($state->tracks[1]['path'], "$home/more/Someone - Another Song.wav");
file_put_contents("$home/more/notes.txt", 'not music');
$app->drop(["$home/more"], 500, 300);
same(4, count($state->tracks), 'dropping a folder adds its audio files');
same('Another Song', $state->tracks[3]['title'], 'dropped tracks get a title');
$app->drop(["$home/more"], 500, 300);
same(4, count($state->tracks), 'dropping the same folder again adds nothing');
check(is_file("$home/library.json"), 'the library is saved');
$app->clickId('search');
$app->type('another');
check($app->find('track-3') !== null && $app->find('track-0') === null, 'search filters the library');
$audio->sound?->pause();
