<?php

declare(strict_types=1);

// Splits one dashboard frame into stages, to find the bottleneck:
// PHP (tree, layout, render submission) and the GPU finishing the frame.
// Usage: php84 examples/bench-stages.php

use PhpUi\Element;

putenv('PHPUI_SCREENSHOTS');
putenv('PHPUI_NO_RUN=1');
require __DIR__ . '/dashboard.php';

$painter = (new ReflectionProperty($app, 'painter'))->getValue($app);
$layout = (new ReflectionProperty($app, 'layout'))->getValue($app);
glfwSwapInterval(0);

$median = function (array $t): float { sort($t); return $t[intdiv(count($t), 2)]; };
$ms = fn (int $from) => (hrtime(true) - $from) / 1e6;

printf("PHP %s, JIT %s, GL: %s\n\n", PHP_VERSION,
    (opcache_get_status(false)['jit']['on'] ?? false) ? 'on' : 'off', glGetString(GL_RENDERER));
printf("%-10s %8s %8s %10s %9s %8s\n", 'size', 'tree', 'layout', 'render', 'GPU', 'total');

foreach (getenv('PHPUI_BENCH_SMALL') ? [[360, 225]] : [[1440, 900], [720, 450], [360, 225]] as [$w, $h]) {
    glfwSetWindowSize($app->window, $w, $h);
    glfwPollEvents();
    $t = ['tree' => [], 'layout' => [], 'render' => [], 'gpu' => []];
    for ($i = 0; $i < max(1, (int) (getenv('PHPUI_BENCH_RUNS') ?: 25)); $i++) {
        $s = hrtime(true); $tree = $render(); $t['tree'][] = $ms($s);
        $s = hrtime(true); $layout->compute($tree, 1440, 900); $t['layout'][] = $ms($s);
        glViewport(0, 0, $w, $h);
        glClear(GL_COLOR_BUFFER_BIT | GL_STENCIL_BUFFER_BIT);
        glFinish();
        // Same UI scaled to the window, so only the pixel count changes.
        $s = hrtime(true);
        $painter->render($tree, 1440, 900, $w / 1440, true);
        $t['render'][] = $ms($s);
        $s = hrtime(true); glFinish(); $t['gpu'][] = $ms($s);
        glfwSwapBuffers($app->window);
    }
    $m = array_map($median, $t);
    printf("%-10s %6.2fms %6.2fms %8.2fms %7.2fms %6.2fms\n", "{$w}x{$h}",
        $m['tree'], $m['layout'], $m['render'], $m['gpu'], array_sum($m));
}
