<?php

declare(strict_types=1);

require __DIR__ . '/../src/autoload.php';

use PhpUi\{App, Desktop, Element, Theme};
use function PhpUi\{alert, areaChart, avatar, badge, barChart, button, card, cardContent, cardFooter, cardHeader,
    checkbox, col, dialog, div, dropdownMenu, grid, icon, image, kbd, label, paragraph, progress, row, select,
    separator, skeleton, slider, spacer, sparkline, state, switchToggle, t, table, tabs, text, textarea, textInput,
    toast, toggleGroup, tooltip};

// Every component, live, with the code that makes it. Copy any snippet into an app.

$state = state(new class {
    public string $section = 'buttons';
    public string $name = 'Ada Lovelace';
    public string $email = '';
    public string $bio = "Wrote the first published program.\nLikes engines and poetry.";
    public string $plan = 'pro';
    public bool $agree = true;
    public bool $emails = false;
    public string $tab = 'account';
    public string $view = 'grid';
    public float $volume = 40;
    public int $rating = 3;
    public bool $dialog = false;
    public bool $menu = false;
    public bool $toast = false;
    public bool $lit = false;
    public int $shown = 0;
    public int $clicks = 0;
});

$sections = [
    'buttons' => ['Buttons', 'circle-dot', 'Six variants, five sizes, with or without an icon.'],
    'forms' => ['Inputs & forms', 'pencil', 'Real editors: caret, selection, copy and paste, undo, Tab between fields.'],
    'choices' => ['Choices', 'check', 'Select, checkbox, switch, tabs, toggle group and slider.'],
    'overlays' => ['Overlays', 'panel-left', 'Dialog, dropdown menu, tooltip and toast. They fade in.'],
    'display' => ['Display', 'gallery-vertical-end', 'Cards, badges, avatars, alerts, progress, skeletons, keys and tables.'],
    'layout' => ['Layout', 'layout-dashboard', 'Grids, wrapping rows, wrapped text and images.'],
    'charts' => ['Charts', 'chart-area', 'Area, bar and sparkline charts in theme colors.'],
    'motion' => ['Motion', 'sparkles', 'Transitions between states, and fading in.'],
];

/** One example: a title with a copy button, the live preview, and its code (selectable). */
$example = function (string $title, Element $preview, string $code): Element {
    return col()->rounded(10)->border(1, t('border'))->clip()->bg(t('card'))
        ->child(row()->itemsCenter()->h(40)->px(16)->borderB(1, t('border'))
            ->child(text($title)->medium()->textSize(13))
            ->child(spacer())
            ->child(button('Copy', fn () => Desktop::setClipboard($code), 'ghost', 'sm', 'copy')->textColor(t('muted-foreground'))))
        ->child(col()->gap(12)->p(24)->child($preview))
        ->child(col()->px(16)->py(12)->bg(t('muted', 0.5))->borderT(1, t('border'))
            ->child(text($code)->wrap()->mono()->textSize(12)->textColor(t('muted-foreground'))->selectable()));
};

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
            tabs()->options(['account' => 'Account', 'password' => 'Password', 'team' => 'Team'])->bind($state->tab),
            toggleGroup()->options(['grid' => 'Grid', 'list' => 'List', 'board' => 'Board'])->bind($state->view),
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
        $example('Dialog', row()->schema([button('Open dialog')->outlined()->onClick(fn () => $state->dialog = true)->id('open-dialog')]), <<<'PHP'
            dialog()->heading('Edit profile')->description('Click save when you are done.')
                ->schema([textInput('name')->label('Name')->bind($state->name)])
                ->footer([button('Save')->onClick(fn () => $state->dialog = false)])
                ->onClose(fn () => $state->dialog = false)
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
        $example('Toast', row()->schema([button('Show toast')->outlined()->id('show-toast')->onClick(function () use ($state) {
                $state->toast = true;
                App::current()?->after(3, fn () => $state->toast = false);
            })]), <<<'PHP'
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
            ['php', 'desktop', 'gpu', 'native', 'no-electron', 'nanovg', 'flexbox', 'shadcn', 'dark-mode', 'single-binary'])), <<<'PHP'
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

$render = function () use ($state, $sections, $pages) {
    $nav = col()->gap(2);
    foreach ($sections as $key => [$name, $ico]) {
        $on = $state->section === $key;
        $nav->child(row()->itemsCenter()->gap(10)->h(34)->px(10)->rounded(8)->textSize(14)->id("nav-$key")->transition()
            ->when($on, fn ($e) => $e->bg(t('accent'))->medium(), fn ($e) => $e->textColor(t('muted-foreground'))->hover(fn ($s) => $s->bg(t('muted', 0.6))->textColor(t('foreground'))))
            ->child(icon($ico, 16))->child($name)
            ->onClick(fn () => $state->section = $key));
    }
    [$title, , $about] = $sections[$state->section];
    $main = col()->flex1()->gap(16)->px(32)->py(28)->scroll('page-' . $state->section)
        ->child(col()->gap(6)->child(text($title)->textSize(24)->bold())->child(paragraph($about)->textColor(t('muted-foreground'))))
        ->children(($pages[$state->section])());

    return row()->full()->bg(t('background'))
        ->child(col()->w(230)->gap(16)->p(16)->bg(t('muted', 0.4))->borderR(1, t('border'))
            ->child(col()->gap(2)->px(10)->pt(4)
                ->child(text('MyPHP')->textSize(18)->bold())
                ->child(text('Components')->textSize(13)->textColor(t('muted-foreground'))))
            ->child($nav)
            ->child(spacer())
            ->child(button(Theme::dark() ? 'Light mode' : 'Dark mode', fn () => Theme::toggle(), 'outline', 'sm', Theme::dark() ? 'sun' : 'moon')->id('theme')))
        ->child($main)
        ->when($state->dialog, fn ($e) => $e->child(dialog()->heading('Edit profile')->description('Make changes here. Click save when you are done.')
            ->schema([textInput('dialog-name')->label('Name')->bind($state->name)])
            ->footer([button('Cancel')->outlined()->onClick(fn () => $state->dialog = false), button('Save')->onClick(fn () => $state->dialog = false)->id('dialog-save')])
            ->onClose(fn () => $state->dialog = false)))
        ->when($state->toast, fn ($e) => $e->child(toast('Saved')->description('Your changes are safe.')));
};

$app = new App('MyPHP components', 1100, 760, $render);
$app->onKey(function (int $key) use ($state) {
    if ($key === GLFW_KEY_ESCAPE) [$state->dialog, $state->menu] = [false, false];
});

if ($dir = getenv('PHPUI_SCREENSHOTS')) {
    // Headless: one picture per section, light and dark.
    @mkdir($dir, 0777, true);
    foreach (['light', 'dark'] as $mode) {
        Theme::set($mode);
        foreach (array_keys($sections) as $key) {
            $state->section = $key;
            $app->screenshot("$dir/gallery-$key-$mode.png", 1100, 760, 2);
        }
    }
    Theme::set('light');
    exit;
}
if (!getenv('PHPUI_NO_RUN')) $app->run();
