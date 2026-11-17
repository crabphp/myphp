<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';

// Island Hop, the sprite-stacking example: voxel models sliced into an atlas, a flight.
putenv('PHPUI_NO_RUN=1');
putenv('PHPUI_SCREENSHOTS');
putenv('XDG_DATA_HOME=' . getenv('PHPUI_TEST_TMP') . '/islandhop-data');
require __DIR__ . '/../examples/islandhop.php';

check(is_file($atlasFile) && is_file($groundFile), 'the atlas of slices and the ground are made');
same(7, count($models), 'seven voxel models');
same(28, count($models['lighthouse']['layers']), 'a model has a slice per layer');
check(count($places) > 50, 'trees, houses and rocks stand on the land');
$g->autopilot = true;
mt_srand(2);
$yaw = $g->yaw;
for ($i = 0; $i < 600; $i++) $update(1 / 60);
check(abs($g->yaw - $yaw) > 0.5, 'the jet turns');
check($g->drones !== [], 'drones come');
$app->frame();
check($app->find('game') !== null, 'the scene draws');
