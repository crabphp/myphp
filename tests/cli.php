<?php

declare(strict_types=1);

require __DIR__ . '/lib.php';

/** Run with isolated output files, bounded time, and no shell interpolation. */
function cliProcess(array $command, ?string $cwd = null, array $env = [], float $timeout = 10): array
{
    $out = tempnam(getenv('PHPUI_TEST_TMP'), 'stdout-');
    $err = tempnam(getenv('PHPUI_TEST_TMP'), 'stderr-');
    $process = proc_open($command, [0 => ['file', '/dev/null', 'r'],
        1 => ['file', $out, 'w'], 2 => ['file', $err, 'w']], $pipes,
        $cwd ?? getenv('PHPUI_TEST_TMP'), $env + getenv());
    $deadline = microtime(true) + $timeout;
    do {
        $status = proc_get_status($process);
        if (!$status['running']) break;
        if (microtime(true) > $deadline) {
            proc_terminate($process, 9);
            throw new RuntimeException('Command timed out: ' . implode(' ', $command));
        }
        usleep(10000);
    } while (true);
    proc_close($process);
    $result = [$status['exitcode'], file_get_contents($out), file_get_contents($err)];
    unlink($out); unlink($err);
    return $result;
}

function cli(array $args, ?string $cwd = null, array $env = []): array
{
    $command = getenv('MYPHP_BIN') ? [getenv('MYPHP_BIN')] : [PHP_BINARY, __DIR__ . '/../bin/myphp'];
    return cliProcess([...$command, ...$args], $cwd, $env);
}

$buildDir = getenv('PHPUI_TEST_TMP') . '/cli-build';
if (!getenv('MYPHP_BIN')) {
    [$code, $out, $err] = cliProcess([__DIR__ . '/../build/make-myphp.sh'], env: [
        'MYPHP_BUILD_DIR' => $buildDir, 'MYPHP_VERSION' => 'dev', 'PHP' => PHP_BINARY,
    ]);
    check($code === 0, "build CLI: $out$err");
    if ($code !== 0) exit(1);
    putenv("MYPHP_BIN=$buildDir/myphp");
    $sfx = file_get_contents(getenv('HOME') . '/.cache/spc/buildroot/bin/micro.sfx');
    same(hash('sha256', $sfx), hash('sha256', file_get_contents(getenv('MYPHP_BIN'), length: strlen($sfx))),
        'CLI starts with the clean sfx');
    [$code, $out, $err] = cliProcess([__DIR__ . '/../build/make-myphp.sh'], env: [
        'MYPHP_BUILD_DIR' => "$buildDir/versioned", 'MYPHP_VERSION' => '9.9.9', 'PHP' => PHP_BINARY,
    ]);
    same(0, $code, "versioned build: $err");
    [$code, $out] = cliProcess(["$buildDir/versioned/myphp", 'version']);
    check($code === 0 && str_starts_with($out, 'myphp 9.9.9 '), 'version is embedded at build time');
}
[$code, $out, $err] = cliProcess(['/usr/bin/env', '-i', getenv('MYPHP_BIN'), 'help']);
same(0, $code, "binary runs with an empty environment: $err");

foreach ([[], ['help'], ['-h'], ['--help']] as $args) {
    [$code, $out, $err] = cli($args);
    same(0, $code, 'help succeeds');
    check(str_contains($out, 'myphp version'), 'help lists implemented commands');
    same('', $err, 'help has no errors');
}
foreach (['version', '-v', '--version'] as $command) {
    [$code, $out, $err] = cli([$command]);
    same(0, $code, 'version succeeds');
    check((bool) preg_match('/^myphp \S+ \(PHP 8\.\d+\.\d+\)\n$/', $out), 'version includes runtime');
    same('', $err, 'version has no errors');
}
[$code, $out, $err] = cli(['nope']);
same(2, $code, 'unknown command is usage error');
same('', $out, 'unknown command has no stdout');
check(str_contains($err, 'myphp: unknown command "nope"'), 'unknown command diagnostic');

