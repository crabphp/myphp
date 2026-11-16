<?php

declare(strict_types=1);

// Run with myphp run.
use PhpUi\App;
use function PhpUi\{button, card, cardContent, cardHeader, col, state, t, text};

$state = state(new class {
    public int $count = 0;
});
$render = fn () => col()->full()->p(24)->bg(t('background'))->center()
    ->child(card(
        cardHeader('{{name}}'),
        cardContent(col()->gap(12)
            ->child(text('Count: ' . $state->count))
            ->child(button('Increment', fn () => $state->count++))),
    )->w(320));

(new App('{{name}}', 480, 320, $render))->run();
