<?php

declare(strict_types=1);

// The MyPHP showcase: a dashboard built from the components (stat cards, an interactive
// chart, a data table with tabs, selection, row menus, a dialog and toasts), and every
// component on its own page, live, with the code that makes it.

require __DIR__ . '/../src/autoload.php';

use PhpUi\{App, Desktop, Element, Key, Theme};
use function PhpUi\{alert, areaChart, avatar, badge, barChart, button, card, checkbox, col, dialog, div, dropdownMenu,
    grid, icon, image, kbd, label, paragraph, progress, row, select, selectTrigger, separator, skeleton, slider,
    spacer, sparkline, state, switchToggle, t, table, tabs, text, textarea, textInput, toast, toggleGroup};

// Data -------------------------------------------------------------------------

mt_srand(7);
$visitors = [];
$day = new DateTimeImmutable('2024-04-01');
for ($i = 0; $i < 91; $i++, $day = $day->modify('+1 day')) {
    $wave = sin($i / 6.5) * 0.35 + sin($i / 2.3) * 0.2;
    $visitors[] = [
        'date' => $day->format('M j'),
        'desktop' => (int) max(60, 260 + 170 * $wave + mt_rand(-70, 90)),
        'mobile' => (int) max(80, 220 + 120 * $wave + mt_rand(-60, 80)),
    ];
}

/** Pages: the dashboard, then one per kind of component. */
$sections = [
    'buttons' => ['Buttons', 'circle-dot', 'Six variants, five sizes, with or without an icon.'],
    'forms' => ['Inputs & forms', 'pencil', 'Real editors: caret, selection, copy and paste, undo, input methods, Tab between fields.'],
    'choices' => ['Choices', 'check', 'Select, checkbox, switch, tabs, toggle group and slider.'],
    'overlays' => ['Overlays', 'panel-left', 'Dialog, dropdown menu, tooltip and toast. They fade in.'],
    'display' => ['Display', 'gallery-vertical-end', 'Cards, badges, avatars, alerts, progress, skeletons, keys and tables.'],
    'layout' => ['Layout', 'layout-dashboard', 'Grids, wrapping rows, wrapped text and images.'],
    'charts' => ['Charts', 'chart-area', 'Area, bar and sparkline charts in theme colors.'],
    'motion' => ['Motion', 'sparkles', 'Transitions between states, and fading in.'],
];

// State ------------------------------------------------------------------------

$state = state(new class {
    public string $page = 'dashboard';
    public string $search = '';
    /** ['Title', 'Description'] while a toast shows. */
    public ?array $toast = null;

    // Dashboard
    public string $range = '90d';
    public string $tab = 'outline';
    public array $selected = [1 => true, 4 => true];
    public ?int $rowMenu = null;
    public bool $adding = false;
    public array $draft = ['header' => '', 'type' => 'Narrative', 'target' => ''];
    public array $outline = [
        ['Cover page', 'Cover page', 'In Process', 18, 5, 'Eddie Lake'],
        ['Table of contents', 'Table of contents', 'Done', 29, 24, 'Eddie Lake'],
        ['Executive summary', 'Narrative', 'Done', 10, 13, 'Eddie Lake'],
        ['Technical approach', 'Narrative', 'Done', 27, 23, 'Jamik Tashpulatov'],
        ['Design', 'Narrative', 'In Process', 2, 16, 'Jamik Tashpulatov'],
        ['Capabilities', 'Narrative', 'In Process', 20, 8, 'Jamik Tashpulatov'],
        ['Integration with existing systems', 'Narrative', 'In Process', 19, 21, 'Jamik Tashpulatov'],
        ['Innovation and Advantages', 'Narrative', 'Done', 25, 26, null],
        ["Overview of EMR's Innovative Solutions", 'Technical content', 'Done', 7, 23, null],
        ['Advanced Algorithms and Machine Learning', 'Narrative', 'Done', 30, 28, null],
    ];

    // Component pages
    public string $name = 'Ada Lovelace';
    public string $email = '';
    public string $bio = "Wrote the first published program.\nLikes engines and poetry.";
    public string $plan = 'pro';
    public bool $agree = true;
    public bool $emails = false;
    public string $account = 'account';
    public string $view = 'grid';
    public float $volume = 40;
    public bool $editing = false;
    public bool $menu = false;
    public bool $lit = false;
    public int $shown = 0;
    public int $clicks = 0;
});

