<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';
require __DIR__ . '/../src/autoload.php';
use PhpUi\App;
use function PhpUi\{col, state, textarea, textInput};

// Input methods: text being composed shows at the caret, underlined, and is not part
// of the value until the input method types it. The window learns where the caret is.

$s = state(['line' => 'xin ', 'note' => "first\nsecond "]);
putenv('PHPUI_NO_RUN=1');
$app = new App('IME', 500, 300, fn () => col()->gap(10)->p(20)
    ->child(textInput('line', $s->line, fn ($v) => $s->line = $v)->w(300))
    ->child(textarea('note', $s->note, fn ($v) => $s->note = $v)->w(300)->rows(3)));
$app->frame();

$app->clickId('line');
$app->key(GLFW_KEY_END);
$app->frame();
same(null, $app->find('line')->composing ?: null, 'nothing is composed before the input method starts');
$plain = PhpUi\Painter::$shared->caretRect;
check($plain !== null, 'the focused input reports its caret to the window');

$app->preedit('chaof', 5);
$app->frame();
same('xin ', $s->line, 'composed text is not typed yet');
same('chaof', $app->find('line')->composing, 'the input shows the composition');
check(PhpUi\Painter::$shared->caretRect[0] > $plain[0], 'the caret moves to the end of the composition');

$app->preedit('chào', 4);
$app->frame();
same('xin ', $s->line, 'a changed composition still is not typed');
$app->type('chào');
$app->frame();
same('xin chào', $s->line, 'the input method types the result');
same('', $app->find('line')->composing, 'typing ends the composition');

$app->preedit('ab', 1);
$app->preedit('');
$app->frame();
same(['xin chào', ''], [$s->line, $app->find('line')->composing], 'an empty composition is cancelled without typing');

$app->clickId('note');
$app->key(GLFW_KEY_END, PHP_OS_FAMILY === 'Darwin' ? GLFW_MOD_SUPER : GLFW_MOD_CONTROL);
$app->preedit('日本', 2);
$app->frame();
same(["first\nsecond ", '日本'], [$s->note, $app->find('note')->composing], 'a textarea composes at its caret too');
$app->type('日本語');
same("first\nsecond 日本語", $s->note, 'and takes the typed result');

$app->clickId('line');
$app->preedit('x');
$app->press(490, 290);
$app->release(490, 290);
$app->frame();
same(null, PhpUi\Painter::$shared->caretRect, 'without a focused input there is no caret to report');
$app->clickId('note');
$app->frame();
same('', $app->find('note')->composing, 'a composition stays with the input it started in');
