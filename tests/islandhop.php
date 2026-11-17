<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';

// Island Hop: terrain from a height map (ray marched), sprite-stacked models, a game.
putenv('PHPUI_NO_RUN=1');
putenv('PHPUI_SCREENSHOTS');
putenv('XDG_DATA_HOME=' . getenv('PHPUI_TEST_TMP') . '/islandhop-data');
require __DIR__ . '/../examples/islandhop.php';

check(is_file($terrainFile) && PhpSdl\Renderer::imageSize($terrainFile) === [MAP, MAP], 'the height map is made');
check(is_file($voxels->file), 'the models are sliced into an atlas');
same([50, 60, 18], [$voxels->models['jet']['w'], $voxels->models['jet']['d'], $voxels->models['jet']['h']], 'the jet is modeled at twice the detail');
check(max($heights) > 0.9 && min($heights) === 0.0, 'the land rises from the sea to peaks');
$trees = array_filter($places, fn ($p) => in_array($p['kind'], ['oak', 'pine', 'house'], true));
check(count($trees) > 300, 'woods and houses stand on the islands');
$p = current($trees);
check(abs($p['z'] + 1 - $groundAt($p['x'], $p['y'])) < 1e-6, 'on the ground, at its height');
same('title', $g->mode, 'it starts at the title');
$reset();
same('play', $g->mode, 'a flight starts');
$g->autopilot = true;
mt_srand(2);
$yaw = $g->yaw;
$low = INF;
for ($i = 0; $i < 1200; $i++) {
    $update(1 / 60);
    $low = min($low, $g->z - $groundAt($g->x, $g->y));
}
check(abs($g->yaw - $yaw) > 0.5, 'the jet turns');
check($low >= 5, 'it keeps above the hills');
check(count($g->trails[0]) > 5, 'contrails stream off the wings');
check($g->drones !== [] || $g->kills > 0, 'drones come');
// Into a hill: the hull takes it.
$high = array_filter($places, fn ($p) => $p['kind'] === 'pine' && $p['z'] > 30);
$p = current($high);
[$g->x, $g->y, $g->hurt, $g->climb] = [$p['x'], $p['y'], 0, 0];
$hp = $g->hp;
$g->z = $groundAt($g->x, $g->y) + 1;
$update(1 / 60);
check($g->hp < $hp && $g->z > $groundAt($g->x, $g->y), 'flying into the ground hurts, and bounces the jet up');
$app->frame();
check($app->find('game') !== null, 'the scene draws');
$g->camera = 'cockpit';
$app->frame();
check(true, 'and from the cockpit');
