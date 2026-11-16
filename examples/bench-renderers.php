<?php

declare(strict_types=1);

// Compares the three renderers on the dashboard: CPU time to issue a frame
// (PHP paint walk plus NanoVG/GL submission) and time for the GPU to finish it.
// Usage: php84 examples/bench-renderers.php

putenv('PHPUI_SCREENSHOTS');
putenv('PHPUI_NO_RUN=1');
require __DIR__ . '/dashboard.php';

$painter = (new ReflectionProperty($app, 'painter'))->getValue($app);
$layout = (new ReflectionProperty($app, 'layout'))->getValue($app);
$app->window->vsync(false);

$median = function (array $t): float { sort($t); return $t[intdiv(count($t), 2)]; };
[$w, $h] = getenv('PHPUI_BENCH_SMALL') ? [360, 225] : [1440, 900];
$app->window->setSize($w, $h);
$app->window->waitEvents(0);
[$fbw, $fbh] = $app->window->framebufferSize();

$tree = $render();
$layout->compute($tree, $w, $h);
$app->frame(); // ids, hover state

printf("PHP %s, JIT %s, GL: %s, %dx%d\n\n", PHP_VERSION,
    (opcache_get_status(false)['jit']['on'] ?? false) ? 'on' : 'off', glGetString(GL_RENDERER), $fbw, $fbh);
printf("%-12s %10s %10s %10s %8s\n", 'renderer', 'CPU', 'GPU', 'frame', 'fps');

$results = [];
foreach (['nanovg', 'nanovg-fast', 'quads'] as $mode) {
    $painter->mode = $mode;
    $cpu = $gpu = [];
    for ($i = 0; $i < max(1, (int) (getenv('PHPUI_BENCH_RUNS') ?: 30)); $i++) {
        glViewport(0, 0, $fbw, $fbh);
        glClear(GL_COLOR_BUFFER_BIT | GL_STENCIL_BUFFER_BIT);
        glFinish();
        $s = hrtime(true);
        $painter->render($tree, $w, $h, $fbw / $w, true);
        $cpu[] = (hrtime(true) - $s) / 1e6;
        $s = hrtime(true);
        glFinish();
        $gpu[] = (hrtime(true) - $s) / 1e6;
        $app->window->swap();
    }
    if ($i > 5) [$cpu, $gpu] = [array_slice($cpu, 5), array_slice($gpu, 5)];
    [$c, $g] = [$median($cpu), $median($gpu)];
    printf("%-12s %8.2fms %8.2fms %8.2fms %8.0f\n", $mode, $c, $g, $c + $g, 1000 / ($c + $g));
    $results[$mode] = $c + $g;
}
printf("\nquads vs nanovg: %.1fx faster\n", $results['nanovg'] / $results['quads']);
