<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';
require __DIR__ . '/../src/autoload.php';
use PhpUi\{App, Painter};
use function PhpUi\{col, div, row, state, text};

$time = 0.0;
$s = state(['on' => false, 'menu' => false]);
$app = new App('Animation', 300, 200, fn () => col()->full()->bg('#ffffff')->p(10)->gap(10)
    ->child(div()->size(40)->id('box')->bg($s->on ? '#ff0000' : '#0000ff')->transition(100))
    ->child(row()->id('hover-row')->h(30)->textColor('#000000')->hover(fn ($x) => $x->textColor('#ff0000'))->child(text('hover me')->id('label')))
    ->when($s->menu, fn ($e) => $e->child(div()->size(20)->bg('#00ff00')->id('menu')->animateIn(100))));
Painter::$shared->clock = function () use (&$time) { return $time; };
$app->frame();
$painted = fn (string $id, string $key) => $app->find($id)->painted[$key] ?? null;
same('#0000ffff', $painted('box', 'bg'), 'a transition starts at the current color');

$s->on = true;
$time = 0.05;
$app->frame(); // the change is seen here, so the transition starts here
$time = 0.1;
$app->frame();
$mid = $painted('box', 'bg');
check($mid !== '#ff0000ff' && $mid !== '#0000ffff', "halfway through, the color is between ($mid)");
check(Painter::$shared->animating, 'the painter reports a running animation');
$time = 0.3;
$app->frame();
same('#ff0000ff', $painted('box', 'bg'), 'the transition ends at the new color');
check(!Painter::$shared->animating, 'nothing animates when it is done');

$s->menu = true;
$time = 1.0;
$app->frame();
same(0.0, round($painted('menu', 'opacity'), 3), 'animateIn starts transparent');
$time = 1.05;
$app->frame();
$o = $painted('menu', 'opacity');
check($o > 0 && $o < 1, "animateIn fades in ($o)");
$time = 1.2;
$app->frame();
same(null, $painted('menu', 'opacity'), 'animateIn ends fully visible');

$app->hoverId('hover-row');
$app->frame();
same('#ff0000', $painted('label', 'color'), "a container's hover text color reaches its text");

// Screenshots show final states, not a frame in the middle of an animation.
$s->on = false;
$time = 2.0;
$app->frame();
$shot = getenv('PHPUI_TEST_TMP') . '/animation.png';
$app->screenshot($shot, 300, 200, 1);
$box = $app->find('box');
same('#0000ff', pixel($shot, (int) ($box->x + 20), (int) ($box->y + 20)), 'a screenshot shows the end of a transition');
