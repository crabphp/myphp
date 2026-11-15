<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';
require __DIR__ . '/../src/autoload.php';
use PhpUi\App;
use function PhpUi\{col, text};
$state = (object) ['element' => null, 'window' => null];
$app = new App('Drop', 400, 300, fn () => col()->full()->child(col()->w(200)->h(100)->id('target')
    ->onDrop(fn ($paths) => $state->element = $paths)->child(text('Drop here'))));
$app->onDrop(fn ($paths, $x, $y) => $state->window = [$paths, $x, $y]);
$paths = ['/tmp/one file', '/tmp/é.png'];
$app->drop($paths, 10, 10);
same($paths, $state->element, 'drop reaches nearest ancestor handler');
same(null, $state->window, 'handled drop does not bubble to window');
$app->drop($paths, 300, 200);
same([$paths, 300.0, 200.0], $state->window, 'empty-space drop reaches window unchanged');
$app->pointer(20, 20);
$state->element = null;
$app->drop(['/tmp/current']);
same(['/tmp/current'], $state->element, 'drop defaults to current pointer');
PhpUi\Desktop::message('Busy', 'A modal blocks window input');
$state->window = null;
$app->drop($paths, 300, 200);
same(null, $state->window, 'drop cannot bypass modal');