$notify = function (string $title, string $description) use ($state) {
    $state->toast = [$title, $description];
    App::current()?->after(3, fn () => $state->toast = null);
};

// Shell: sidebar and header -----------------------------------------------------

$navItem = fn (string $page, string $ico, string $name) => row()->itemsCenter()->gap(8)->h(32)->px(8)->rounded(6)->textSize(14)
    ->id("nav-$page")->role('button', ['selected' => $state->page === $page])->label($name)
    ->when($state->page === $page, fn ($e) => $e->bg(t('sidebar-accent'))->medium())
    ->hover(fn ($s) => $s->bg(t('sidebar-accent')))
    ->onClick(function () use ($state, $page) { $state->page = $page; })
    ->schema([icon($ico, 16, t('sidebar-foreground')), text($name)->truncate()->grow()]);

$groupLabel = fn (string $name) => row()->itemsCenter()->h(32)->px(8)->textSize(12)->medium()
    ->textColor(t('sidebar-foreground', 0.7))->schema([text($name)]);

$sidebar = fn () => col()->w(256)->p(8)->gap(8)->textColor(t('sidebar-foreground'))->schema([
    row()->itemsCenter()->gap(8)->h(40)->px(6)->schema([
        icon('gallery-vertical-end', 20),
        col()->schema([text('MyPHP')->textSize(16)->semibold(), text('Showcase')->textSize(12)->textColor(t('sidebar-foreground', 0.7))]),
    ]),
    row()->gap(8)->p(8)->pt(4)->schema([
        button('Quick Create')->icon('circle-plus')->onClick(fn () => $state->adding = true)
            ->flex1()->h(32)->justify('start')->px(8)->id('quick-create'),
        button()->outlined()->size('icon-sm')->icon('mail')->label('Inbox')->tooltip('Inbox'),
    ]),
    col()->gap(4)->px(8)->schema([$groupLabel('Overview'), $navItem('dashboard', 'layout-dashboard', 'Dashboard')]),
    col()->gap(4)->px(8)->schema([
        $groupLabel('Components'),
        ...array_map(fn ($key, $section) => $navItem($key, $section[1], $section[0]), array_keys($sections), $sections),
    ]),
    spacer(),
    row()->itemsCenter()->gap(8)->h(48)->px(8)->rounded(8)->hover(fn ($s) => $s->bg(t('sidebar-accent')))->cursor('pointer')->schema([
        avatar('AL', 32, 'lg'),
        col()->flex1()->schema([
            text('Ada Lovelace')->textSize(14)->medium()->truncate(),
            text('ada@example.com')->textSize(12)->textColor(t('muted-foreground'))->truncate(),
        ]),
        icon('ellipsis-vertical', 16),
    ]),
]);

$siteHeader = fn (string $title) => row()->itemsCenter()->gap(8)->h(48)->px(24)->borderB(1, t('border'))->shrink(0)->schema([
    button()->color('ghost')->size('icon-sm')->icon('panel-left')->label('Toggle sidebar'),
    separator(true)->h(16)->self('center'),
    text($title)->textSize(16)->medium(),
    spacer(),
    textInput('search')->placeholder('Search…')->prefixIcon('search')->bind($state->search)->w(220),
    button()->color('ghost')->size('icon-sm')->icon(Theme::dark() ? 'sun' : 'moon')->label('Toggle theme')->tooltip('Toggle theme')
        ->onClick(fn () => Theme::toggle())->id('theme'),
]);

// Dashboard --------------------------------------------------------------------

$statCard = fn (string $title, string $value, string $change, bool $up, string $note, string $sub) => card()
    ->heading(text($title)->textSize(14)->textColor(t('muted-foreground')))
    ->description(text($value)->textSize(30)->semibold()->tracking(-0.5))
    ->headerAction(badge($change)->color('outline')->icon($up ? 'trending-up' : 'trending-down'))
    ->footer([col()->gap(6)->textSize(14)->schema([
        row()->itemsCenter()->gap(8)->medium()->schema([text($note), icon($up ? 'trending-up' : 'trending-down', 16)]),
        text($sub)->textColor(t('muted-foreground')),
    ])])
    ->flex1()->shadow('xs')
    ->when(!Theme::dark(), fn ($e) => $e->gradient(t('card'), Theme::mix(t('card'), t('primary'), 0.05)));

