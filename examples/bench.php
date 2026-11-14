<?php

declare(strict_types=1);

// Times each stage of a frame for a list of N rows: build tree, layout, paint (CPU side), GPU.
// Usage: php examples/bench.php [rows...]

require __DIR__ . '/../src/autoload.php';

use PhpUi\{App, Layout, Painter};
use function PhpUi\{checkbox, col, div, row, text};

$sizes = array_map('intval', array_slice($argv, 1)) ?: [10, 100, 1000];

$tree = fn (int $n) => col()->full()->bg('#131619')->textColor('#e8e9eb')->p(16)->gap(2)->clip()
    ->children(array_map(fn ($i) => row()->itemsCenter()->gap(12)->h(36)->px(12)->rounded(8)
        ->bg($i % 2 ? '#17191c' : '#1b1e21')->hover(fn ($s) => $s->bg('#ffffff10'))
        ->child(checkbox($i % 3 === 0, fn () => null))
        ->child(text("Row number $i with some text")->grow())
        ->child(div()->size(24)->icon('close', '#6b7078', 8)->onClick(fn () => null)), range(1, $n)));

$app = new App('bench', 800, 600, fn () => $tree(10));
$painter = (new ReflectionProperty($app, 'painter'))->getValue($app);
$layout = new Layout($painter);

$time = function (callable $fn, int $runs): float {
    $fn();
    $t = hrtime(true);
    for ($i = 0; $i < $runs; $i++) $fn();
    return (hrtime(true) - $t) / $runs / 1e6;
};

printf("PHP %s, JIT %s\n", PHP_VERSION, (opcache_get_status(false)['jit']['on'] ?? false) ? 'on' : 'off');
printf("%6s %6s %8s %8s %8s %8s %8s\n", 'rows', 'nodes', 'build', 'layout', 'paint', 'frame', 'fps');
foreach ($sizes as $n) {
    $runs = max(5, intdiv(2000, $n));
    $count = fn ($e) => 1 + array_sum(array_map($GLOBALS['countFn'], $e->children));
    $GLOBALS['countFn'] = $count;
    $root = $tree($n);
    $nodes = $count($root);

    $build = $time(fn () => $tree($n), $runs);
    $lay = $time(fn () => $layout->compute($root, 800, 600), $runs);
    $paint = $time(function () use ($painter, $root) {
        $painter->vg->beginFrame(800, 600, 1);
        $painter->paint($root, true);
        $painter->vg->cancelFrame();
    }, $runs);

    // Whole frame through App, including GPU submit and swap.
    $prop = new ReflectionProperty($app, 'render');
    $prop->setValue($app, fn () => $tree($n));
    glfwSwapInterval(0);
    $frame = $time(fn () => $app->frame(), $runs);

    printf("%6d %6d %7.2fms %7.2fms %7.2fms %7.2fms %8.0f\n", $n, $nodes, $build, $lay, $paint, $frame, 1000 / $frame);
}
