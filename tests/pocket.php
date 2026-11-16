<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';

// The pocket player example: turning the click wheel, its buttons and the keyboard.
putenv('PHPUI_NO_RUN=1');
putenv('PHPUI_SCREENSHOTS');
putenv('MYPHP_PLAYER_DIR=' . getenv('PHPUI_TEST_TMP') . '/pocket');
require __DIR__ . '/../examples/pocket.php';
$audio->engine?->setMasterVolume(0);
$app->frame();

same(3, count($state->tracks), 'demo songs are made on first run');
$wheel = $app->find('wheel');
[$cx, $cy, $r] = [$wheel->x + $wheel->w / 2, $wheel->y + $wheel->h / 2, 80];
// A quarter turn clockwise from the top: a notch every 20 degrees.
$app->press($cx, $cy - $r);
for ($a = -90; $a <= 0; $a += 5) $app->pointer($cx + $r * cos(deg2rad($a)), $cy + $r * sin(deg2rad($a)));
$app->release($cx + $r, $cy);
same([4, null], [$state->selected['menu'] ?? 0, $state->current], 'turning the wheel moves the highlight, and is not a press');
usleep(300000);
$app->clickId('select');
same(true, $state->shuffle, 'the center chooses the highlighted row');
$app->press($cx + $r, $cy);
$app->release($cx + $r, $cy);
check($state->current !== null && $state->playing, 'the right side of the wheel skips to a song');

$app->press($cx, $cy - $r);
$app->release($cx, $cy - $r);
same('menu', end($state->screens), 'MENU goes back, and stops at the main menu');
$app->key(GLFW_KEY_UP);
$app->key(GLFW_KEY_UP);
$app->key(GLFW_KEY_UP);
$app->key(GLFW_KEY_ENTER);
same('songs', end($state->screens), 'Up and Enter open Songs');
$app->key(GLFW_KEY_DOWN);
$app->key(GLFW_KEY_ENTER);
same(['now', 1], [end($state->screens), $state->current], 'choosing a song plays it and shows it');
$volume = $state->volume;
$app->key(GLFW_KEY_DOWN);
$app->frame();
same([$volume + 4, true], [$state->volume, $app->find('volume') !== null], 'on Now Playing the wheel sets the volume and shows it');
$app->key(GLFW_KEY_ESCAPE);
same('songs', end($state->screens), 'Escape goes back');
$audio->sound?->stop();