$chartCard = function () use ($state, $visitors) {
    $days = ['90d' => 90, '30d' => 30, '7d' => 7][$state->range];
    $data = array_slice($visitors, -$days);
    $what = ['90d' => 'the last 3 months', '30d' => 'the last 30 days', '7d' => 'the last 7 days'][$state->range];
    return card()
        ->heading(text('Total Visitors')->semibold())
        ->description("Total for $what")
        ->headerAction(toggleGroup()->options(['90d' => 'Last 3 months', '30d' => 'Last 30 days', '7d' => 'Last 7 days'])
            ->bind($state->range)->idPrefix('range'))
        ->schema([areaChart(array_column($data, 'date'), [
            'mobile' => [t('primary'), array_column($data, 'mobile')],
            'desktop' => [t('primary', 0.55), array_column($data, 'desktop')],
        ], $days === 7 ? 7 : 9)->h(250)])
        ->shadow('xs');
};

$rowActions = fn (int $i) => div()->id("actions-$i")->schema([
    button()->color('ghost')->size('icon-sm')->icon('ellipsis-vertical')->label('Row actions')
        ->onClick(function () use ($state, $i) { $state->rowMenu = $state->rowMenu === $i ? null : $i; })
        ->textColor(t('muted-foreground'))->when($state->rowMenu === $i, fn ($e) => $e->bg(t('accent'))),
])->when($state->rowMenu === $i, fn ($e) => $e->child(dropdownMenu([
    ['Edit', null, 'pencil'],
    ['Make a copy', function () use ($state, $i, $notify) {
        $outline = $state->outline;
        array_splice($outline, $i + 1, 0, [$outline[$i]]);
        $state->outline = $outline;
        $notify('Section copied', $outline[$i][0] . ' was duplicated.');
    }, 'copy'],
    ['Favorite', null, 'sparkles', '⌘F'],
    'separator',
    ['Delete', function () use ($state, $i, $notify) {
        $outline = $state->outline;
        $notify('Section deleted', $outline[$i][0] . ' was removed.');
        array_splice($outline, $i, 1);
        $state->outline = $outline;
    }, 'trash-2', 'variant' => 'destructive'],
], fn () => $state->rowMenu = null, 160)->absolute()->top(36)->right(0)->id('row-menu')));

$dataTable = function () use ($state, $rowActions) {
    $all = count($state->outline);
    $picked = count(array_filter($state->selected));
    $allChecked = $picked === $all && $all > 0;
    $rows = [];
    foreach ($state->outline as $i => [$header, $type, $status, $target, $limit, $reviewer]) {
        $done = $status === 'Done';
        $rows[] = [
            'drag' => icon('grip-vertical', 12, t('muted-foreground')),
            'select' => checkbox()->checked(!empty($state->selected[$i]))
                ->onToggle(function () use ($state, $i) { $state->selected = [$i => empty($state->selected[$i])] + $state->selected; })
                ->id("select-$i"),
            'header' => text($header)->truncate()->grow()->medium(),
            'type' => badge($type)->color('outline')->textColor(t('muted-foreground'))->px(6),
            'status' => row()->itemsCenter()->gap(4)->h(22)->px(6)->rounded(6)->border(1, t('border'))->self('center')
                ->textSize(12)->medium()->textColor(t('muted-foreground'))
                ->schema([icon($done ? 'circle-check' : 'loader', 12, $done ? t('success') : t('muted-foreground')), text($status)]),
            'target' => text((string) $target)->textRight()->grow(),
            'limit' => text((string) $limit)->textRight()->grow(),
            'reviewer' => $reviewer !== null
                ? text($reviewer)->truncate()->grow()
                : selectTrigger('Assign reviewer', fn () => null, 'sm')->w(152)->textColor(t('muted-foreground')),
            'actions' => $rowActions($i),
        ];
    }
    $columns = [
        'drag' => ['', 40, 'left'],
        'select' => [checkbox()->checked($allChecked)->id('select-all')
            ->onToggle(function () use ($state, $allChecked) {
                $state->selected = $allChecked ? [] : array_fill(0, count($state->outline), true);
            }), 36, 'left'],
        'header' => ['Header', null, 'left'],
        'type' => ['Section Type', 168, 'left'],
        'status' => ['Status', 136, 'left'],
        'target' => ['Target', 72, 'right'],
        'limit' => ['Limit', 72, 'right'],
        'reviewer' => ['Reviewer', 184, 'left'],
        'actions' => ['', 52, 'right'],
    ];
    $count = fn (int $n) => row()->center()->size(20)->rounded(10)->bg(t('muted-foreground', 0.3))->textSize(12)->medium()->schema([text((string) $n)]);
    $pager = fn (string $ico, string $name, bool $off = false) => button()->outlined()->size('icon-sm')->icon($ico)->label($name)
        ->when($off, fn ($e) => $e->disabled());

    return col()->gap(16)->schema([
        row()->itemsCenter()->justifyBetween()->schema([
            tabs()->options([
                'outline' => 'Outline',
                'past' => row()->itemsCenter()->gap(6)->schema([text('Past Performance'), $count(3)]),
                'people' => row()->itemsCenter()->gap(6)->schema([text('Key Personnel'), $count(2)]),
                'focus' => 'Focus Documents',
            ])->bind($state->tab),
            row()->gap(8)->schema([
                button('Customize Columns')->outlined()->size('sm')->icon('columns-3')->child(icon('chevron-down', 16)),
                button('Add Section')->outlined()->size('sm')->icon('plus')->onClick(fn () => $state->adding = true)->id('add-section'),
            ]),
        ]),
        col()->rounded(10)->border(1, t('border'))->clip()->schema([
            table($columns, $rows, fn ($i) => "row-$i", t('muted'),
                fn (Element $line, int $i) => $line->when(!empty($state->selected[$i]), fn ($e) => $e->bg(t('muted')))),
        ]),
        row()->itemsCenter()->justifyBetween()->px(16)->textSize(14)->schema([
            text("$picked of 68 row(s) selected.")->textColor(t('muted-foreground')),
            row()->itemsCenter()->gap(32)->schema([
                row()->itemsCenter()->gap(8)->schema([label('Rows per page'), selectTrigger('10', fn () => null, 'sm')->w(80)]),
                text('Page 1 of 7')->medium(),
                row()->gap(8)->schema([
                    $pager('chevrons-left', 'First page', true), $pager('chevron-left', 'Previous page', true),
                    $pager('chevron-right', 'Next page'), $pager('chevrons-right', 'Last page'),
                ]),
            ]),
        ]),
    ]);
};

