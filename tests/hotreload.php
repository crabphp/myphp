<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';
require __DIR__ . '/../src/autoload.php';

// Hot reload (myphp run --watch): the script runs again in the same window; state stays.
use PhpUi\{App, HotReload, State};

$dir = (getenv('PHPUI_TEST_TMP') ?: sys_get_temp_dir()) . '/hot-' . getmypid();
@mkdir($dir, 0777, true);
$script = "$dir/app.php";
$write = function (string $label, string $extra = '') use ($script) {
    file_put_contents($script, <<<PHP
    <?php
    use PhpUi\\App;
    use function PhpUi\\{button, col, state, text, textInput};

    \$state = state(new class {
        public int \$count = 0;
        public string \$name = '';
        $extra
    });
    \$app = new App('Hot', 400, 300, fn () => col()->p(20)->gap(8)->schema([
        text('$label' . \$state->count)->id('label'),
        button('Add')->onClick(fn () => \$state->count++)->id('add'),
        textInput('name')->bind(\$state->name)->id('name'),
    ]));
    if (!getenv('HOT_TEST')) \$app->run();
    PHP);
};
putenv('HOT_TEST=1');
putenv('MYPHP_WATCH_INTERVAL=0');
putenv("MYPHP_HOT_RELOAD=$script");
$write('Count: ');
require $script;
State::stopRecording(); // what $app->run() does
$first = App::current();
$first->frame();
$first->clickId('add');
$first->clickId('add');
$first->clickId('name');
$first->type('Ada');
$first->frame();
same('Count: 2', $first->find('label')->text, 'the app runs');

$poll = fn () => HotReload::poll(App::current());
$write('Total: ', 'public array $added = [1];');
check($poll(), 'a changed script reloads');
$app = App::current();
check($app !== $first, 'a new app took over');
check($app->window === $first->window, 'in the same window');
$app->frame();
same('Total: 2', $app->find('label')->text, 'the new code draws, with the count kept');
same('name', (new ReflectionProperty($app, 'focusId'))->getValue($app), 'focus stays');
$app->clickId('add');
same('Total: 3', $app->find('label')->text, 'the new handlers work on the kept state');

file_put_contents($script, "<?php \$x = ;");
$poll();
check(HotReload::$error !== null && str_contains(HotReload::$error, 'syntax error'), 'a syntax error is reported');
same($app, App::current(), 'the last working app keeps running');
$app->frame();
check($app->find('label') !== null && str_contains(json_encode($app->accessibilityTree()), 'Reload failed'), 'the error shows over the app');

$write('Total: ');
file_put_contents($script, str_replace("\$app = new App", "throw new RuntimeException('boom');\n\$app = new App", file_get_contents($script)));
$poll();
check(str_contains((string) HotReload::$error, 'boom'), 'an exception in the script is reported');
same($app, App::current(), 'and the last working app keeps running');
$app->clickId('add');
same('Total: 4', $app->find('label')->text, 'with its state and handlers');

$write('Back: ');
$poll();
same(null, HotReload::$error, 'a working reload clears the error');
App::current()->frame();
same('Back: 4', App::current()->find('label')->text, 'state survives a failed reload too');

check(!HotReload::declares('<?php use function A\\{b}; $s = new class { function f() {} }; $f = function () {}; $g = fn () => 1;'), 'closures, methods and anonymous classes reload in place');
check(HotReload::declares('<?php function helper() {}') && HotReload::declares('<?php class Model {}') && HotReload::declares('<?php enum Suit {}'), 'named functions and classes restart the app');

array_map('unlink', glob("$dir/*"));
rmdir($dir);
