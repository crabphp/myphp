<?php

declare(strict_types=1);

require __DIR__ . '/../src/autoload.php';

use PhpUi\{App, Element, Theme};
use function PhpUi\{avatar, badge, button, col, dialog, div, icon, paragraph, row, separator, spacer, state, t, text, textarea, textInput, toast};

// A mail client's interface with sample mail: folders, a searchable list, a reader,
// and a compose dialog. Nothing is sent anywhere; "sent" mail goes to the Sent folder.

$now = time();
$state = state(new class {
    public string $folder = 'inbox';
    public ?int $open = null;
    public string $search = '';
    public bool $composing = false;
    public string $to = '';
    public string $subject = '';
    public string $body = '';
    public string $toast = '';
    /** @var list<array{id: int, folder: string, from: string, email: string, subject: string, body: string, time: int, unread: bool, starred: bool, tag: ?string}> */
    public array $mail = [];
});

$sample = [
    ['Ada Lovelace', 'ada@analytical.engine', 'Notes on the engine', "Hi,\n\nI finished the notes on the Analytical Engine. The part about loops took longer than planned, but I think the example with Bernoulli numbers finally reads well.\n\nCould you take a look before Friday? I attached nothing, because there is no attachment support in this demo.\n\nAda", 1200, true, true, 'Work'],
    ['GitHub', 'noreply@github.com', '[crabphp/myphp] v0.2.0 released', "A new release was published in crabphp/myphp.\n\nmyphp 0.2.0\n- Filament-style components with schema() and bind()\n- Accessibility: keyboard access to every control, VoiceOver on macOS\n- HTTPS, SQLite and Composer packages in built apps\n\nView it on GitHub.", 3600 * 2, true, false, 'Updates'],
    ['Grace Hopper', 'grace@navy.mil', 'Re: the bug in relay 70', "Found it. There was an actual moth in relay 70 of Panel F.\n\nI taped it into the logbook with the note \"First actual case of bug being found\". We should keep it.\n\nGrace", 3600 * 5, false, true, null],
    ['Linus', 'linus@example.org', 'Patch review', "The patch looks fine, but please split the refactoring from the actual fix. Reviewing one big change is how bugs sneak in.\n\nThanks.", 3600 * 26, false, false, 'Work'],
    ['Alan Turing', 'alan@bletchley.uk', 'Lunch on Thursday?', "Are you free for lunch on Thursday? I'd like to talk about the imitation game idea. I think it makes a better question than whether machines can think.\n\nAlan", 3600 * 30, false, false, null],
    ['Figma', 'news@figma.com', 'What\'s new in Figma this month', "Dev Mode updates, faster variables, and a new way to share prototypes.\n\nRead the full changelog on our blog.", 3600 * 50, false, false, 'Updates'],
    ['Margaret Hamilton', 'margaret@mit.edu', 'Apollo guidance: priority display', "The priority display worked as designed during the landing: the computer dropped low-priority jobs and kept the important ones running.\n\nI'll write up the details for the review board.", 3600 * 75, false, true, 'Work'],
    ['Dennis Ritchie', 'dmr@bell-labs.com', 'Re: C book draft', "Brian and I went through chapter 5 again. Pointers still confuse everyone, so we added more pictures.\n\nDennis", 3600 * 98, false, false, null],
];
foreach ($sample as $i => [$from, $email, $subject, $body, $ago, $unread, $starred, $tag]) {
    $state->mail[] = ['id' => $i, 'folder' => 'inbox', 'from' => $from, 'email' => $email, 'subject' => $subject, 'body' => $body,
        'time' => $now - $ago, 'unread' => $unread, 'starred' => $starred, 'tag' => $tag];
}
$state->mail[] = ['id' => 100, 'folder' => 'sent', 'from' => 'You', 'email' => 'me@example.com', 'subject' => 'Re: Lunch on Thursday?',
    'body' => "Thursday works. The usual place at noon?", 'time' => $now - 3600 * 29, 'unread' => false, 'starred' => false, 'tag' => null];
