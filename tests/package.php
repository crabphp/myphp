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

// A Mach-O runtime the size of a stub: __DATA with __micro_sfxsize, __LINKEDIT, a signature.
$macho = function (): string {
    $section = str_pad('__micro_sfxsize', 16, "\0") . str_pad('__DATA', 16, "\0") . pack('P2V8', 0, 16, 0x1000, 0, 0, 0, 0, 0, 0, 0);
    $data = pack('V2', 0x19, 72 + 80) . str_pad('__DATA', 16, "\0") . pack('P4V4', 0x1000, 0x4000, 0x1000, 0x1000, 3, 3, 1, 0) . $section;
    $linkedit = pack('V2', 0x19, 72) . str_pad('__LINKEDIT', 16, "\0") . pack('P4V4', 0x8000, 0x4000, 0x2000, 0x100, 1, 1, 0, 0);
    $sign = pack('V4', 0x1d, 16, 0x2080, 0x80);
    $cmds = $data . $linkedit . $sign;
    $head = pack('V8', 0xfeedfacf, 0x0100000c, 0, 2, 3, strlen($cmds), 0, 0) . $cmds;
    return str_pad($head, 0x2000, "\0") . str_repeat('L', 0x80) . str_repeat('S', 0x80);
};
$runtime = $macho();
$embedded = myphpMachoEmbed($runtime, 'APP');
check($embedded !== null, 'a Mach-O runtime takes the app inside');
same(0x2080 + 3, strlen($embedded), 'the old signature is gone and the app follows __LINKEDIT\'s data');
same('APP', substr($embedded, 0x2080), 'the app sits where __LINKEDIT\'s data ended');
same([0x2080, 0x2083], array_values(unpack('J2', $embedded, 0x1000)), '__micro_sfxsize says where it is');
same(2, unpack('V', $embedded, 16)[1], 'the signature command is removed');
same(0x2083 - 0x2000, unpack('P', $embedded, 32 + 152 + 48)[1], '__LINKEDIT grows to cover it');
$again = myphpMachoEmbed($embedded . str_repeat('X', 50), 'NEW APP');
same('NEW APP', substr((string) $again, 0x2080), 'embedding again replaces the app');
same(null, myphpMachoEmbed(str_replace('__micro_sfxsize', '__other_section', $runtime), 'APP'), 'a runtime without the section keeps the old way');

myphpRemoveTree($tmp);
