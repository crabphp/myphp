<?php

declare(strict_types=1);

// Times the dashboard: building the tree, layout, painting, a whole frame,
// and a pointer move (hover). Usage: php examples/bench-dashboard.php

use PhpUi\Element;

// Load the dashboard without running its loop.
putenv('PHPUI_SCREENSHOTS');
putenv('PHPUI_NO_RUN=1');
require __DIR__ . '/dashboard.php';

$get = fn (string $prop) => (new ReflectionProperty($app, $prop))->getValue($app);
$painter = $get('painter');
$layout = $get('layout');
glfwSwapInterval(0);
[$w, $h] = [1440, 900];

$time = function (callable $fn, int $runs = 200): array {
    for ($i = 0; $i < 10; $i++) $fn();
    $t = [];
    for ($i = 0; $i < $runs; $i++) {
        $s = hrtime(true);
        $fn();
        $t[] = (hrtime(true) - $s) / 1e6;
    }
    sort($t);
    return [$t[intdiv($runs, 2)], $t[(int) ($runs * 0.95)]];
};

$count = function (Element $e) use (&$count): int {
    return 1 + array_sum(array_map($count, $e->children));
};

$app->frame();
$tree = $render();
$layout->compute($tree, $w, $h);

$rows = [
    'build tree' => $time(fn () => $render()),
    'layout' => $time(fn () => $layout->compute($tree, $w, $h)),
    'paint (CPU)' => $time(function () use ($painter, $tree, $w, $h) {
        $painter->vg->beginFrame($w, $h, 1);
        $painter->paint($tree, true);
        $painter->vg->cancelFrame();
    }),
    'whole frame' => $time(fn () => $app->frame()),
    'pointer move' => $time(fn () => $app->pointer(mt_rand(0, $w), mt_rand(0, $h))),
];

printf("PHP %s, JIT %s, %d elements, %dx%d\n", PHP_VERSION,
    (opcache_get_status(false)['jit']['on'] ?? false) ? 'on' : 'off', $count($tree), $w, $h);
printf("%-14s %8s %8s\n", '', 'median', 'p95');
foreach ($rows as $name => [$med, $p95]) printf("%-14s %6.2fms %6.2fms\n", $name, $med, $p95);
printf("%-14s %8.0f\n", 'fps (median)', 1000 / $rows['whole frame'][0]);
printf("memory peak    %6.1f MB\n", memory_get_peak_usage(true) / 1048576);