$project = getenv('PHPUI_TEST_TMP') . '/run-app';
mkdir($project);
file_put_contents("$project/app.php", <<<'PHP'
<?php
use PhpUi\App;
use function PhpUi\{col, text};
(new App('Test', 240, 100, fn () => col()->full()->bg('#ffffff')->p(20)
    ->child(text('Hello from the phar')->textColor('#000000'))))->run();
PHP);
$shot = "$project/run.png";
[$code, $out, $err] = cli(['run'], $project, ['PHPUI_SCREENSHOT' => $shot]);
same(0, $code, "run without vendor or require: $err");
check(is_file($shot), 'run writes a screenshot');
same('#ffffff', pixel($shot, 0, 0), 'run background');
[$code, $out] = cliProcess(['magick', $shot, '-crop', '190x30+20+20', '-format', '%[fx:mean]', 'info:']);
check($code === 0 && (float) $out < 0.99, 'bundled font paints text');
file_put_contents("$project/other.php", '<?php file_put_contents("args.json", json_encode([$argv, $argc, $_SERVER["argv"], $_SERVER["argc"], isset($command)]));');
[$code, $out, $err] = cli(['run', 'other.php', 'a', 'b'], $project);
same(0, $code, "run accepts arguments: $err");
same([['other.php', 'a', 'b'], 3, ['other.php', 'a', 'b'], 3, false],
    json_decode(file_get_contents("$project/args.json"), true), 'app gets argv/argc, not CLI locals');
[$code, $out, $err] = cli(['run']);
same(1, $code, 'missing app fails');
check(str_contains($err, 'app.php not found'), 'missing app diagnostic');
file_put_contents("$project/throws.php", '<?php throw new RuntimeException("app broke");');
[$code, $out, $err] = cli(['run', 'throws.php'], $project);
same(1, $code, 'app exception fails');
check(str_contains($err, 'myphp: RuntimeException: app broke') && str_contains($err, '#0'), 'app exception and trace');
[$code, $out, $err] = cli(['demo', 'todo'], env: ['PHPUI_SCREENSHOTS' => "$project/shots"]);
same(0, $code, "bundled todo runs: $err");
check(is_file("$project/shots/todo-1-start.png"), 'bundled todo screenshot');
same(2, cli(['demo', 'nope'])[0], 'unknown demo is usage error');

$workspace = getenv('PHPUI_TEST_TMP') . '/init';
mkdir($workspace);
[$code, $out, $err] = cli(['init', 'hello'], $workspace);
same(0, $code, "init succeeds: $err");
check(str_contains($out, 'Created hello.'), 'init prints next steps');
$hello = "$workspace/hello";
foreach (['app.php', 'composer.json', '.gitignore', 'README.md'] as $file) {
    check(is_file("$hello/$file"), "init writes $file");
    check(!str_contains(file_get_contents("$hello/$file"), '{{'), "$file has no template markers");
}
[$code, $out, $err] = cliProcess([PHP_BINARY, '-l', "$hello/app.php"]);
same(0, $code, "template syntax: $err");
$composer = json_decode(file_get_contents("$hello/composer.json"), true, flags: JSON_THROW_ON_ERROR);
same('app/hello', $composer['name'], 'composer name');
same('^1.0', $composer['require-dev']['phpgl/ide-stubs'], 'editor stubs package');
same('dev-main', $composer['require-dev']['crabphp/myphp'], 'dev constraint');
check(!file_exists("$hello/vendor"), 'init does not run composer');
[$code, $out, $err] = cli(['run'], $hello, ['PHPUI_SCREENSHOT' => "$workspace/hello.png"]);
same(0, $code, "new counter runs without vendor: $err");
check(is_file("$workspace/hello.png"), 'new counter screenshot');
same(1, cli(['init', 'hello'], $workspace)[0], 'init refuses nonempty directory');
same(2, cli(['init'], $workspace)[0], 'init requires name');
same(2, cli(['init', 'bad name'], $workspace)[0], 'init rejects unsafe name');
mkdir("$workspace/empty");
same(0, cli(['init', 'empty'], $workspace)[0], 'init accepts empty directory');
same(0, cli(['init', 'Mixed..Name-'], $workspace)[0], 'directory names may contain repeated punctuation');
same('app/mixed-name', json_decode(file_get_contents("$workspace/Mixed..Name-/composer.json"), true)['name'], 'composer name normalizes punctuation and case');
check(str_contains(cli(['help'])[1], 'myphp init <name>'), 'help now lists init');