$state->mail[] = ['id' => 101, 'folder' => 'drafts', 'from' => 'You', 'email' => 'me@example.com', 'subject' => 'Ideas for the next release',
    'body' => "- Multiple windows\n- App menus\n- Tray icon", 'time' => $now - 3600 * 3, 'unread' => false, 'starred' => false, 'tag' => null];
// The first message starts open, so it counts as read.
[$state->open, $state->mail[0]['unread']] = [0, false];

const FOLDERS = ['inbox' => ['Inbox', 'inbox'], 'starred' => ['Starred', 'sparkles'], 'sent' => ['Sent', 'arrow-up-right'],
    'drafts' => ['Drafts', 'file-text'], 'archive' => ['Archive', 'folder'], 'trash' => ['Trash', 'trash-2']];

$when = function (int $time): string {
    $age = time() - $time;
    return match (true) {
        $age < 3600 => max(1, intdiv($age, 60)) . 'm',
        date('Y-m-d', $time) === date('Y-m-d') => date('H:i', $time),
        $age < 7 * 86400 => date('D', $time),
        default => date('M j', $time),
    };
};
$index = fn (int $id) => array_search($id, array_column($state->mail, 'id'), true);
$visible = function () use ($state): array {
    $query = mb_strtolower(trim($state->search));
    $list = array_filter($state->mail, fn ($m) => ($state->folder === 'starred' ? $m['starred'] && $m['folder'] !== 'trash' : $m['folder'] === $state->folder)
        && ($query === '' || str_contains(mb_strtolower("{$m['from']} {$m['subject']} {$m['body']}"), $query)));
    usort($list, fn ($a, $b) => $b['time'] <=> $a['time']);
    return array_values($list);
};
$openMail = function (int $id) use ($state, $index) {
    $state->open = $id;
    $state->mail[$index($id)]['unread'] = false;
};
$move = function (int $id, string $folder) use ($state, $index, $visible) {
    $list = array_column($visible(), 'id');
    $at = array_search($id, $list, true);
    $state->mail[$index($id)]['folder'] = $folder;
    // Open the next message, as mail apps do after archiving or deleting.
    $next = $list[$at + 1] ?? $list[$at - 1] ?? null;
    $state->open = $next;
    $state->toast = $folder === 'trash' ? 'Moved to Trash' : 'Archived';
    App::current()?->after(2.5, fn () => $state->toast = '');
};
$compose = function (string $to = '', string $subject = '', string $body = '') use ($state) {
    [$state->to, $state->subject, $state->body, $state->composing] = [$to, $subject, $body, true];
};
$send = function () use ($state) {
    if (trim($state->to) === '') return;
    $state->mail[] = ['id' => 1000 + count($state->mail), 'folder' => 'sent', 'from' => 'You', 'email' => 'me@example.com',
        'subject' => $state->subject === '' ? '(no subject)' : $state->subject, 'body' => $state->body, 'time' => time(),
        'unread' => false, 'starred' => false, 'tag' => null];
    [$state->composing, $state->toast] = [false, 'Message sent'];
    App::current()?->after(2.5, fn () => $state->toast = '');
};
$step = function (int $direction) use ($state, $visible, $openMail) {
    $ids = array_column($visible(), 'id');
    if (!$ids) return;
    $at = array_search($state->open, $ids, true);
    $openMail($ids[$at === false ? 0 : max(0, min(count($ids) - 1, $at + $direction))]);
};

$tagColor = fn (?string $tag) => match ($tag) { 'Work' => t('chart-2'), 'Updates' => t('chart-4'), default => t('muted-foreground') };