$dashboard = fn () => col()->flex1()->scroll('main')->gap(24)->py(24)->schema([
    row()->gap(16)->px(24)->schema([
        $statCard('Total Revenue', '$1,250.00', '+12.5%', true, 'Trending up this month', 'Visitors for the last 6 months'),
        $statCard('New Customers', '1,234', '-20%', false, 'Down 20% this period', 'Acquisition needs attention'),
        $statCard('Active Accounts', '45,678', '+12.5%', true, 'Strong user retention', 'Engagement exceed targets'),
        $statCard('Growth Rate', '4.5%', '+4.5%', true, 'Steady performance increase', 'Meets growth projections'),
    ]),
    col()->px(24)->schema([$chartCard()]),
    col()->px(24)->schema([$dataTable()]),
]);

$addSection = function () use ($state) {
    $save = function () use ($state) {
        $name = trim($state->draft['header']) ?: 'Untitled section';
        $state->outline = [[$name, $state->draft['type'], 'In Process', (int) $state->draft['target'] ?: 10, 12, null], ...$state->outline];
        $state->adding = false;
        $state->draft = ['header' => '', 'type' => 'Narrative', 'target' => ''];
        $state->toast = ['Section added', "$name is now first in the outline."];
    };
    return dialog()->heading('Add section')->description('Add a section to the proposal outline. You can change it later.')
        ->schema([
            textInput('draft-header')->label('Header')->placeholder('e.g. Pricing')->bind($state->draft['header'])->onSubmit($save),
            row()->gap(16)->schema([
                col()->gap(8)->flex1()->schema([label('Section type'), selectTrigger($state->draft['type'], fn () => null)]),
                textInput('draft-target')->label('Target')->placeholder('10')->bind($state->draft['target'])->flex1(),
            ]),
        ])
        ->footer([button('Cancel')->outlined()->onClick(fn () => $state->adding = false), button('Save section')->onClick($save)->id('save')])
        ->onClose(fn () => $state->adding = false);
};

// Component pages ---------------------------------------------------------------

