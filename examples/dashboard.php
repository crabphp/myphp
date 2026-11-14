<?php

declare(strict_types=1);

// shadcn's dashboard-01 block, rebuilt with PHP UI: inset sidebar, stat
// cards, an interactive area chart and a data table with tabs, selection,
// row menus, a dialog and a toast.

require __DIR__ . '/../src/autoload.php';

use PhpUi\{App, Element, Theme};
use function PhpUi\{
    areaChart, avatar, badge, button, card, cardContent, cardFooter, cardHeader, checkbox, col, dialog, div,
    dropdownMenu, icon, label, row, selectTrigger, separator, spacer, t, table, tabs, text, textInput, toast,
    toggleGroup, tooltip
};

// Data ------------------------------------------------------------------------

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

$sections = [
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

// State -----------------------------------------------------------------------

$state = (object) [
    'nav' => 'Dashboard',
    'range' => '90d',
    'tab' => 'outline',
    'selected' => [1 => true, 4 => true],
    'menu' => null,     // row index whose actions menu is open
    'dialog' => false,
    'draft' => ['header' => '', 'type' => 'Narrative', 'target' => ''],
    'toast' => null,
    'search' => '',
    'sections' => $sections,
];

// Pieces ----------------------------------------------------------------------

$navItem = fn (string $ico, string $name) => row()->itemsCenter()->gap(8)->h(32)->px(8)->rounded(6)->textSize(14)
    ->id('nav-' . strtolower($name))
    ->when($state->nav === $name, fn ($e) => $e->bg(t('sidebar-accent'))->medium())
    ->hover(fn ($s) => $s->bg(t('sidebar-accent')))
    ->onClick(function () use ($state, $name) { $state->nav = $name; })
    ->child(icon($ico, 16, t('sidebar-foreground')))
    ->child(text($name)->truncate()->grow());

$groupLabel = fn (string $name) => row()->itemsCenter()->h(32)->px(8)->textSize(12)->medium()
    ->textColor(t('sidebar-foreground', 0.7))->child($name);

$sidebar = fn () => col()->w(256)->p(8)->gap(8)->textColor(t('sidebar-foreground'))
    ->child(row()->itemsCenter()->gap(8)->h(40)->px(6)
        ->child(icon('gallery-vertical-end', 20))
        ->child(text('Acme Inc.')->textSize(16)->semibold()))
    ->child(col()->gap(8)->p(8)->pt(4)
        ->child(row()->gap(8)
            ->child(button('Quick Create', fn () => $state->dialog = true, icon: 'circle-plus')
                ->flex1()->h(32)->justify('start')->px(8)->id('quick-create'))
            ->child(button('', null, 'outline', 'icon-sm', 'mail')
                ->child(tooltip('Inbox')))))
    ->child(col()->gap(4)->px(8)
        ->child($navItem('layout-dashboard', 'Dashboard'))
        ->child($navItem('list', 'Lifecycle'))
        ->child($navItem('chart-column', 'Analytics'))
        ->child($navItem('folder', 'Projects'))
        ->child($navItem('users', 'Team')))
    ->child(col()->gap(4)->px(8)
        ->child($groupLabel('Documents'))
        ->child($navItem('database', 'Data Library'))
        ->child($navItem('clipboard-list', 'Reports'))
        ->child($navItem('file', 'Word Assistant'))
        ->child(row()->itemsCenter()->gap(8)->h(32)->px(8)->rounded(6)->textSize(14)
            ->textColor(t('sidebar-foreground', 0.7))->hover(fn ($s) => $s->bg(t('sidebar-accent')))->cursor('pointer')
            ->child(icon('ellipsis', 16, t('sidebar-foreground', 0.7)))->child('More')))
    ->child(spacer())
    ->child(col()->gap(4)->px(8)
        ->child($navItem('settings', 'Settings'))
        ->child($navItem('circle-help', 'Get Help'))
        ->child($navItem('search', 'Search')))
    ->child(row()->itemsCenter()->gap(8)->h(48)->px(8)->rounded(8)->hover(fn ($s) => $s->bg(t('sidebar-accent')))->cursor('pointer')
        ->child(avatar('CN', 32, 'lg'))
        ->child(col()->flex1()
            ->child(text('shadcn')->textSize(14)->medium()->truncate())
            ->child(text('m@example.com')->textSize(12)->textColor(t('muted-foreground'))->truncate()))
        ->child(icon('ellipsis-vertical', 16)));

$siteHeader = fn () => row()->itemsCenter()->gap(8)->h(48)->px(24)->borderB(1, t('border'))->shrink(0)
    ->child(button('', null, 'ghost', 'icon-sm', 'panel-left'))
    ->child(separator(true)->h(16)->self('center'))
    ->child(text('Documents')->textSize(16)->medium())
    ->child(spacer())
    ->child(textInput('search', $state->search, fn ($v) => $state->search = $v, 'Search…', 'search')->w(220))
    ->child(button('', fn () => Theme::toggle(), 'ghost', 'icon-sm', Theme::dark() ? 'sun' : 'moon')->id('theme')
        ->child(tooltip('Toggle theme')));

$statCard = function (string $title, string $value, string $change, bool $up, string $note, string $sub) {
    return card(
        cardHeader(
            text($title)->textSize(14)->textColor(t('muted-foreground')),
            text($value)->textSize(30)->semibold()->tracking(-0.5),
            badge($change, 'outline', $up ? 'trending-up' : 'trending-down'),
        ),
        cardFooter(col()->gap(6)->textSize(14)
            ->child(row()->itemsCenter()->gap(8)->medium()->child($note)->child(icon($up ? 'trending-up' : 'trending-down', 16)))
            ->child(text($sub)->textColor(t('muted-foreground')))),
    )->flex1()->when(!Theme::dark(), fn ($e) => $e->gradient(t('card'), Theme::mix(t('card'), t('primary'), 0.05)))->shadow('xs');
};

$chartCard = function () use ($state, $visitors) {
    $days = ['90d' => 90, '30d' => 30, '7d' => 7][$state->range];
    $data = array_slice($visitors, -$days);
    $what = ['90d' => 'the last 3 months', '30d' => 'the last 30 days', '7d' => 'the last 7 days'][$state->range];
    return card(
        cardHeader(
            text('Total Visitors')->semibold(),
            "Total for $what",
            toggleGroup(['90d' => 'Last 3 months', '30d' => 'Last 30 days', '7d' => 'Last 7 days'],
                $state->range, fn ($v) => $state->range = $v, 'range'),
        ),
        cardContent(areaChart(array_column($data, 'date'), [
            'mobile' => [t('primary'), array_column($data, 'mobile')],
            'desktop' => [t('primary', 0.55), array_column($data, 'desktop')],
        ], $days === 7 ? 7 : 9)->h(250)),
    )->shadow('xs');
};

$dataTable = function () use ($state) {
    $all = count($state->sections);
    $picked = count(array_filter($state->selected));
    $rows = [];
    foreach ($state->sections as $i => [$header, $type, $status, $target, $limit, $reviewer]) {
        $done = $status === 'Done';
        $rows[] = [
            'drag' => icon('grip-vertical', 12, t('muted-foreground')),
            'select' => checkbox(!empty($state->selected[$i]), function () use ($state, $i) {
                $state->selected[$i] = empty($state->selected[$i]);
            })->id("select-$i"),
            'header' => text($header)->truncate()->grow()->medium(),
            'type' => badge($type, 'outline')->textColor(t('muted-foreground'))->px(6),
            'status' => row()->itemsCenter()->gap(4)->h(22)->px(6)->rounded(6)->border(1, t('border'))->self('center')
                ->textSize(12)->medium()->textColor(t('muted-foreground'))
                ->child(icon($done ? 'circle-check' : 'loader', 12, $done ? t('success') : t('muted-foreground')))
                ->child($status),
            'target' => text((string) $target)->textRight()->grow(),
            'limit' => text((string) $limit)->textRight()->grow(),
            'reviewer' => $reviewer !== null
                ? text($reviewer)->truncate()->grow()
                : selectTrigger('Assign reviewer', fn () => null, 'sm')->w(152)->textColor(t('muted-foreground')),
            'actions' => div()->id("actions-$i")
                ->child(button('', function () use ($state, $i) { $state->menu = $state->menu === $i ? null : $i; }, 'ghost', 'icon-sm', 'ellipsis-vertical')
                    ->textColor(t('muted-foreground'))->when($state->menu === $i, fn ($e) => $e->bg(t('accent'))))
                ->when($state->menu === $i, fn ($e) => $e->child(dropdownMenu([
                    ['Edit', null, 'pencil'],
                    ['Make a copy', function () use ($state, $i) {
                        array_splice($state->sections, $i + 1, 0, [$state->sections[$i]]);
                        $state->toast = ['Section copied', $state->sections[$i][0] . ' was duplicated.'];
                    }, 'copy'],
                    ['Favorite', null, 'sparkles', '⌘F'],
                    'separator',
                    ['Delete', function () use ($state, $i) {
                        $state->toast = ['Section deleted', $state->sections[$i][0] . ' was removed.'];
                        array_splice($state->sections, $i, 1);
                    }, 'trash-2', 'variant' => 'destructive'],
                ], fn () => $state->menu = null, 160)->absolute()->top(36)->right(0)->id('row-menu'))),
        ];
    }
    $allChecked = $picked === $all && $all > 0;
    $columns = [
        'drag' => ['', 40, 'left'],
        'select' => [checkbox($allChecked, function () use ($state, $allChecked) {
            $state->selected = $allChecked ? [] : array_fill(0, count($state->sections), true);
        })->id('select-all'), 36, 'left'],
        'header' => ['Header', null, 'left'],
        'type' => ['Section Type', 168, 'left'],
        'status' => ['Status', 136, 'left'],
        'target' => ['Target', 72, 'right'],
        'limit' => ['Limit', 72, 'right'],
        'reviewer' => ['Reviewer', 184, 'left'],
        'actions' => ['', 52, 'right'],
    ];

    $count = fn (int $n) => row()->center()->size(20)->rounded(10)->bg(t('muted-foreground', 0.3))->textSize(12)->medium()->child((string) $n);
    return col()->gap(16)
        ->child(row()->itemsCenter()->justifyBetween()
            ->child(tabs([
                'outline' => 'Outline',
                'past' => row()->itemsCenter()->gap(6)->child('Past Performance')->child($count(3)),
                'people' => row()->itemsCenter()->gap(6)->child('Key Personnel')->child($count(2)),
                'focus' => 'Focus Documents',
            ], $state->tab, fn ($v) => $state->tab = $v))
            ->child(row()->gap(8)
                ->child(button('Customize Columns', null, 'outline', 'sm', 'columns-3')->child(icon('chevron-down', 16)))
                ->child(button('Add Section', fn () => $state->dialog = true, 'outline', 'sm', 'plus')->id('add-section'))))
        ->child(col()->rounded(10)->border(1, t('border'))->clip()
            ->child(table($columns, $rows, fn ($i) => "row-$i", t('muted'),
                fn (Element $line, int $i) => $line->when(!empty($state->selected[$i]), fn ($e) => $e->bg(t('muted'))))))
        ->child(row()->itemsCenter()->justifyBetween()->px(16)->textSize(14)
            ->child(text("$picked of 68 row(s) selected.")->textColor(t('muted-foreground')))
            ->child(row()->itemsCenter()->gap(32)
                ->child(row()->itemsCenter()->gap(8)->child(label('Rows per page'))->child(selectTrigger('10', fn () => null, 'sm')->w(80)))
                ->child(text('Page 1 of 7')->medium())
                ->child(row()->gap(8)
                    ->child(button('', null, 'outline', 'icon-sm', 'chevrons-left')->opacity(0.5))
                    ->child(button('', null, 'outline', 'icon-sm', 'chevron-left')->opacity(0.5))
                    ->child(button('', null, 'outline', 'icon-sm', 'chevron-right'))
                    ->child(button('', null, 'outline', 'icon-sm', 'chevrons-right')))));
};

$addDialog = function () use ($state) {
    $d = &$state->draft;
    $save = function () use ($state) {
        $name = trim($state->draft['header']) ?: 'Untitled section';
        array_unshift($state->sections, [$name, $state->draft['type'], 'In Process', (int) $state->draft['target'] ?: 10, 12, null]);
        $state->dialog = false;
        $state->toast = ['Section added', "$name is now first in the outline."];
        $state->draft = ['header' => '', 'type' => 'Narrative', 'target' => ''];
    };
    return dialog('Add section', 'Add a section to the proposal outline. You can change it later.',
        col()->gap(16)
            ->child(col()->gap(8)->child(label('Header'))
                ->child(textInput('draft-header', $d['header'], function ($v) use ($state) { $state->draft['header'] = $v; }, 'e.g. Pricing')->onSubmit($save)))
            ->child(row()->gap(16)
                ->child(col()->gap(8)->flex1()->child(label('Section type'))->child(selectTrigger($d['type'], fn () => null)))
                ->child(col()->gap(8)->flex1()->child(label('Target'))
                    ->child(textInput('draft-target', $d['target'], function ($v) use ($state) { $state->draft['target'] = $v; }, '10')))),
        row()->gap(8)
            ->child(button('Cancel', fn () => $state->dialog = false, 'outline'))
            ->child(button('Save section', $save)->id('save')),
        fn () => $state->dialog = false,
    );
};

// Render ----------------------------------------------------------------------

$render = function () use ($state, $sidebar, $siteHeader, $statCard, $chartCard, $dataTable, $addDialog) {
    return row()->full()->bg(t('sidebar'))->textColor(t('foreground'))->textSize(14)
        ->child($sidebar())
        ->child(col()->flex1()->py(8)->pr(8)
            ->child(col()->grow()->rounded(12)->bg(t('background'))->shadow('sm')
                ->when(Theme::dark(), fn ($e) => $e->border(1, t('border')))
                ->child($siteHeader())
                ->child(col()->flex1()->scroll('main')->gap(24)->py(24)
                    ->child(row()->gap(16)->px(24)
                        ->child($statCard('Total Revenue', '$1,250.00', '+12.5%', true, 'Trending up this month', 'Visitors for the last 6 months'))
                        ->child($statCard('New Customers', '1,234', '-20%', false, 'Down 20% this period', 'Acquisition needs attention'))
                        ->child($statCard('Active Accounts', '45,678', '+12.5%', true, 'Strong user retention', 'Engagement exceed targets'))
                        ->child($statCard('Growth Rate', '4.5%', '+4.5%', true, 'Steady performance increase', 'Meets growth projections')))
                    ->child(col()->px(24)->child($chartCard()))
                    ->child(col()->px(24)->child($dataTable())))))
        ->when($state->dialog, fn ($e) => $e->child($addDialog()))
        ->when($state->toast !== null, fn ($e) => $e->child(toast(...$state->toast)));
};

$app = new App('Acme Inc. — Dashboard', 1440, 900, $render);
$app->onKey(function (int $key) use ($state) {
    if ($key === GLFW_KEY_ESCAPE) [$state->dialog, $state->menu] = [false, null];
});

// Screenshots: PHPUI_SCREENSHOTS=dir php examples/dashboard.php ----------------

if ($dir = getenv('PHPUI_SCREENSHOTS')) {
    @mkdir($dir, 0777, true);
    [$w, $h] = [1440, 1420];
    $shot = function (string $name) use ($app, $dir, $w, $h) {
        $app->screenshot("$dir/$name.png", $w, $h, 2);
        echo "$dir/$name.png\n";
    };

    $app->screenshot("$dir/.warmup.png", $w, $h, 1); // first layout, so ids can be found
    unlink("$dir/.warmup.png");
    $app->hoverId('row-2');
    $shot('dashboard-light');

    $app->clickId('range-30d');
    $app->clickId('actions-3');
    $app->hoverId('row-menu');
    $shot('dashboard-menu');

    $app->click(5, 5); // click outside closes the menu
    $app->clickId('add-section');
    $app->clickId('draft-header');
    $app->type('Pricing and payment terms');
    $shot('dashboard-dialog');

    $app->clickId('save');
    $app->hoverId('theme');
    Theme::toggle();
    $app->clickId('range-7d');
    $app->pointer(0, 0);
    $shot('dashboard-dark');

    echo json_encode(['sections' => count($state->sections), 'first' => $state->sections[0][0], 'range' => $state->range]), "\n";
    exit;
}

if (!getenv('PHPUI_NO_RUN')) $app->run();
