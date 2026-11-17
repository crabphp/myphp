<?php

declare(strict_types=1);

require __DIR__ . '/../src/autoload.php';

use PhpUi\App;
use function PhpUi\{button, card, checkbox, col, row, state, t, tabs, text, textInput};
use PhpUi\Key;

// State lives in plain PHP; render() turns it into a tree on every frame.
$state = state([
    'draft' => '',
    'filter' => 'all',
    'todos' => [
        ['text' => 'Draw boxes on the GPU', 'done' => true],
        ['text' => 'Write a tiny flexbox', 'done' => true],
        ['text' => 'Route clicks by hit-testing', 'done' => false],
    ],
]);

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
        ->schema([
            checkbox()->bind($state->todos[$i]['done'])->id("check-$i"),
            text($t['text'])->grow()->when($t['done'], fn ($e) => $e->textColor(t('muted-foreground'))),
            button()->color('ghost')->size('icon-sm')->icon('x')->label('Delete')
                ->onClick(function () use ($state, $i) { array_splice($state->todos, $i, 1); })
                ->id("delete-$i")->textColor(t('muted-foreground')),
        ]);

    return col()->full()->bg(t('muted'))->p(24)->itemsCenter()->justifyCenter()->schema([
        card()->heading('Todos')->description("$left left")->w(440)->schema([
            row()->gap(8)->schema([
                textInput('draft')->placeholder('What needs doing?')->bind($state->draft)->onSubmit($add)->grow(),
                button('Add')->onClick($add)->id('add'),
            ]),
            col()->h(220)->gap(2)->clip()
                ->schema(array_map($item, array_keys($visible), $visible))
                ->when(!$visible, fn ($e) => $e->center()->child(text('Nothing here')->textColor(t('muted-foreground')))),
            tabs()->options(['all' => 'All', 'open' => 'Open', 'done' => 'Done'])->bind($state->filter)->idPrefix('filter'),
        ]),
    ]);
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
    $app->key(Key::ENTER);
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
