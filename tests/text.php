<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';
require __DIR__ . '/../src/autoload.php';
use PhpUi\{App, Key, Mod, Painter};
use function PhpUi\{col, icon, paragraph, row, state, text, textarea, textInput};

$mod = PHP_OS_FAMILY === 'Darwin' ? Mod::SUPER : Mod::CONTROL;
$word = PHP_OS_FAMILY === 'Darwin' ? Mod::ALT : Mod::CONTROL;
$sentence = 'The quick brown fox jumps over the lazy dog and keeps running far away.';

// State ----------------------------------------------------------------------

$s = state(['count' => 0, 'items' => [], 'line' => '', 'note' => '']);
$s->count++;
$s->count += 2;
$s->items[] = 'a';
$s->items['k'] = 'b';
same(3, $s->count, 'state counts with ++ and +=');
same(['a', 'k' => 'b'], $s->items, 'state arrays change in place');
same(state(['open' => false], 'menu'), state(['open' => true], 'menu'), 'a keyed state is the same store every call');
same(false, state([], 'menu')->open, 'a keyed state keeps its first value');
$s->set(['count' => 10]);
same(10, $s->count, 'set() changes several keys');
check(isset($s->count) && !isset($s->missing), 'isset works on state');
$typed = new class { public int $count = 0; public array $items = []; };
same($typed, state($typed), 'state() returns a typed object as it is');
$first = state(new class { public bool $open = false; }, 'panel');
$first->open = true;
same(true, state(new class { public bool $open = false; }, 'panel')->open, 'a keyed typed state keeps its object');

$sent = state(['value' => null]);
$app = new App('Text', 420, 520, fn () => col()->full()->p(10)->gap(10)
    ->child(text((string) $s->count)->id('count'))
    ->child(col()->w(300)->id('panel')->child(text($sentence)->wrap()->id('wrapped')))
    ->child(row()->w(200)->gap(8)->child(icon('info'))->child(text($sentence)->wrap()->id('row-wrapped')))
    ->child(paragraph($sentence)->id('para'))
    ->child(textInput('line', $s->line, fn ($v) => $s->line = $v)->w(200))
    ->child(textarea('note', $s->note, fn ($v) => $s->note = $v, 'Notes', 3)->w(220)->onSubmit(fn ($v) => $sent->value = $v)));
$app->frame();
$s->count = 42; // outside any handler
same('42', $app->find('count')->text, 'changing state outside a handler redraws');

// Wrapping -------------------------------------------------------------------

$painter = Painter::$shared;
$lh = $painter->lineHeight(14);
$wrapped = $app->find('wrapped');
check(count($wrapped->lines) > 1, 'wrap() breaks long text into lines');
same(count($wrapped->lines) * $lh, $wrapped->h, 'wrapped text is as tall as its lines');
check(max(array_map(fn ($l) => $painter->textWidth($l, 'regular', 14), $wrapped->lines)) <= 300, 'every wrapped line fits its width');
$rowText = $app->find('row-wrapped');
check($rowText->x + $rowText->w <= 210 + 0.5 && count($rowText->lines) > 1, 'wrapped text shrinks and wraps next to an icon in a row');
$para = $app->find('para');
check($para->w === 400.0 && count($para->lines) > 1, 'paragraph() without a width fills its parent');

// Line breaking keeps every character ----------------------------------------

$text = "first line here\n\nthird " . str_repeat('x', 60);
$lines = $painter->breakLines($text, 120);
$covered = '';
foreach ($lines as $i => [$start, $len]) {
    $covered .= mb_substr($text, $start, $len);
    if (isset($lines[$i + 1]) && $lines[$i + 1][0] === $start + $len + 1) $covered .= "\n";
}
same($text, $covered, 'line ranges cover every character and line break');
check(count($lines) > 4, 'a long word splits across lines');

// Single-line editing --------------------------------------------------------

$app->clickId('line');
$app->type('hello world');
same('hello world', $s->line, 'typing appends at the caret');
for ($i = 0; $i < 5; $i++) $app->key(Key::LEFT);
$app->type('X');
same('hello Xworld', $s->line, 'arrow keys move the caret and typing inserts there');
$app->key(Key::HOME, Mod::SHIFT);
$app->type('Hi ');
same('Hi world', $s->line, 'Shift+Home selects to the start and typing replaces the selection');
$app->key(Key::END);
$app->key(Key::BACKSPACE, $word);
same('Hi ', $s->line, 'word backspace deletes the previous word');
$app->key(Key::A, $mod);
$app->key(Key::BACKSPACE);
same('', $s->line, 'select all then backspace clears the input');
$app->type('one two three');
$app->key(Key::LEFT, $word | Mod::SHIFT);
$app->key(Key::C, $mod);
same('three', PhpUi\Desktop::clipboard(), 'copy takes only the selection');
$app->key(Key::X, $mod);
same('one two ', $s->line, 'cut removes only the selection');
$app->key(Key::HOME);
$app->key(Key::DELETE);
same('ne two ', $s->line, 'Delete removes the next character');

