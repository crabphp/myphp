<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';

// Packaging for myphp build: app details, macOS icons and bundles.
const MYPHP_ROOT = __DIR__ . '/..';
require __DIR__ . '/../src/autoload.php';
require __DIR__ . '/../cli/package.php';

$tmp = (getenv('PHPUI_TEST_TMP') ?: sys_get_temp_dir()) . '/package-' . getmypid();
@mkdir("$tmp/my-cool_app", 0777, true);
$project = "$tmp/my-cool_app";

$info = myphpAppInfo($project);
same('My Cool App', $info['name'], 'the name defaults to the folder, in words');
same('dev.myphp.my-cool-app', $info['id'], 'the id defaults to dev.myphp.<folder>');
same('1.0.0', $info['version'], 'the version defaults to 1.0.0');
check(!$info['iconInProject'] && str_ends_with($info['icon'], '/assets/icon.png'), "without an icon, myphp's is used");

copy(__DIR__ . '/../assets/icon.png', "$project/art.png");
file_put_contents("$project/composer.json", json_encode(['version' => '3.4.5-beta', 'extra' => ['myphp' => [
    'name' => 'Cool & Co', 'id' => 'com.example.cool', 'icon' => 'art.png',
]]]));
$info = myphpAppInfo($project);
same(['Cool & Co', 'com.example.cool', '3.4.5'], [$info['name'], $info['id'], $info['version']], 'composer.json extra.myphp names the app');
check($info['iconInProject'] && str_ends_with($info['icon'], '/art.png'), 'and its icon');
file_put_contents("$project/composer.json", json_encode(['extra' => ['myphp' => ['id' => 'not an id']]]));
same('dev.myphp.my-cool-app', myphpAppInfo($project)['id'], 'an invalid id falls back to the default');

check(myphpIcns(__DIR__ . '/../assets/icon.png', "$tmp/AppIcon.icns"), 'an .icns is written');
$icns = file_get_contents("$tmp/AppIcon.icns");
same('icns', substr($icns, 0, 4), 'with the icns magic');
same(strlen($icns), unpack('N', substr($icns, 4, 4))[1], 'and its length');
$sizes = [];
for ($o = 8; $o < strlen($icns);) {
    $n = unpack('N', substr($icns, $o + 4, 4))[1];
    $sizes[substr($icns, $o, 4)] = getimagesizefromstring(substr($icns, $o + 8, $n - 8))[0];
    $o += $n;
}
same(['icp4' => 16, 'icp5' => 32, 'icp6' => 64, 'ic07' => 128, 'ic08' => 256, 'ic09' => 512, 'ic10' => 1024,
    'ic11' => 32, 'ic12' => 64, 'ic13' => 256, 'ic14' => 512], $sizes, 'every size macOS uses, as PNGs');

$plist = myphpPlist(['name' => 'Cool & Co', 'id' => 'com.example.cool', 'version' => '3.4.5'], 'cool');
$xml = simplexml_load_string($plist);
check($xml !== false, 'Info.plist is valid XML');
$keys = array_map('strval', $xml->xpath('//key'));
$values = $xml->xpath('//key/following-sibling::*[1]');
$dict = array_combine($keys, array_map(fn ($v) => $v->getName() === 'true' ? true : (string) $v, $values));
same('Cool & Co', $dict['CFBundleName'], 'names are escaped');
same(['cool', 'AppIcon', 'APPL', true], [$dict['CFBundleExecutable'], $dict['CFBundleIconFile'], $dict['CFBundlePackageType'], $dict['NSHighResolutionCapable']], 'the bundle keys macOS needs');

file_put_contents("$tmp/cool", "#!/bin/sh\n");
$app = myphpMacBundle("$tmp/cool", ['name' => 'Cool', 'id' => 'com.example.cool', 'version' => '1.0.0', 'icon' => __DIR__ . '/../assets/icon.png', 'iconInProject' => false], $tmp);
same("$tmp/Cool.app", $app, 'the bundle is named after the app');
check(is_executable("$app/Contents/MacOS/cool") && is_file("$app/Contents/Info.plist") && is_file("$app/Contents/Resources/AppIcon.icns") && file_get_contents("$app/Contents/PkgInfo") === 'APPL????', 'with its executable, Info.plist, icon and PkgInfo');
check(myphpMacBundle("$tmp/cool", ['name' => 'Cool', 'id' => 'x.y', 'version' => '1', 'icon' => __DIR__ . '/../assets/icon.png', 'iconInProject' => false], $tmp) === $app, 'building again replaces it');

myphpRemoveTree($tmp);
