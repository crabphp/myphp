<?php

declare(strict_types=1);

require __DIR__ . '/../src/autoload.php';

use PhpUi\App;
use function PhpUi\{button, col, dataDir, row, separator, spacer, state, t, text, textarea, textInput};

// Notes are saved as JSON in the app's data folder (MYPHP_NOTES_DIR overrides it for tests).
$file = (getenv('MYPHP_NOTES_DIR') ?: dataDir('myphp-notes')) . '/notes.json';

$state = state(new class {
    /** @var list<array{title: string, body: string, updated: int}> */
    public array $notes = [];
    public int $selected = 0;
    public string $search = '';
    public string $saved = '';
    public bool $dirty = false;
});
$loaded = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
$state->notes = is_array($loaded) && $loaded ? $loaded : [
    ['title' => 'Welcome', 'body' => "Notes is a MyPHP example.\n\nType on the right: text wraps, the caret and selection work like any editor, and Ctrl+Z undoes. Changes save on their own.", 'updated' => time()],
    ['title' => 'Shopping', 'body' => "Coffee\nBread\nSomething sweet", 'updated' => time() - 3600],
];

$save = function () use ($state, $file) {
    if (!$state->dirty) return;
    file_put_contents($file, json_encode($state->notes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $state->dirty = false;
    $state->saved = 'Saved ' . date('H:i:s');
};
/** After an edit: the inputs already wrote the text through bind(). */
$touch = function () use ($state) {
    $state->notes[$state->selected]['updated'] = time();
    $state->dirty = true;
};
$create = function () use ($state) {
    array_unshift($state->notes, ['title' => 'Untitled', 'body' => '', 'updated' => time()]);
    $state->selected = 0;
    $state->search = '';
    $state->dirty = true;
};
$delete = function () use ($state) {
    if (count($state->notes) < 2) return;
    array_splice($state->notes, $state->selected, 1);
    $state->selected = max(0, $state->selected - 1);
    $state->dirty = true;
};

$render = function () use ($state, $touch, $create, $delete) {
    $query = mb_strtolower(trim($state->search));
    $list = col()->gap(2)->scroll('note-list')->grow();
    foreach ($state->notes as $i => $note) {
        if ($query !== '' && !str_contains(mb_strtolower($note['title'] . ' ' . $note['body']), $query)) continue;
        $on = $i === $state->selected;
        $first = trim(strtok($note['body'], "\n") ?: '');
        $list->child(col()->gap(2)->px(10)->py(8)->rounded(8)->id("note-$i")->transition()
            ->when($on, fn ($e) => $e->bg(t('accent')), fn ($e) => $e->hover(fn ($s) => $s->bg(t('muted', 0.6))))
            ->onClick(fn () => $state->selected = $i)
            ->schema([
                text($note['title'] === '' ? 'Untitled' : $note['title'])->medium()->truncate(),
                text($first === '' ? 'Empty note' : $first)->textSize(12)->textColor(t('muted-foreground'))->truncate(),
            ]));
    }

    $note = $state->notes[$state->selected] ?? null;
    $editor = $note === null ? col() : col()->flex1()->gap(12)->p(20)->schema([
        row()->itemsCenter()->gap(8)->schema([
            // bind() reads and writes the note inside the state array directly.
            textInput('title')->placeholder('Title')->bind($state->notes[$state->selected]['title'])->afterStateUpdated($touch)->flex1()->textSize(18),
            button()->color('ghost')->size('icon')->icon('trash-2')->label('Delete note')->onClick($delete)->id('delete'),
        ]),
        textarea('body')->rows(14)->placeholder('Write something…')->bind($state->notes[$state->selected]['body'])->afterStateUpdated($touch)->flex1(),
        row()->textSize(12)->textColor(t('muted-foreground'))->schema([
            text(mb_strlen($note['body']) . ' characters'),
            spacer(),
            text($state->saved)->id('saved'),
        ]),
    ]);

    return row()->full()->bg(t('background'))->schema([
        col()->w(240)->gap(10)->p(12)->bg(t('muted', 0.4))->borderR(1, t('border'))->schema([
            row()->itemsCenter()->schema([
                text('Notes')->textSize(16)->semibold(),
                spacer(),
                button()->color('ghost')->size('icon-sm')->icon('plus')->label('New note')->onClick($create)->id('new'),
            ]),
            textInput('search')->placeholder('Search')->prefixIcon('search')->bind($state->search),
            $list,
        ]),
        $editor,
    ]);
};

$app = new App('Notes', 820, 560, $render);
$app->every(1, $save);
$app->onShutdown($save);

if ($dir = getenv('PHPUI_SCREENSHOTS')) {
    @mkdir($dir, 0777, true);
    $app->frame();
    $app->clickId('body');
    $app->key(GLFW_KEY_END, PHP_OS_FAMILY === 'Darwin' ? GLFW_MOD_SUPER : GLFW_MOD_CONTROL);
    $app->type("\n\nThis line was typed by the screenshot script.");
    $app->screenshot("$dir/notes.png", 820, 560, 2);
    exit;
}
if (!getenv('PHPUI_NO_RUN')) $app->run();
