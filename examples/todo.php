<?php

declare(strict_types=1);

require __DIR__ . '/../src/autoload.php';

use PhpUi\App;
use function PhpUi\{button, card, cardContent, cardHeader, checkbox, col, row, t, tabs, text, textInput};

// State lives in plain PHP; render() turns it into a tree on every frame.
$state = (object) [
    'draft' => '',
    'filter' => 'all',
    'todos' => [
        ['text' => 'Draw boxes with NanoVG', 'done' => true],
        ['text' => 'Write a tiny flexbox', 'done' => true],
        ['text' => 'Route clicks by hit-testing', 'done' => false],
    ],
];

$add = function () use ($state) {
    $text = trim($state->draft);
    if ($text === '') return;
    $state->todos[] = ['text' => $text, 'done' => false];
    $state->draft = '';
};

$render = function () use ($state, $add) {
    $visible = array_filter($state->todos, fn ($t) => match ($state->filter) {
        'open' => !$t['done'],
        'done' => $t['done'],
        default => true,
    });
    $left = count(array_filter($state->todos, fn ($t) => !$t['done']));

    $item = fn (int $i, array $t) => row()->itemsCenter()->gap(12)->h(40)->px(8)->rounded(8)
        ->hover(fn ($s) => $s->bg(t('muted', 0.5)))
        ->child(checkbox($t['done'], function () use ($state, $i) {
            $state->todos[$i]['done'] = !$state->todos[$i]['done'];
        })->id("check-$i"))
        ->child(text($t['text'])->grow()->when($t['done'], fn ($e) => $e->textColor(t('muted-foreground'))))
        ->child(button('', function () use ($state, $i) { array_splice($state->todos, $i, 1); }, 'ghost', 'icon-sm', 'x')
            ->id("delete-$i")->textColor(t('muted-foreground')));

    return col()->full()->bg(t('muted'))->p(24)->itemsCenter()->justifyCenter()
        ->child(card(
            cardHeader('Todos', "$left left"),
            cardContent(col()->gap(16)
                ->child(row()->gap(8)
                    ->child(textInput('draft', $state->draft, function (string $v) use ($state) { $state->draft = $v; }, 'What needs doing?')
                        ->grow()->onSubmit($add))
                    ->child(button('Add', $add)->id('add')))
                ->child(col()->h(220)->gap(2)->clip()
                    ->children(array_map($item, array_keys($visible), $visible))
                    ->when(!$visible, fn ($e) => $e->center()->child(text('Nothing here')->textColor(t('muted-foreground')))))
                ->child(tabs(['all' => 'All', 'open' => 'Open', 'done' => 'Done'], $state->filter,
                    function (string $v) use ($state) { $state->filter = $v; }, 'filter'))),
        )->w(440));
};

$app = new App('PHP UI: Todos', 640, 560, $render);

if ($dir = getenv('PHPUI_SCREENSHOTS')) {
    // Headless check: drive the UI like a user and save a PNG of each step.
    @mkdir($dir, 0777, true);
    $shot = fn (string $name) => $app->screenshot("$dir/$name.png", 640, 560, 2);
    $shot('todo-1-start');
    $app->clickId('draft');
    $app->type('Ship a prototype');
    $app->hoverId('add');
    $shot('todo-2-typing');
    $app->key(GLFW_KEY_ENTER);
    $app->clickId('check-2');
    $app->hoverId('delete-3');
    $shot('todo-3-added');
    $app->clickId('delete-0');
    $app->clickId('filter-open');
    $app->pointer(0, 0);
    $shot('todo-4-filter-open');
    $shown = count(array_filter(array_keys($state->todos), fn ($i) => $app->find("check-$i")));
    echo json_encode(['todos' => $state->todos, 'filter' => $state->filter, 'shown' => $shown]), "\n";
    exit;
}

$app->run();
