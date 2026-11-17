<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';

// Sky Patrol, the 3D game example: assets made, a flight played by the autopilot, game input.
putenv('PHPUI_NO_RUN=1');
putenv('PHPUI_SCREENSHOTS');
putenv('XDG_DATA_HOME=' . getenv('PHPUI_TEST_TMP') . '/skypatrol-data');
require __DIR__ . '/../examples/skypatrol.php';
use PhpUi\{Key, Mouse};

check(is_file($terrainFile) && PhpSdl\Renderer::imageSize($terrainFile) === [TERRAIN, TERRAIN], 'the archipelago is generated');
same([17, 18, 5], [$voxels->models['jet']['w'], $voxels->models['jet']['d'], $voxels->models['jet']['h']], 'the jet is extruded from text into voxels');
check(is_file($voxels->file), 'the models are sliced into an atlas');
check(count($places) > 100, 'woods, houses and mountains stand on the islands');
same(5, count(array_filter($sounds, 'is_file')), 'sounds are synthesized');

$reset();
same('play', $g->mode, 'a game starts');
$g->autopilot = true;
mt_srand(1);
$frames = 0;
for (; $frames < 3600 && $g->kills < 3; $frames++) $update(1 / 60);
check($g->kills >= 3 && $g->score >= 300, 'enemies come and the guns bring them down');
check($g->particles !== [], 'explosions leave particles');
$app->frame();
check($app->find('game') !== null, 'the game draws');
check($g->toGame === null || count($g->toGame) === 2, 'the mouse is found on the field');

// Held keys, for games.
$emit = new ReflectionMethod($app->window, 'emit');
$emit->invoke($app->window, 'key', Key::LEFT, 0);
check($app->isKeyDown(Key::LEFT), 'a pressed key is held');
$emit->invoke($app->window, 'keyUp', Key::LEFT);
check(!$app->isKeyDown(Key::LEFT), 'and released');
$emit->invoke($app->window, 'button', Mouse::RIGHT, true);
check($app->isMouseDown(Mouse::RIGHT), 'mouse buttons too');
$emit->invoke($app->window, 'focus', false);
check(!$app->isMouseDown(Mouse::RIGHT), 'leaving the window lets go of everything');
