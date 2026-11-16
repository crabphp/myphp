<?php

declare(strict_types=1);

// Run with myphp run.
use PhpUi\App;
use function PhpUi\{button, card, col, state, t, text};

$state = state(new class {
    public int $count = 0;
});
$render = fn () => col()->full()->p(24)->bg(t('background'))->center()->schema([
    card()->heading('{{name}}')->w(320)->schema([
        text('Count: ' . $state->count),
        button('Increment')->onClick(fn () => $state->count++),
    ]),
]);

(new App('{{name}}', 480, 320, $render))->run();