// Mouse: click, double-click, drag
$s->line = 'alpha beta gamma';
$line = $app->find('line');
$right = $line->x + $line->w - 14;
$app->click($right, $line->y + $line->h / 2);
$app->type('!');
same('alpha beta gamma!', $s->line, 'clicking after the text puts the caret at the end');
$line = $app->find('line');
$betaX = $line->x + 12 + $painter->textWidth('alpha be', 'regular', 14);
$app->click($betaX, $line->y + $line->h / 2);
$app->click($betaX, $line->y + $line->h / 2);
$app->type('B');
same('alpha B gamma!', $s->line, 'double-click selects a word');
$line = $app->find('line');
$app->press($line->x + 12, $line->y + $line->h / 2);
$app->pointer($line->x + 12 + $painter->textWidth('alpha', 'regular', 14), $line->y + $line->h / 2);
$app->release($line->x + 12 + $painter->textWidth('alpha', 'regular', 14), $line->y + $line->h / 2);
$app->type('Z');
same('Z B gamma!', $s->line, 'dragging selects text');

// Long text scrolls so the caret stays visible
$app->key(Key::A, $mod);
$app->type(str_repeat('wide ', 30));
check($app->find('line')->scrollX > 0, 'a long single line scrolls to keep the caret visible');
$app->key(Key::HOME);
same(0.0, $app->find('line')->scrollX, 'Home scrolls back to the start');

// Undo and redo
$app->key(Key::A, $mod);
$app->type('abc');
$app->type(' ');
$app->type('def');
same('abc def', $s->line, 'typed text before undo');
$app->key(Key::Z, $mod);
same('abc ', $s->line, 'undo removes the last run of typing');
$app->key(Key::Z, $mod);
same('abc', $s->line, 'undo steps back through edits');
$app->key(Key::Z, $mod | Mod::SHIFT);
same('abc ', $s->line, 'Shift+undo redoes');
$app->type('x');
$app->key(Key::Z, $mod | Mod::SHIFT);
same('abc x', $s->line, 'typing clears the redo history');
$s->line = 'reset by the app';
$app->key(Key::Z, $mod);
same('reset by the app', $s->line, 'a value set by the app starts a new history');

// Multi-line editing ---------------------------------------------------------

$app->clickId('note');
$app->type('first');
$app->key(Key::ENTER);
$app->type('second');
same("first\nsecond", $s->note, 'Enter adds a line in a textarea');
$app->key(Key::UP);
$app->type('>');
same("first>\nsecond", $s->note, 'Up moves to the line above, keeping the column');
$app->key(Key::DOWN);
$app->key(Key::END);
$app->type('!');
same("first>\nsecond!", $s->note, 'Down and End move within lines');
$app->key(Key::ENTER, $mod);
same("first>\nsecond!", $sent->value, 'Ctrl/Cmd+Enter submits a textarea');
$app->key(Key::A, $mod);
$app->type($sentence . ' ' . $sentence);
$note = $app->find('note');
check(count($note->lines) > 3, 'a textarea wraps long text');
check($note->scrollY > 0, 'a textarea scrolls to keep the caret visible');
$app->wheel(5);
same(0.0, $app->find('note')->scrollY, 'the mouse wheel scrolls a textarea');
$s->note = "a\nb";
$app->key(Key::ENTER);
same("a\nb\n", $s->note, 'a value changed by the app puts the caret at its end');

$shot = getenv('PHPUI_TEST_TMP') . '/text.png';
$app->screenshot($shot, 420, 520, 1);
check(filesize($shot) > 1000, 'text screen renders');


// Selectable text ------------------------------------------------------------

$copy = 'Copy this sentence, then paste it somewhere else.';
$selApp = new App('Select', 400, 200, fn () => col()->full()->p(10)->gap(10)
    ->child(text('Plain text is not selectable')->id('plain'))
    ->child(text($copy)->wrap()->selectable()->w(200)->id('sel'))
    ->child(col()->selectable()->child(text('inherited')->id('inherited'))));
$selApp->frame();
$sel = $selApp->find('sel');
check(count($sel->ranges) > 1, 'selectable wrapped text keeps character ranges');
$y0 = $sel->y + 10;
$selApp->press($sel->x + 1, $y0);
$selApp->pointer($sel->x + $painter->textWidth('Copy this', 'regular', 14) + 1, $y0);
$selApp->release($sel->x + $painter->textWidth('Copy this', 'regular', 14) + 1, $y0);
same('Copy this', $selApp->selectedText(), 'dragging over selectable text selects it');
$selApp->key(Key::C, $mod);
same('Copy this', PhpUi\Desktop::clipboard(), 'Ctrl/Cmd+C copies the selected text');
$selApp->key(Key::A, $mod);
same($copy, $selApp->selectedText(), 'Ctrl/Cmd+A selects the whole text');
$wordX = $sel->x + $painter->textWidth('Copy th', 'regular', 14);
$selApp->click($wordX, $y0);
$selApp->click($wordX, $y0);
same('this', $selApp->selectedText(), 'double-click selects a word');
$selApp->press($sel->x + 1, $y0);
$selApp->pointer($sel->x + 150, $sel->y + $sel->h - 5);
$selApp->release($sel->x + 150, $sel->y + $sel->h - 5);
check(mb_strlen($selApp->selectedText()) > 25, 'a selection can span wrapped lines');
$plain = $selApp->find('plain');
$selApp->press($plain->x + 2, $plain->y + 8);
$selApp->pointer($plain->x + 80, $plain->y + 8);
$selApp->release($plain->x + 80, $plain->y + 8);
same('', $selApp->selectedText(), 'plain text is not selectable');
check($selApp->find('inherited')->selectable, 'selectable() on a container applies to the text inside');
