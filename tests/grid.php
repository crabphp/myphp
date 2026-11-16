<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';
require __DIR__ . '/../src/autoload.php';
use PhpUi\App;
use function PhpUi\{col, div, grid, row, text};

$long = 'This card has a longer description that needs more than one line.';
$app = new App('Grid', 600, 700, fn () => col()->full()->p(20)->gap(20)
    ->child(grid(3)->gap(10)->id('grid')->children([
        div()->h(40)->id('g0'), div()->h(60)->id('g1'), div()->h(40)->id('g2'),
        div()->h(30)->id('g3'), col()->id('g4')->child(text($long)->wrap()->id('g4-text')),
    ]))
    ->child(row()->flexWrap()->gap(8)->w(300)->id('tags')->children(array_map(
        fn ($i) => div()->w(90)->h(24)->id("tag$i"), range(0, 6))))
    ->child(row()->flexWrap()->gap(8)->w(300)->justify('center')->id('centered')->children([
        div()->w(100)->h(20)->id('c0'), div()->w(100)->h(20)->id('c1')])));
$app->frame();
$box = fn (string $id) => (fn ($e) => [$e->x, $e->y, $e->w, $e->h])($app->find($id));

// Grid: 560 wide, gap 10 -> columns of 180.
same([20.0, 20.0, 180.0, 40.0], $box('g0'), 'grid cells are equal columns; a fixed height stays');
same(60.0, $app->find('g1')->h, 'the tallest cell sets the row height');
same([210.0, 20.0, 180.0], array_slice($box('g1'), 0, 3), 'second column');
same([20.0, 90.0, 180.0], array_slice($box('g3'), 0, 3), 'the fourth cell starts the second row');
$text = $app->find('g4-text');
check(count($text->lines) > 1, 'wrapped text in a grid cell wraps to the column width');
same(count($text->lines) * 20.0, $app->find('g4')->h, 'a cell without a height stretches to its row');
$grid = $app->find('grid');
same(60.0 + 10 + $app->find('g4')->h, $grid->h, 'the grid is as tall as its rows');

// Wrapping row: 300 wide, 90 + 8 gap -> three per line.
same([20.0, $app->find('tags')->y], array_slice($box('tag0'), 0, 2), 'tags start at the left');
same($app->find('tag0')->y + 24 + 8, $app->find('tag3')->y, 'the fourth tag moves to the next line');
same(3 * 24 + 2 * 8.0, $app->find('tags')->h, 'a wrapping row is as tall as its lines');
same(20.0 + (300 - 208) / 2, $app->find('c0')->x, 'justify centers each line');
