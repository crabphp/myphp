<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';
require __DIR__ . '/../src/autoload.php';
use PhpUi\App;
use function PhpUi\{col, dataDir, div, slider, state, text, textInput};

$s = state(['a' => '', 'b' => 'second', 'level' => 0, 'menu' => null, 'ticks' => 0, 'once' => 0]);
$app = new App('Interaction', 400, 300, fn () => col()->full()->p(10)->gap(10)
    ->child(textInput('a', $s->a, fn ($v) => $s->a = $v))
    ->child(textInput('b', $s->b, fn ($v) => $s->b = $v))
    ->child(slider($s->level, fn ($v) => $s->level = $v)->w(200)->id('slider'))
    ->child(div()->h(40)->id('area')->onRightClick(fn ($x, $y) => $s->menu = [$x, $y]))
    ->child(text((string) $s->ticks)->id('ticks')));
$app->frame();

// Tab moves between inputs and selects their text.
$app->clickId('a');
$app->type('first');
$app->key(GLFW_KEY_TAB);
$app->type('typed over');
same('typed over', $s->b, 'Tab focuses the next input with its text selected');
$app->key(GLFW_KEY_TAB, GLFW_MOD_SHIFT);
$app->type('!');
same('!', $s->a, 'Shift+Tab goes back');

// Dragging a slider.
$bar = $app->find('slider');
$y = $bar->y + $bar->h / 2;
$app->press($bar->x + 8, $y);
same(0.0, (float) $s->level, 'pressing the left end sets 0');
$app->pointer($bar->x + 8 + ($bar->w - 16) / 2, $y);
same(50.0, (float) $s->level, 'dragging follows the pointer');
$app->release($bar->x + $bar->w, $y);
$app->pointer($bar->x + 8, $y);
same(50.0, (float) $s->level, 'moving after release does not change the value');

// Right click.
$area = $app->find('area');
$app->rightClick($area->x + 5, $area->y + 6);
same([$area->x + 5, $area->y + 6], $s->menu, 'right click reaches onRightClick with the point');

// Timers.
$app->every(0.05, fn () => $s->ticks++);
$app->after(0.05, fn () => $s->once++);
$cancelled = $app->after(0.05, fn () => $s->once += 100);
$app->cancel($cancelled);
$end = microtime(true) + 0.3;
while (microtime(true) < $end) { $app->step(); usleep(10000); }
check($s->ticks >= 3, "every() repeats ({$s->ticks} ticks)");
same(1, $s->once, 'after() runs once and cancel() stops a timer');
same((string) $s->ticks, $app->find('ticks')->text, 'timer changes redraw');

// Window title and data folder.
same($app, $app->title('Renamed'), 'title() changes the window title');
putenv('XDG_DATA_HOME=' . getenv('PHPUI_TEST_TMP') . '/data-home');
$dir = dataDir('my-app');
check(is_dir($dir) && str_ends_with($dir, 'data-home/my-app'), 'dataDir() creates the app folder');
try { dataDir('../escape'); check(false, 'dataDir() rejects paths'); }
catch (InvalidArgumentException) { check(true, 'dataDir() rejects paths'); }
