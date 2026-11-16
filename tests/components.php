<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';
require __DIR__ . '/../src/autoload.php';
use PhpUi\App;
use function PhpUi\{alert, badge, button, card, checkbox, col, dialog, grid, row, select, slider, state, switchToggle, tabs, text, textarea, textInput, toast, toggleGroup};

// Filament-style components: chained settings, schema() for children, bind() for state.
$s = state(new class {
    public string $email = 'ada@example.com';
    public string $bio = '';
    public bool $agree = false;
    public bool $emails = true;
    public string $plan = 'free';
    public int $rating = 3;
    public float $volume = 40.0;
    public string $tab = 'account';
    public string $view = 'grid';
    public bool $open = true;
    public array $log = [];
    public int $saves = 0;
});
$app = new App('Components', 900, 900, fn () => col()->full()->p(10)->gap(10)->schema([
    grid(2)->gap(10)->id('grid')->schema([
        card()->id('card')->heading('Team plan')->description('For small teams.')->headerAction(badge('Popular')->color('secondary'))
            ->schema([text('$29')->id('price')])
            ->footer([button('Choose plan')->color('primary')->icon('check')->iconPosition('after')->id('choose')->onClick(fn () => $s->saves++)]),
        col()->gap(8)->schema([
            textInput('email')->label('Email')->placeholder('you@example.com')->prefixIcon('mail')->helperText('We never share it.')
                ->bind($s->email)->afterStateUpdated(fn ($v) => $s->log[] = "email:$v"),
            textarea('bio')->rows(3)->label('Bio')->bind($s->bio),
            checkbox()->label('Accept the terms')->bind($s->agree)->id('agree'),
            switchToggle()->label('Email me')->bind($s->emails)->id('emails'),
        ]),
    ]),
    select('plan')->label('Plan')->options(['free' => 'Free', 'pro' => 'Pro', 'team' => 'Team'])->bind($s->plan)->w(200),
    slider()->min(1)->max(5)->step(1)->bind($s->rating)->w(200)->id('rating'),
    slider()->bind($s->volume)->w(200)->id('volume'),
    tabs()->options(['account' => 'Account', 'team' => 'Team'])->bind($s->tab),
    toggleGroup()->options(['grid' => 'Grid', 'list' => 'List'])->bind($s->view),
    row()->gap(8)->schema([
        button('Off')->disabled()->id('off')->onClick(fn () => $s->saves += 100),
        button('', fn () => null, 'ghost', 'icon', 'settings')->label('Settings')->id('icon-button'),
        button('Big icon')->size(52)->id('sized'),
    ]),
    alert('Heads up')->description('Alerts take a description and a color.')->color('danger')->id('alert'),
])->when($s->open, fn ($e) => $e->child(
    dialog()->heading('Rename')->description('Pick a new name.')
        ->schema([textInput('dialog-name')->bind($s->email)])
        ->footer([button('Done')->id('done')->onClick(fn () => $s->open = false)])
        ->onClose(fn () => $s->open = false))
)->when($s->saves > 0, fn ($e) => $e->child(toast('Saved')->description('Your plan is chosen.')->id('toast'))));
$app->frame();

// Card: heading, description, header action, content, footer.
check($app->find('price') !== null, 'card schema() renders its content');
$app->clickId('done');
same(false, $s->open, 'dialog footer buttons work');
$app->clickId('choose');
same(1, $s->saves, 'a chained onClick works');
check($app->find('toast') !== null, 'toast() takes a description');
same(2, count($app->find('grid')->children), 'grid()->schema([...]) holds its children');

// bind(): inputs show the property and write it back.
same('ada@example.com', $app->find('email')->text, 'a bound input shows the property');
$app->clickId('email');
$app->key(GLFW_KEY_END);
$app->type('!');
same('ada@example.com!', $s->email, 'typing writes the bound property');
same(['email:ada@example.com!'], $s->log, 'afterStateUpdated runs after a change');
$app->clickId('bio');
$app->type("line one\nline two");
same("line one\nline two", $s->bio, 'a bound textarea keeps line breaks');
$app->clickId('agree');
same(true, $s->agree, 'clicking a bound checkbox (or its label) flips the property');
$app->clickId('emails');
same(false, $s->emails, 'a bound switch flips its property');
$app->clickId('plan-trigger');
$menu = $app->find('plan-menu');
$app->click($menu->x + 20, $menu->y + 4 + 32 * 1 + 16);
same('pro', $s->plan, 'a bound select writes the chosen option');
$app->focusElement('rating');
$app->key(GLFW_KEY_RIGHT);
same(4, $s->rating, 'a slider with min/max/step writes an int property');
$app->key(GLFW_KEY_END);
same(5, $s->rating, 'End moves it to max');
$v = $app->find('volume');
$app->press($v->x + 8 + ($v->w - 16) * 0.75, $v->y + 8);
$app->release($v->x + 8 + ($v->w - 16) * 0.75, $v->y + 8);
same(75.0, $s->volume, 'dragging a bound slider writes a float property');
$app->clickId('tab-team');
same('team', $s->tab, 'bound tabs write the chosen tab');
$app->clickId('toggle-list');
same('list', $s->view, 'a bound toggle group writes its value');

// Buttons: disabled, icon-only with a label, numeric size.
$app->clickId('off');
same(1, $s->saves, 'a disabled button ignores clicks');
check(!$app->find('icon-button')->children || $app->find('icon-button')->label === 'Settings', 'an icon button takes an accessible label');
same([52.0, 52.0], [$app->find('sized')->w, $app->find('sized')->h], 'size(52) still sets a button\'s width and height');
$flat = [];
$walk = function (array $nodes) use (&$walk, &$flat) { foreach ($nodes as $n) { $flat[] = $n; $walk($n['children']); } };
$walk($app->accessibilityTree());
check((bool) array_filter($flat, fn ($n) => $n['role'] === 'textbox' && $n['name'] === 'Email'), 'a field label names its input for screen readers');
