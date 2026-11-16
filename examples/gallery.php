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
        $example('Variants', row()->flexWrap()->gap(8)
            ->child(button('Default', fn () => $state->clicks++))
            ->child(button('Secondary', null, 'secondary'))
            ->child(button('Outline', null, 'outline'))
            ->child(button('Ghost', null, 'ghost'))
            ->child(button('Destructive', null, 'destructive'))
            ->child(button('Link', null, 'link')),
            "button('Default', fn () => \$state->clicks++)\nbutton('Outline', null, 'outline')"),
        $example('Sizes and icons', row()->flexWrap()->itemsCenter()->gap(8)
            ->child(button('Small', null, 'outline', 'sm'))
            ->child(button('Default', null, 'outline'))
            ->child(button('Large', null, 'outline', 'lg'))
            ->child(button('New mail', null, 'default', 'default', 'mail'))
            ->child(button('', null, 'outline', 'icon', 'settings'))
            ->child(button('', null, 'ghost', 'icon-sm', 'trash-2')),
            "button('New mail', \$send, 'default', 'default', 'mail')\nbutton('', \$open, 'outline', 'icon', 'settings')"),
        $example('Clicks', row()->itemsCenter()->gap(12)
            ->child(button('Click me', fn () => $state->clicks++)->id('click-me'))
            ->child(text("Clicked {$state->clicks} times")->textColor(t('muted-foreground'))),
            "button('Click me', fn () => \$state->clicks++)\ntext(\"Clicked {\$state->clicks} times\")"),
    ],
    'forms' => fn () => [
        $example('Profile form', grid(2)->gap(16)
            ->child(col()->gap(8)->child(label('Name'))->child(textInput('name', $state->name, fn ($v) => $state->name = $v)))
            ->child(col()->gap(8)->child(label('Email'))->child(textInput('email', $state->email, fn ($v) => $state->email = $v, 'you@example.com', 'mail'))),
            "grid(2)->gap(16)\n    ->child(col()->gap(8)->child(label('Name'))\n        ->child(textInput('name', \$state->name, fn (\$v) => \$state->name = \$v)))"),
        $example('Textarea', col()->gap(8)->child(label('Bio'))
            ->child(textarea('bio', $state->bio, fn ($v) => $state->bio = $v, 'Tell us about you', 4))
            ->child(text(mb_strlen($state->bio) . ' characters')->textSize(12)->textColor(t('muted-foreground'))),
            "textarea('bio', \$state->bio, fn (\$v) => \$state->bio = \$v, 'Tell us about you', 4)"),
    ],
    'choices' => fn () => [
        $example('Select', row()->gap(12)->itemsCenter()
            ->child(select('plan', $state->plan, ['free' => 'Free', 'pro' => 'Pro', 'team' => 'Team'], fn ($v) => $state->plan = $v)->w(180))
            ->child(text("Plan: {$state->plan}")->textColor(t('muted-foreground'))),
            "select('plan', \$state->plan, ['free' => 'Free', 'pro' => 'Pro'], fn (\$v) => \$state->plan = \$v)"),
        $example('Checkbox and switch', col()->gap(12)
            ->child(row()->itemsCenter()->gap(8)->child(checkbox($state->agree, fn () => $state->agree = !$state->agree)->id('agree'))->child(label('Accept the terms')))
            ->child(row()->itemsCenter()->gap(8)->child(switchToggle($state->emails, fn () => $state->emails = !$state->emails)->id('emails'))->child(label('Email me updates'))),
            "checkbox(\$state->agree, fn () => \$state->agree = !\$state->agree)\nswitchToggle(\$state->emails, fn () => \$state->emails = !\$state->emails)"),
        $example('Tabs and toggle group', col()->gap(12)
            ->child(tabs(['account' => 'Account', 'password' => 'Password', 'team' => 'Team'], $state->tab, fn ($v) => $state->tab = $v))
            ->child(toggleGroup(['grid' => 'Grid', 'list' => 'List', 'board' => 'Board'], $state->view, fn ($v) => $state->view = $v)),
            "tabs(['account' => 'Account', 'team' => 'Team'], \$state->tab, fn (\$v) => \$state->tab = \$v)"),
        $example('Slider', row()->itemsCenter()->gap(16)
            ->child(slider($state->volume, fn ($v) => $state->volume = $v)->w(240)->id('volume'))
            ->child(text((int) $state->volume . '%')->mono()),
            "slider(\$state->volume, fn (\$v) => \$state->volume = \$v)->w(240)"),
    ],
    'overlays' => fn () => [
        $example('Dialog', row()->child(button('Open dialog', fn () => $state->dialog = true, 'outline')->id('open-dialog')),
            "dialog('Edit profile', 'Changes save when you click Save.', \$body, \$footer, fn () => \$state->open = false)"),
        $example('Dropdown menu', div()->child(button('Options', fn () => $state->menu = !$state->menu, 'outline', 'default', 'chevron-down')->id('open-menu'))
            ->when($state->menu, fn ($e) => $e->child(dropdownMenu([
                ['label' => 'My account'],
                ['Profile', null, 'user', 'Shift+Ctrl+P'],
                ['Settings', null, 'settings', 'Ctrl+,'],
                'separator',
                ['Log out', null, 'log-out', null, 'variant' => 'destructive'],
            ], fn () => $state->menu = false)->absolute()->top(40)->left(0)->id('menu'))),
            "dropdownMenu([['Profile', \$open, 'user'], 'separator', ['Log out', \$quit, 'log-out']], \$close)\n    ->absolute()->top(40)->left(0)"),
        $example('Tooltip', row()->child(button('Hover me', null, 'outline')->child(tooltip('Tooltips follow the pointer\'s target')))->pb(24),
            "button('Hover me', null, 'outline')->child(tooltip('Saved!'))"),
        $example('Toast', row()->child(button('Show toast', function () use ($state) {
                $state->toast = true;
                App::current()?->after(3, fn () => $state->toast = false);
            }, 'outline')->id('show-toast')),
            "\$state->toast = true;\n\$app->after(3, fn () => \$state->toast = false);\n// in render: ->when(\$state->toast, fn (\$e) => \$e->child(toast('Saved', 'Your changes are safe.')))"),
    ],
    'display' => fn () => [
        $example('Card', card(
            cardHeader('Team plan', 'For small teams that ship together.', badge('Popular')),
            cardContent(row()->itemsCenter()->gap(4)->child(text('$29')->textSize(30)->bold())->child(text('/ month')->textColor(t('muted-foreground')))),
            cardFooter(button('Choose plan')->grow()),
        )->w(320), "card(\n    cardHeader('Team plan', 'For small teams.', badge('Popular')),\n    cardContent(text('\$29')->textSize(30)->bold()),\n    cardFooter(button('Choose plan')->grow()),\n)->w(320)"),
        $example('Badges, avatars and keys', row()->flexWrap()->itemsCenter()->gap(8)
            ->child(badge('Default'))->child(badge('Secondary', 'secondary'))->child(badge('Destructive', 'destructive'))->child(badge('Outline', 'outline'))
            ->child(badge('Verified', 'secondary', 'circle-check'))
            ->child(avatar('AL'))->child(avatar('GH', 40, 'rounded'))
            ->child(kbd('Ctrl+K'))->child(kbd('Shift+Enter')),
            "badge('Verified', 'secondary', 'circle-check')\navatar('AL')\nkbd('Ctrl+K')"),
        $example('Alerts', col()->gap(12)
            ->child(alert('Heads up!', 'You can select this text and copy it: alerts and messages are selectable.', 'info'))
            ->child(alert('Payment failed', 'Your card was declined. Check the details and try again.', 'circle-alert', 'destructive')),
            "alert('Heads up!', 'You can select this text.', 'info')\nalert('Payment failed', 'Card declined.', 'circle-alert', 'destructive')"),
        $example('Progress, skeleton and separator', col()->gap(16)
            ->child(progress(66)->w(320))
            ->child(row()->gap(12)->itemsCenter()->child(skeleton(40, 40)->rounded(20))->child(col()->gap(8)->child(skeleton(220, 14))->child(skeleton(160, 14))))
            ->child(separator())
            ->child(row()->h(20)->gap(12)->itemsCenter()->child(text('Docs'))->child(separator(true))->child(text('Blog'))->child(separator(true))->child(text('Source'))),
            "progress(66)->w(320)\nskeleton(220, 14)\nseparator()"),
        $example('Table', table(
            ['name' => ['Name', null, 'left'], 'role' => ['Role', 140, 'left'], 'status' => ['Status', 110, 'left']],
            [
                ['name' => 'Ada Lovelace', 'role' => 'Engineer', 'status' => badge('Active', 'secondary')],
                ['name' => 'Grace Hopper', 'role' => 'Admiral', 'status' => badge('Active', 'secondary')],
                ['name' => 'Alan Turing', 'role' => 'Researcher', 'status' => badge('Away', 'outline')],
            ],
        ), "table(\n    ['name' => ['Name', null, 'left'], 'role' => ['Role', 140, 'left']],\n    [['name' => 'Ada Lovelace', 'role' => 'Engineer']],\n)"),
    ],
    'layout' => fn () => [
        $example('Grid', grid(3)->gap(12)->children(array_map(
            fn ($i) => col()->gap(4)->p(16)->rounded(8)->border(1, t('border'))
                ->child(text("Card $i")->medium())
                ->child(paragraph($i === 2 ? 'A longer note that wraps and makes this row taller.' : 'Short.')->textColor(t('muted-foreground'))),
            [1, 2, 3, 4, 5, 6])),
            "grid(3)->gap(12)->children(\$cards)"),
        $example('Wrapping row', row()->flexWrap()->gap(8)->children(array_map(
            fn ($tag) => badge($tag, 'outline'),
            ['php', 'desktop', 'gpu', 'native', 'no-electron', 'nanovg', 'flexbox', 'shadcn', 'dark-mode', 'single-binary']))->w(420),
            "row()->flexWrap()->gap(8)->children(array_map(fn (\$tag) => badge(\$tag, 'outline'), \$tags))"),
        $example('Image', row()->gap(12)
            ->child(image(__DIR__ . '/gallery.jpg')->w(200)->rounded(8))
            ->child(image(__DIR__ . '/gallery.jpg')->size(110)->fit('cover')->rounded(55)),
            "image(__DIR__ . '/photo.jpg')->w(200)->rounded(8)\nimage(__DIR__ . '/photo.jpg')->size(110)->fit('cover')->rounded(55)"),
    ],
    'charts' => fn () => [
        $example('Area chart', areaChart(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], [
            'Desktop' => [t('chart-1'), [186, 305, 237, 273, 209, 214, 260]],
            'Mobile' => [t('chart-2'), [80, 200, 120, 190, 130, 140, 170]],
        ])->h(220), "areaChart(\$days, ['Desktop' => [t('chart-1'), \$values]])->h(220)"),
        $example('Bar chart and sparkline', row()->gap(24)->itemsCenter()
            ->child(barChart(['Q1', 'Q2', 'Q3', 'Q4'], [
                '2025' => [t('chart-1'), [120, 160, 140, 190]],
                '2026' => [t('chart-2'), [140, 180, 170, 230]],
            ])->w(320)->h(180))
            ->child(sparkline([4, 6, 5, 9, 7, 11, 10, 14], t('chart-2'))->w(160)->h(48)),
            "barChart(['Q1', 'Q2'], ['2026' => [t('chart-2'), [140, 180]]])->w(320)->h(180)\nsparkline([4, 6, 5, 9, 14], t('chart-2'))->w(160)->h(48)"),
    ],
    'motion' => fn () => [
        $example('Transition', row()->itemsCenter()->gap(16)
            ->child(div()->size(64)->rounded(12)->id('lamp')->transition(400)->bg($state->lit ? t('chart-1') : t('muted')))
            ->child(button($state->lit ? 'Turn off' : 'Turn on', fn () => $state->lit = !$state->lit, 'outline')->id('toggle-lamp')),
            "div()->size(64)->rounded(12)->transition(400)\n    ->bg(\$state->lit ? t('chart-1') : t('muted'))"),
        $example('Fade in', row()->itemsCenter()->gap(16)
            ->child(button('Show another', fn () => $state->shown++, 'outline')->id('show-another'))
            ->child(row()->gap(8)->children(array_map(
                fn ($i) => avatar((string) $i)->animateIn(400)->id("fade-$i"), range(1, $state->shown % 6 + 1)))),
            "avatar('1')->animateIn(400)"),
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
        ->when($state->dialog, fn ($e) => $e->child(dialog('Edit profile', 'Make changes here. Click save when you are done.',
            col()->gap(8)->child(label('Name'))->child(textInput('dialog-name', $state->name, fn ($v) => $state->name = $v)),
            row()->gap(8)->child(button('Cancel', fn () => $state->dialog = false, 'outline'))->child(button('Save', fn () => $state->dialog = false)->id('dialog-save')),
            fn () => $state->dialog = false)))
        ->when($state->toast, fn ($e) => $e->child(toast('Saved', 'Your changes are safe.')));
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
