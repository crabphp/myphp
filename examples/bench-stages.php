<?php

declare(strict_types=1);

// Splits one dashboard frame into stages, to find the bottleneck:
// PHP (tree, layout, render submission) and showing the frame (swap).
// Usage: php84 examples/bench-stages.php

use PhpUi\Element;

putenv('PHPUI_SCREENSHOTS');
putenv('PHPUI_NO_RUN=1');
require __DIR__ . '/showcase.php';

$painter = (new ReflectionProperty($app, 'painter'))->getValue($app);
$layout = (new ReflectionProperty($app, 'layout'))->getValue($app);
$app->window->vsync(false);

$median = function (array $t): float { sort($t); return $t[intdiv(count($t), 2)]; };
$ms = fn (int $from) => (hrtime(true) - $from) / 1e6;

printf("PHP %s, JIT %s, drawing with %s\n\n", PHP_VERSION,
    (opcache_get_status(false)['jit']['on'] ?? false) ? 'on' : 'off', \PhpSdl\Sdl::backend());
printf("%-10s %8s %8s %10s %9s %8s\n", 'size', 'tree', 'layout', 'render', 'swap', 'total');

foreach (getenv('PHPUI_BENCH_SMALL') ? [[360, 225]] : [[1440, 900], [720, 450], [360, 225]] as [$w, $h]) {
    $app->window->setSize($w, $h);
    $app->window->waitEvents(0);
    $t = ['tree' => [], 'layout' => [], 'render' => [], 'swap' => []];
    for ($i = 0; $i < max(1, (int) (getenv('PHPUI_BENCH_RUNS') ?: 25)); $i++) {
        $s = hrtime(true); $tree = $render(); $t['tree'][] = $ms($s);
        $s = hrtime(true); $layout->compute($tree, 1440, 900); $t['layout'][] = $ms($s);
        $app->window->begin([1, 1, 1, 1]);
        // Same UI scaled to the window, so only the pixel count changes.
        $s = hrtime(true);
        $painter->render($tree, 1440, 900, $w / 1440, true);
        $t['render'][] = $ms($s);
        $s = hrtime(true); $app->window->swap(); $t['swap'][] = $ms($s);
    }
    $m = array_map($median, $t);
    printf("%-10s %6.2fms %6.2fms %8.2fms %7.2fms %6.2fms\n", "{$w}x{$h}",
        $m['tree'], $m['layout'], $m['render'], $m['swap'], array_sum($m));
}