$render = function () use ($state, $when, $index, $visible, $openMail, $move, $compose, $send, $tagColor) {
    // Folders
    $folders = col()->gap(2);
    foreach (FOLDERS as $key => [$name, $ico]) {
        $unread = count(array_filter($state->mail, fn ($m) => $m['folder'] === $key && $m['unread']));
        $on = $state->folder === $key;
        $folders->child(row()->itemsCenter()->gap(10)->h(34)->px(10)->rounded(8)->textSize(14)->id("folder-$key")->transition()
            ->when($on, fn ($e) => $e->bg(t('accent'))->medium(), fn ($e) => $e->textColor(t('muted-foreground'))->hover(fn ($s) => $s->bg(t('muted', 0.6))->textColor(t('foreground'))))
            ->onClick(function () use ($state, $key, $visible) { [$state->folder, $state->search] = [$key, '']; $state->open = $visible()[0]['id'] ?? null; })
            ->schema([icon($ico, 16), text($name)->grow(), $unread ? text((string) $unread)->textSize(12)->semibold() : null]));
    }

    // Message list
    $mails = $visible();
    $list = col()->scroll('mail-list')->grow();
    foreach ($mails as $m) {
        $on = $m['id'] === $state->open;
        $list->child(col()->gap(4)->px(16)->py(12)->borderB(1, t('border'))->id("mail-{$m['id']}")->transition()
            ->when($on, fn ($e) => $e->bg(t('accent')), fn ($e) => $e->hover(fn ($s) => $s->bg(t('muted', 0.5))))
            ->onClick(fn () => $openMail($m['id']))->label("{$m['from']}: {$m['subject']}")
            ->schema([
                row()->itemsCenter()->gap(8)->schema([
                    row()->size(8)->rounded(4)->bg($m['unread'] ? t('chart-2') : '#00000000'),
                    text($m['from'])->truncate()->flex1()->when($m['unread'], fn ($e) => $e->semibold()),
                    $m['starred'] ? icon('sparkles', 14, t('chart-4')) : null,
                    text($when($m['time']))->textSize(12)->textColor(t('muted-foreground')),
                ]),
                text($m['subject'])->textSize(13)->truncate()->pl(16)->when($m['unread'], fn ($e) => $e->medium()),
                text(str_replace("\n", ' ', $m['body']))->textSize(12)->textColor(t('muted-foreground'))->truncate()->pl(16),
            ]));
    }
    if (!$mails) $list->child(col()->itemsCenter()->gap(8)->py(60)->schema([icon('inbox', 28, t('muted-foreground')), text($state->search === '' ? 'Nothing here' : 'No matches')->textColor(t('muted-foreground'))]));

    // Reader
    $at = $state->open === null ? false : $index($state->open);
    $m = $at === false ? null : $state->mail[$at];
    $reader = $m === null
        ? col()->flex1()->center()->gap(8)->schema([icon('mail', 32, t('muted-foreground')), text('No message selected')->textColor(t('muted-foreground'))])
        : col()->flex1()->schema([
            row()->itemsCenter()->gap(4)->h(52)->px(16)->borderB(1, t('border'))->schema([
                button()->color('ghost')->size('icon')->icon('folder')->label('Archive')->tooltip('Archive')->onClick(fn () => $move($m['id'], 'archive'))->id('archive'),
                button()->color('ghost')->size('icon')->icon('trash-2')->label('Delete')->tooltip('Delete')->onClick(fn () => $move($m['id'], 'trash'))->id('delete'),
                row()->px(6)->schema([separator(true)->h(20)]),
                button()->color('ghost')->size('icon')->icon('sparkles')->label($m['starred'] ? 'Unstar' : 'Star')->tooltip($m['starred'] ? 'Unstar' : 'Star')
                    ->when($m['starred'], fn ($e) => $e->iconColor(t('chart-4')))
                    ->onClick(function () use ($state, $at) { $state->mail[$at]['starred'] = !$state->mail[$at]['starred']; })->id('star'),
                spacer(),
                button('Reply')->outlined()->size('sm')->icon('arrow-up-right')->id('reply')
                    ->onClick(fn () => $compose($m['email'], str_starts_with($m['subject'], 'Re: ') ? $m['subject'] : "Re: {$m['subject']}",
                        "\n\nOn " . date('M j, H:i', $m['time']) . ", {$m['from']} wrote:\n> " . str_replace("\n", "\n> ", $m['body']))),
            ]),
            col()->gap(20)->p(28)->scroll('reader')->grow()->selectable()->schema([
                row()->itemsCenter()->gap(12)->schema([
                    avatar(implode('', array_map(fn ($w) => mb_substr($w, 0, 1), array_slice(explode(' ', $m['from']), 0, 2))), 40),
                    col()->gap(2)->flex1()->schema([
                        text($m['from'])->semibold(),
                        text("<{$m['email']}>")->textSize(12)->textColor(t('muted-foreground')),
                    ]),
                    text(date('D, M j \a\t H:i', $m['time']))->textSize(12)->textColor(t('muted-foreground')),
                ]),
                row()->itemsCenter()->gap(10)->schema([
                    text($m['subject'])->textSize(20)->bold()->wrap()->flex1()->id('subject'),
                    $m['tag'] ? badge($m['tag'])->color('outline')->textColor($tagColor($m['tag'])) : null,
                ]),
                separator(),
                paragraph($m['body'], null, 15)->id('body'),
            ]),
        ]);

    return row()->full()->bg(t('background'))->schema([
        col()->w(220)->gap(14)->p(14)->bg(t('muted', 0.4))->borderR(1, t('border'))->schema([
            row()->itemsCenter()->gap(8)->px(6)->schema([icon('mail', 18), text('Mail')->textSize(16)->semibold(), spacer(),
                button()->color('ghost')->size('icon-sm')->icon(Theme::dark() ? 'sun' : 'moon')->label('Switch theme')->onClick(fn () => Theme::toggle())]),
            button('New message')->icon('pencil')->onClick(fn () => $compose())->id('compose'),
            $folders,
            spacer(),
            text('Sample mail. Nothing is sent anywhere.')->wrap()->textSize(12)->textColor(t('muted-foreground'))->px(6),
        ]),
        col()->w(340)->borderR(1, t('border'))->schema([
            col()->gap(10)->p(14)->borderB(1, t('border'))->schema([
                row()->itemsCenter()->schema([text(FOLDERS[$state->folder][0])->textSize(18)->bold(), spacer(), text(count($mails) . ' messages')->textSize(12)->textColor(t('muted-foreground'))]),
                textInput('search')->placeholder('Search mail')->prefixIcon('search')->bind($state->search),
            ]),
            $list,
        ]),
        $reader,
    ])->when($state->composing, fn ($e) => $e->child(dialog()->heading('New message')->width(560)
        ->schema([
            textInput('to')->label('To')->placeholder('name@example.com')->bind($state->to),
            textInput('subject')->label('Subject')->bind($state->subject),
            textarea('message')->rows(10)->placeholder('Write your message…')->bind($state->body),
        ])
        ->footer([button('Discard')->outlined()->onClick(fn () => $state->composing = false)->id('discard'),
            button('Send')->icon('arrow-up-right')->iconPosition('after')->disabled(trim($state->to) === '')->onClick($send)->id('send')])
        ->onClose(fn () => $state->composing = false)))
    ->when($state->toast !== '', fn ($e) => $e->child(toast($state->toast)->id('toast')));
};

$app = new App('MyPHP Mail', 1180, 720, $render);
$app->onKey(function (int $key, int $mods) use ($state, $step, $compose) {
    if ($state->composing) return;
    match ($key) {
        GLFW_KEY_J, GLFW_KEY_DOWN => $step(1),
        GLFW_KEY_K, GLFW_KEY_UP => $step(-1),
        GLFW_KEY_C => $compose(),
        default => null,
    };
});

if ($dir = getenv('PHPUI_SCREENSHOTS')) {
    @mkdir($dir, 0777, true);
    $app->screenshot("$dir/mail.png", 1180, 720, 2);
    Theme::set('dark');
    $app->screenshot("$dir/mail-dark.png", 1180, 720, 2);
    Theme::set('light');
    $state->composing = true;
    [$state->to, $state->subject] = ['grace@navy.mil', 'Re: the bug in relay 70'];
    $app->screenshot("$dir/mail-compose.png", 1180, 720, 2);
    exit;
}
if (!getenv('PHPUI_NO_RUN')) $app->run();