[$code, $out, $err] = cli(['build'], $hello, ['PATH' => '', 'TMPDIR' => $workspace]);
same(0, $code, "build offline with no PHP on PATH: $err");
check(is_executable("$hello/build/hello"), 'built app is executable');
$sfx = file_get_contents(getenv('HOME') . '/.cache/spc/buildroot/bin/micro.sfx');
same(hash('sha256', $sfx), hash('sha256', file_get_contents("$hello/build/hello", length: strlen($sfx))), 'app has clean runtime prefix');
$standalone = "$workspace/standalone";
mkdir($standalone);
copy("$hello/build/hello", "$standalone/counter"); chmod("$standalone/counter", 0755);
[$code, $out, $err] = cliProcess(['/usr/bin/env', '-i', 'DISPLAY=' . getenv('DISPLAY'), "HOME=$standalone",
    "XDG_CACHE_HOME=$standalone/cache", "PHPUI_SCREENSHOT=$standalone/built.png", "$standalone/counter"]);
same(0, $code, "standalone app without PHP: $err");
check(is_file("$standalone/built.png"), 'standalone screenshot exists');
foreach ([[0, 0], [85, 50], [110, 130], [240, 240]] as [$x, $y]) {
    same(pixel("$workspace/hello.png", $x, $y), pixel("$standalone/built.png", $x, $y), "built/run pixels agree at $x,$y");
}
file_put_contents("$hello/data.txt", 'packed data');
mkdir("$hello/vendor"); file_put_contents("$hello/vendor/x.php", '<?php');
file_put_contents("$hello/.env", 'secret');
symlink("$hello/.env", "$hello/leaked-env");
file_put_contents("$hello/probe.php", <<<'PHP'
<?php
echo json_encode([file_get_contents(__DIR__ . '/data.txt'), file_exists(__DIR__ . '/vendor/x.php'),
    file_exists(__DIR__ . '/.env'), file_exists(__DIR__ . '/leaked-env'), file_exists(__DIR__ . '/build/hello'),
    ini_get('phar.readonly'), file_exists(__DIR__ . '/probe-output')]);
PHP);
file_put_contents("$hello/probe-output", 'previous output');
[$code, $out, $err] = cli(['build', 'probe.php', '-o', 'probe-output'], $hello);
same(0, $code, "build alternate entry/output: $err");
[$code, $out, $err] = cliProcess(["$hello/probe-output"]);
same(0, $code, "run packed data probe: $err");
same(['packed data', false, false, false, false, '1', false], json_decode($out, true), 'packs data, excludes dependencies, dotfiles, links, builds and output; no CLI INI');
[$code, $out, $err] = cli(['build', '-o', "$workspace/out/app2"], $hello);
same(0, $code, "build creates output parents: $err");
check(is_executable("$workspace/out/app2"), 'custom output exists');
same(1, cli(['build', 'missing.php'], $hello)[0], 'missing build entry fails');
same(1, cli(['build', '.env'], $hello)[0], 'hidden entry fails rather than building a broken app');
same(2, cli(['build', '-o'], $hello)[0], 'missing output argument is usage error');
same(1, cli(['build', '-o', 'app.php'], $hello)[0], 'build cannot overwrite entry');
[$code, $out, $err] = cliProcess([PHP_BINARY, __DIR__ . '/../bin/myphp', 'build'], $hello, ['MYPHP_SFX' => '']);
same(1, $code, 'source build requires runtime');
check(str_contains($err, 'build needs the myphp binary'), 'source build explains runtime requirement');
if (is_file("$buildDir/versioned/myphp")) {
    [$code] = cliProcess(["$buildDir/versioned/myphp", 'init', 'versioned'], $workspace);
    same(0, $code, 'versioned init succeeds');
    same('^9.9', json_decode(file_get_contents("$workspace/versioned/composer.json"), true)['require-dev']['crabphp/myphp'], 'release constraint');
}
