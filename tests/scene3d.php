<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';
require __DIR__ . '/../src/autoload.php';

// Camera3D and Voxels: projecting, unprojecting, wrapping, and slicing models into an atlas.
use PhpUi\{Camera3D, Voxels};

$cam = new Camera3D(0, 0, 50, 0, 0.6, 400, 300, 200);
$p = $cam->project(10, -120, 0);
check($p !== null && $p[3] > 0, 'a point ahead projects');
$back = $cam->unproject($p[0], $p[1], 0);
check(abs($back[0] - 10) < 1e-6 && abs($back[1] + 120) < 1e-6, 'and unprojects back onto the ground');
same(null, $cam->project(0, 100, 0), 'a point behind the camera does not');
$ahead = $cam->project(0, -200, 50);
check(abs($ahead[0] - 300) < 1e-9 && $ahead[1] < 200, 'straight ahead is on the center line, above the center when level with the camera');
check(abs($cam->horizon() - (200 - 400 * tan(0.6))) < 1e-9, 'the horizon follows the pitch');
$rolled = new Camera3D(0, 0, 50, 0, 0.6, 400, 300, 200, 0, 0.3);
$q = $rolled->project(10, -120, 0);
$back = $rolled->unproject($q[0], $q[1], 0);
check(abs($back[0] - 10) < 1e-6 && abs($back[1] + 120) < 1e-6, 'rolled, too');
$turned = new Camera3D(0, 0, 50, M_PI / 2, 0, 400, 300, 200);
check($turned->project(100, 0, 0) !== null && $turned->project(-100, 0, 0) === null, 'yaw turns the view (π/2 looks east)');
$wrapped = new Camera3D(0, 0, 50, 0, 0.6, 400, 300, 200, 1000);
same($wrapped->project(10, -120, 0), $wrapped->project(1010, 880, 0), 'a wrapping world repeats');

$file = (getenv('PHPUI_TEST_TMP') ?: sys_get_temp_dir()) . '/voxels/models.png';
@unlink($file);
$voxels = new Voxels($file, [
    'box' => ['size' => [3, 2, 4], 'lit' => false, 'voxel' => fn ($x, $y, $z) => '#ff0000'],
    'lit' => ['size' => [3, 3, 3], 'voxel' => fn ($x, $y, $z) => '#808080'],
    'post' => ['size' => [1, 1, 6], 'unit' => 2, 'voxel' => fn ($x, $y, $z) => $z < 3 ? '#00ff00' : null],
]);
check($voxels->built && is_file($file), 'the atlas is made');
same([24, 6], PhpSdl\Renderer::imageSize($file), 'a row a model, a top and a side image a layer');
same(2.0, $voxels->models['post']['unit'], 'models keep their size in world units');
same(str_repeat("\xff\0\0\xff", 6), $voxels->layer('box', 0), 'a layer\'s top is the voxels\' colors');
$again = new Voxels($file, ['box' => ['size' => [3, 2, 4], 'voxel' => fn () => throw new LogicException('rebuilt')],
    'lit' => ['size' => [3, 3, 3], 'voxel' => fn () => null], 'post' => ['size' => [1, 1, 6], 'unit' => 2, 'voxel' => fn () => null]]);
$top = $voxels->layer('lit', 2);
check(ord($top[0]) > ord($top[8 * 4 + 0]), 'lit by the sun from the north-west: that corner is brighter than the far one');
check(!$again->built && $again->layer('box', 0) === null, 'an existing atlas is reused, not rebuilt');