/** One example: a title with a copy button, the live preview, and its code (selectable). */
$example = fn (string $title, Element $preview, string $code) => col()->rounded(10)->border(1, t('border'))->clip()->bg(t('card'))->schema([
    row()->itemsCenter()->h(40)->px(16)->borderB(1, t('border'))->schema([
        text($title)->medium()->textSize(13),
        spacer(),
        button('Copy')->color('ghost')->size('sm')->icon('copy')->onClick(fn () => Desktop::setClipboard($code))->textColor(t('muted-foreground')),
    ]),
    col()->gap(12)->p(24)->schema([$preview]),
    col()->px(16)->py(12)->bg(t('muted', 0.5))->borderT(1, t('border'))->schema([
        text($code)->wrap()->mono()->textSize(12)->textColor(t('muted-foreground'))->selectable(),
    ]),
]);

$pages = [
    'buttons' => fn () => [
        $example('Variants', row()->flexWrap()->gap(8)->schema([
            button('Primary')->onClick(fn () => $state->clicks++),
            button('Secondary')->color('secondary'),
            button('Outline')->outlined(),
            button('Ghost')->color('ghost'),
            button('Danger')->color('danger'),
            button('Link')->color('link'),
            button('Disabled')->disabled(),
        ]), <<<'PHP'
            button('Primary')->onClick(fn () => $state->clicks++)
            button('Outline')->outlined()
            button('Danger')->color('danger')
            button('Disabled')->disabled()
            PHP),
        $example('Sizes and icons', row()->flexWrap()->itemsCenter()->gap(8)->schema([
            button('Small')->outlined()->size('sm'),
            button('Default')->outlined(),
            button('Large')->outlined()->size('lg'),
            button('New mail')->icon('mail'),
            button('Next')->outlined()->icon('chevron-right')->iconPosition('after'),
            button()->outlined()->size('icon')->icon('settings')->label('Settings')->tooltip('Settings'),
            button()->color('ghost')->size('icon-sm')->icon('trash-2')->label('Delete'),
        ]), <<<'PHP'
            button('New mail')->icon('mail')
            button('Next')->icon('chevron-right')->iconPosition('after')
            button()->size('icon')->icon('settings')->label('Settings')->tooltip('Settings')
            PHP),
        $example('Clicks', row()->itemsCenter()->gap(12)->schema([
            button('Click me')->onClick(fn () => $state->clicks++)->id('click-me'),
            text("Clicked {$state->clicks} times")->textColor(t('muted-foreground')),
        ]), <<<'PHP'
            button('Click me')->onClick(fn () => $state->clicks++)
            text("Clicked {$state->clicks} times")
            PHP),
    ],
    'forms' => fn () => [
        $example('Profile form', grid(2)->gap(16)->schema([
            textInput('name')->label('Name')->bind($state->name),
            textInput('email')->label('Email')->placeholder('you@example.com')->prefixIcon('mail')
                ->helperText('We never share it.')->bind($state->email),
        ]), <<<'PHP'
            grid(2)->gap(16)->schema([
                textInput('name')->label('Name')->bind($state->name),
                textInput('email')->label('Email')->placeholder('you@example.com')
                    ->prefixIcon('mail')->helperText('We never share it.')->bind($state->email),
            ])
            PHP),
        $example('Textarea', col()->gap(8)->schema([
            textarea('bio')->label('Bio')->rows(4)->placeholder('Tell us about you')->bind($state->bio),
            text(mb_strlen($state->bio) . ' characters')->textSize(12)->textColor(t('muted-foreground')),
        ]), <<<'PHP'
            textarea('bio')->label('Bio')->rows(4)->placeholder('Tell us about you')->bind($state->bio)
            PHP),
    ],
    'choices' => fn () => [
        $example('Select', row()->gap(12)->itemsCenter()->schema([
            select('plan')->options(['free' => 'Free', 'pro' => 'Pro', 'team' => 'Team'])->bind($state->plan)->w(180),
            text("Plan: {$state->plan}")->textColor(t('muted-foreground')),
        ]), <<<'PHP'
            select('plan')->options(['free' => 'Free', 'pro' => 'Pro', 'team' => 'Team'])->bind($state->plan)
            PHP),
        $example('Checkbox and switch', col()->gap(12)->schema([
            checkbox()->label('Accept the terms')->bind($state->agree)->id('agree'),
            switchToggle()->label('Email me updates')->bind($state->emails)->id('emails'),
        ]), <<<'PHP'
            checkbox()->label('Accept the terms')->bind($state->agree)
            switchToggle()->label('Email me updates')->bind($state->emails)
            PHP),
        $example('Tabs and toggle group', col()->gap(12)->schema([
            tabs()->options(['account' => 'Account', 'password' => 'Password', 'team' => 'Team'])->bind($state->account),
            toggleGroup()->options(['grid' => 'Grid', 'list' => 'List', 'board' => 'Board'])->bind($state->view)->idPrefix('view'),
        ]), <<<'PHP'
            tabs()->options(['account' => 'Account', 'team' => 'Team'])->bind($state->tab)
            toggleGroup()->options(['grid' => 'Grid', 'list' => 'List'])->bind($state->view)
            PHP),
        $example('Slider', row()->itemsCenter()->gap(16)->schema([
            slider()->bind($state->volume)->w(240)->id('volume'),
            text((int) $state->volume . '%')->mono(),
        ]), <<<'PHP'
            slider()->bind($state->volume)          // 0 to 100
            slider()->min(1)->max(5)->step(1)->bind($state->rating)
            PHP),
    ],
    'overlays' => fn () => [
        $example('Dialog', row()->schema([button('Open dialog')->outlined()->onClick(fn () => $state->editing = true)->id('open-dialog')]), <<<'PHP'
            dialog()->heading('Edit profile')->description('Click save when you are done.')
                ->schema([textInput('name')->label('Name')->bind($state->name)])
                ->footer([button('Save')->onClick(fn () => $state->editing = false)])
                ->onClose(fn () => $state->editing = false)
            PHP),
        $example('Dropdown menu', div()->schema([button('Options')->outlined()->icon('chevron-down')->iconPosition('after')
                ->onClick(fn () => $state->menu = !$state->menu)->id('open-menu')])
            ->when($state->menu, fn ($e) => $e->child(dropdownMenu([
                ['label' => 'My account'],
                ['Profile', null, 'user', 'Shift+Ctrl+P'],
                ['Settings', null, 'settings', 'Ctrl+,'],
                'separator',
                ['Log out', null, 'log-out', null, 'variant' => 'destructive'],
            ], fn () => $state->menu = false)->absolute()->top(40)->left(0)->id('menu'))), <<<'PHP'
            dropdownMenu([['Profile', $open, 'user'], 'separator', ['Log out', $quit, 'log-out']], $close)
                ->absolute()->top(40)->left(0)
            PHP),
        $example('Tooltip', row()->pb(24)->schema([button('Hover me')->outlined()->tooltip('Tooltips show on hover')]), <<<'PHP'
            button('Hover me')->tooltip('Tooltips show on hover')
            PHP),
        $example('Toast', row()->schema([button('Show toast')->outlined()->id('show-toast')
            ->onClick(fn () => $notify('Saved', 'Your changes are safe.'))]), <<<'PHP'
            $app->after(3, fn () => $state->toast = false);
            // in render:
            ->when($state->toast, fn ($e) => $e->child(toast('Saved')->description('Your changes are safe.')))
            PHP),
    ],
    'display' => fn () => [
        $example('Card', card()->w(320)
            ->heading('Team plan')->description('For small teams that ship together.')->headerAction(badge('Popular'))
            ->schema([row()->itemsCenter()->gap(4)->schema([text('$29')->textSize(30)->bold(), text('/ month')->textColor(t('muted-foreground'))])])
            ->footer([button('Choose plan')->grow()]), <<<'PHP'
            card()->heading('Team plan')->description('For small teams.')->headerAction(badge('Popular'))
                ->schema([text('$29')->textSize(30)->bold()])
                ->footer([button('Choose plan')->grow()])
            PHP),
        $example('Badges, avatars and keys', row()->flexWrap()->itemsCenter()->gap(8)->schema([
            badge('Default'), badge('Secondary')->color('secondary'), badge('Danger')->color('danger'), badge('Outline')->color('outline'),
            badge('Verified')->color('secondary')->icon('circle-check'),
            avatar('AL'), avatar('GH', 40, 'rounded'),
            kbd('Ctrl+K'), kbd('Shift+Enter'),
        ]), <<<'PHP'
            badge('Verified')->color('secondary')->icon('circle-check')
            avatar('AL')
            kbd('Ctrl+K')
            PHP),
        $example('Alerts', col()->gap(12)->schema([
            alert('Heads up!')->description('You can select this text and copy it: alerts and messages are selectable.')->icon('info'),
            alert('Payment failed')->description('Your card was declined. Check the details and try again.')->color('danger'),
        ]), <<<'PHP'
            alert('Heads up!')->description('You can select this text.')->icon('info')
            alert('Payment failed')->description('Card declined.')->color('danger')
            PHP),
        $example('Progress, skeleton and separator', col()->gap(16)->schema([
            progress(66)->w(320),
            row()->gap(12)->itemsCenter()->schema([skeleton(40, 40)->rounded(20), col()->gap(8)->schema([skeleton(220, 14), skeleton(160, 14)])]),
            separator(),
            row()->h(20)->gap(12)->itemsCenter()->schema([text('Docs'), separator(true), text('Blog'), separator(true), text('Source')]),
        ]), <<<'PHP'
            progress(66)->w(320)
            skeleton(220, 14)
            separator()
            PHP),
        $example('Table', table(
            ['name' => ['Name', null, 'left'], 'role' => ['Role', 140, 'left'], 'status' => ['Status', 110, 'left']],
            [
                ['name' => 'Ada Lovelace', 'role' => 'Engineer', 'status' => badge('Active')->color('secondary')],
                ['name' => 'Grace Hopper', 'role' => 'Admiral', 'status' => badge('Active')->color('secondary')],
                ['name' => 'Alan Turing', 'role' => 'Researcher', 'status' => badge('Away')->color('outline')],
            ],
        ), <<<'PHP'
            table(
                ['name' => ['Name', null, 'left'], 'role' => ['Role', 140, 'left']],
                [['name' => 'Ada Lovelace', 'role' => 'Engineer']],
            )
            PHP),
    ],
    'layout' => fn () => [
        $example('Grid', grid(3)->gap(12)->schema(array_map(
            fn ($i) => col()->gap(4)->p(16)->rounded(8)->border(1, t('border'))->schema([
                text("Card $i")->medium(),
                paragraph($i === 2 ? 'A longer note that wraps and makes this row taller.' : 'Short.')->textColor(t('muted-foreground')),
            ]),
            [1, 2, 3, 4, 5, 6])), <<<'PHP'
            grid(3)->gap(12)->schema([card()->heading('One'), card()->heading('Two'), ...])
            PHP),
        $example('Wrapping row', row()->flexWrap()->gap(8)->w(420)->schema(array_map(
            fn ($tag) => badge($tag)->color('outline'),
            ['php', 'desktop', 'gpu', 'native', 'no-electron', 'sdl3', 'flexbox', 'shadcn', 'dark-mode', 'single-binary'])), <<<'PHP'
            row()->flexWrap()->gap(8)->schema(array_map(fn ($tag) => badge($tag)->color('outline'), $tags))
            PHP),
        $example('Image', row()->gap(12)->schema([
            image(__DIR__ . '/gallery.jpg')->w(200)->rounded(8),
            image(__DIR__ . '/gallery.jpg')->size(110)->fit('cover')->rounded(55),
        ]), <<<'PHP'
            image(__DIR__ . '/photo.jpg')->w(200)->rounded(8)
            image(__DIR__ . '/photo.jpg')->size(110)->fit('cover')->rounded(55)
            PHP),
    ],
    'charts' => fn () => [
        $example('Area chart', areaChart(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], [
            'Desktop' => [t('chart-1'), [186, 305, 237, 273, 209, 214, 260]],
            'Mobile' => [t('chart-2'), [80, 200, 120, 190, 130, 140, 170]],
        ])->h(220), <<<'PHP'
            areaChart($days, ['Desktop' => [t('chart-1'), $values]])->h(220)
            PHP),
        $example('Bar chart and sparkline', row()->gap(24)->itemsCenter()->schema([
            barChart(['Q1', 'Q2', 'Q3', 'Q4'], [
                '2025' => [t('chart-1'), [120, 160, 140, 190]],
                '2026' => [t('chart-2'), [140, 180, 170, 230]],
            ])->w(320)->h(180),
            sparkline([4, 6, 5, 9, 7, 11, 10, 14], t('chart-2'))->w(160)->h(48),
        ]), <<<'PHP'
            barChart(['Q1', 'Q2'], ['2026' => [t('chart-2'), [140, 180]]])->w(320)->h(180)
            sparkline([4, 6, 5, 9, 14], t('chart-2'))->w(160)->h(48)
            PHP),
    ],
    'motion' => fn () => [
        $example('Transition', row()->itemsCenter()->gap(16)->schema([
            div()->size(64)->rounded(12)->id('lamp')->transition(400)->bg($state->lit ? t('chart-1') : t('muted')),
            button($state->lit ? 'Turn off' : 'Turn on')->outlined()->onClick(fn () => $state->lit = !$state->lit)->id('toggle-lamp'),
        ]), <<<'PHP'
            div()->size(64)->rounded(12)->transition(400)
                ->bg($state->lit ? t('chart-1') : t('muted'))
            PHP),
        $example('Fade in', row()->itemsCenter()->gap(16)->schema([
            button('Show another')->outlined()->onClick(fn () => $state->shown++)->id('show-another'),
            row()->gap(8)->schema(array_map(fn ($i) => avatar((string) $i)->animateIn(400)->id("fade-$i"), range(1, $state->shown % 6 + 1))),
        ]), <<<'PHP'
            avatar('1')->animateIn(400)
            PHP),
    ],
];

$componentPage = function (string $key) use ($sections, $pages) {
    [$title, , $about] = $sections[$key];
    return col()->flex1()->gap(16)->px(32)->py(28)->scroll("page-$key")->schema([
        col()->gap(6)->schema([text($title)->textSize(24)->bold(), paragraph($about)->textColor(t('muted-foreground'))]),
        ...($pages[$key])(),
    ]);
};

// Render -----------------------------------------------------------------------

$render = function () use ($state, $sections, $sidebar, $siteHeader, $dashboard, $componentPage, $addSection) {
    $onDashboard = $state->page === 'dashboard';
    return row()->full()->bg(t('sidebar'))->textColor(t('foreground'))->textSize(14)->schema([
        $sidebar(),
        col()->flex1()->py(8)->pr(8)->schema([
            col()->grow()->rounded(12)->bg(t('background'))->shadow('sm')->clip()
                ->when(Theme::dark(), fn ($e) => $e->border(1, t('border')))
                ->schema([
                    $siteHeader($onDashboard ? 'Documents' : $sections[$state->page][0]),
                    $onDashboard ? $dashboard() : $componentPage($state->page),
                ]),
        ]),
    ])
        ->when($state->adding, fn ($e) => $e->child($addSection()))
        ->when($state->editing, fn ($e) => $e->child(dialog()->heading('Edit profile')->description('Make changes here. Click save when you are done.')
            ->schema([textInput('dialog-name')->label('Name')->bind($state->name)])
            ->footer([button('Cancel')->outlined()->onClick(fn () => $state->editing = false), button('Save')->onClick(fn () => $state->editing = false)->id('dialog-save')])
            ->onClose(fn () => $state->editing = false)))
        ->when($state->toast !== null, fn ($e) => $e->child(toast($state->toast[0])->description($state->toast[1])));
};

$app = new App('MyPHP Showcase', 1440, 900, $render);
$app->onKey(function (int $key) use ($state) {
    if ($key === Key::ESCAPE) [$state->adding, $state->editing, $state->menu, $state->rowMenu] = [false, false, false, null];
});

// Screenshots: PHPUI_SCREENSHOTS=dir myphp run examples/showcase.php ---------------

if ($dir = getenv('PHPUI_SCREENSHOTS')) {
    @mkdir($dir, 0777, true);
    $shot = function (string $name, int $w = 1440, int $h = 1420) use ($app, $dir) {
        $app->screenshot("$dir/$name.png", $w, $h, 2);
        echo "$dir/$name.png\n";
    };
    $app->screenshot("$dir/.warmup.png", 1440, 1420, 1); // first layout, so ids can be found
    unlink("$dir/.warmup.png");
    $app->hoverId('row-2');
    $shot('dashboard-light');
    $app->clickId('range-30d');
    $app->clickId('actions-3');
    $app->hoverId('row-menu');
    $shot('dashboard-menu');
    $app->click(5, 5); // a click outside closes the menu
    $app->clickId('add-section');
    $app->clickId('draft-header');
    $app->type('Pricing and payment terms');
    $shot('dashboard-dialog');
    $app->clickId('save');
    $state->toast = null;
    Theme::set('dark');
    $app->clickId('range-7d');
    $app->pointer(0, 0);
    $shot('dashboard-dark');
    foreach (['light', 'dark'] as $mode) {
        Theme::set($mode);
        foreach (array_keys($sections) as $key) {
            $state->page = $key;
            $shot("components-$key-$mode", 1440, 900);
        }
    }
    Theme::set('light');
    exit;
}

if (!getenv('PHPUI_NO_RUN')) $app->run();
