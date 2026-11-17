<?php

declare(strict_types=1);

/*
 * Packaging for `myphp build`: the app's name, id, version and icon, macOS .app
 * bundles (with an .icns icon) and disk images.
 */

/**
 * What the app is called and how it looks, from composer.json's extra.myphp:
 * {"extra": {"myphp": {"name": "Hello", "id": "com.example.hello", "version": "1.2.0", "icon": "icon.png"}}}.
 * Defaults: the folder's name, a dev.myphp id, composer's version or 1.0.0, and
 * icon.png beside the app or myphp's own icon.
 *
 * @return array{name: string, id: string, version: string, icon: string, iconInProject: bool}
 */
function myphpAppInfo(string $project): array
{
    $composer = is_file("$project/composer.json") ? json_decode((string) file_get_contents("$project/composer.json"), true) : null;
    $extra = is_array($composer['extra']['myphp'] ?? null) ? $composer['extra']['myphp'] : [];
    $folder = basename($project);
    $name = is_string($extra['name'] ?? null) && trim($extra['name']) !== ''
        ? trim($extra['name'])
        : ucwords(str_replace(['-', '_', '.'], ' ', $folder));
    $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($folder)), '-') ?: 'app';
    $id = is_string($extra['id'] ?? null) && preg_match('/^[A-Za-z0-9-]+(\.[A-Za-z0-9-]+)+$/', $extra['id']) ? $extra['id'] : "dev.myphp.$slug";
    $version = $extra['version'] ?? $composer['version'] ?? '1.0.0';
    $version = is_string($version) && preg_match('/^\d+(\.\d+){0,2}/', $version, $m) ? $m[0] : '1.0.0';
    $icon = is_string($extra['icon'] ?? null) ? $extra['icon'] : 'icon.png';
    $path = str_starts_with($icon, '/') ? $icon : "$project/$icon";
    $inProject = is_file($path);
    return [
        'name' => $name,
        'id' => $id,
        'version' => $version,
        'icon' => $inProject ? (string) realpath($path) : MYPHP_ROOT . '/assets/icon.png',
        'iconInProject' => $inProject,
    ];
}

/** An .icns file holding $png at every size macOS asks for, as PNGs. */
function myphpIcns(string $png, string $icns): bool
{
    // Type => pixel size: 1x and 2x ("@2x") variants from 16 to 512 points.
    $types = ['icp4' => 16, 'icp5' => 32, 'icp6' => 64, 'ic07' => 128, 'ic08' => 256, 'ic09' => 512, 'ic10' => 1024,
        'ic11' => 32, 'ic12' => 64, 'ic13' => 256, 'ic14' => 512];
    $source = realFileForIcon($png);
    // Without phpsdl (a plain PHP running the CLI), the image goes in as it is.
    if (!class_exists(\PhpSdl\Renderer::class)) {
        $data = (string) file_get_contents($source);
        return file_put_contents($icns, 'icns' . pack('N', 16 + strlen($data)) . 'ic10' . pack('N', 8 + strlen($data)) . $data) !== false;
    }
    $dir = sys_get_temp_dir() . '/myphp-icns-' . getmypid();
    @mkdir($dir, 0700, true);
    $body = '';
    try {
        foreach ($types as $type => $size) {
            $out = "$dir/$size.png";
            if (!is_file($out) && !\PhpSdl\Renderer::resizeImage($source, $out, $size, $size)) return false;
            $data = (string) file_get_contents($out);
            $body .= $type . pack('N', 8 + strlen($data)) . $data;
        }
        return file_put_contents($icns, 'icns' . pack('N', 8 + strlen($body)) . $body) !== false;
    } finally {
        array_map('unlink', glob("$dir/*") ?: []);
        @rmdir($dir);
    }
}

/** A file stb can read: icons inside the binary are copied out first. */
function realFileForIcon(string $path): string
{
    if (!str_starts_with($path, 'phar://')) return $path;
    $copy = sys_get_temp_dir() . '/myphp-icon-' . md5($path) . '.png';
    if (!is_file($copy)) file_put_contents($copy, (string) file_get_contents($path));
    return $copy;
}

function myphpPlist(array $info, string $executable): string
{
    $x = fn (string $s) => htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $keys = [
        'CFBundleDevelopmentRegion' => 'en',
        'CFBundleDisplayName' => $info['name'],
        'CFBundleExecutable' => $executable,
        'CFBundleIconFile' => 'AppIcon',
        'CFBundleIdentifier' => $info['id'],
        'CFBundleInfoDictionaryVersion' => '6.0',
        'CFBundleName' => $info['name'],
        'CFBundlePackageType' => 'APPL',
        'CFBundleShortVersionString' => $info['version'],
        'CFBundleVersion' => $info['version'],
        'LSMinimumSystemVersion' => '11.0',
    ];
    $body = '';
    foreach ($keys as $k => $v) $body .= "\t<key>{$x($k)}</key>\n\t<string>{$x($v)}</string>\n";
    // Sharp on Retina screens.
    $body .= "\t<key>NSHighResolutionCapable</key>\n\t<true/>\n";
    return <<<XML
    <?xml version="1.0" encoding="UTF-8"?>
    <!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
    <plist version="1.0">
    <dict>
    $body</dict>
    </plist>

    XML;
}

/**
 * A macOS app bundle for $binary: build/<Name>.app with Info.plist, the icon as
 * AppIcon.icns and the executable in Contents/MacOS. Signed ad hoc when codesign is
 * there, so Apple Silicon Macs open it. Returns the bundle's path, or null.
 */
function myphpMacBundle(string $binary, array $info, string $dir): ?string
{
    $app = rtrim($dir, '/') . '/' . str_replace('/', '-', $info['name']) . '.app';
    $executable = basename($binary);
    if (is_dir($app)) myphpRemoveTree($app);
    foreach (["$app/Contents/MacOS", "$app/Contents/Resources"] as $d) {
        if (!is_dir($d) && !mkdir($d, 0755, true)) return null;
    }
    if (!copy($binary, "$app/Contents/MacOS/$executable") || !chmod("$app/Contents/MacOS/$executable", 0755)) return null;
    if (file_put_contents("$app/Contents/Info.plist", myphpPlist($info, $executable)) === false) return null;
    file_put_contents("$app/Contents/PkgInfo", "APPL????");
    if (!myphpIcns($info['icon'], "$app/Contents/Resources/AppIcon.icns")) {
        fwrite(STDERR, "myphp: could not make the app icon from {$info['icon']}\n");
    }
    if (PHP_OS_FAMILY === 'Darwin') {
        // Ad hoc: no identity, but a sealed bundle; a Developer ID needs `codesign --sign`.
        exec('codesign --force --sign - ' . escapeshellarg($app) . ' 2>&1', $out, $code);
        if ($code !== 0) fwrite(STDERR, "myphp: codesign failed; the app is unsigned: " . implode(' ', $out) . "\n");
    }
    return $app;
}

/** A compressed disk image with the app and a link to /Applications to drag it to. */
function myphpDmg(string $app, array $info, string $dir): ?string
{
    if (PHP_OS_FAMILY !== 'Darwin') {
        fwrite(STDERR, "myphp: disk images are made on macOS (hdiutil)\n");
        return null;
    }
    $dmg = rtrim($dir, '/') . '/' . str_replace('/', '-', $info['name']) . '.dmg';
    $stage = sys_get_temp_dir() . '/myphp-dmg-' . getmypid();
    myphpRemoveTree($stage);
    mkdir($stage, 0755, true);
    try {
        exec('cp -R ' . escapeshellarg($app) . ' ' . escapeshellarg("$stage/") . ' 2>&1', $out, $code);
        if ($code !== 0 || !symlink('/Applications', "$stage/Applications")) return null;
        @unlink($dmg);
        $command = sprintf('hdiutil create -volname %s -srcfolder %s -ov -format UDZO %s 2>&1',
            escapeshellarg($info['name']), escapeshellarg($stage), escapeshellarg($dmg));
        exec($command, $out, $code);
        if ($code !== 0) {
            fwrite(STDERR, "myphp: hdiutil failed: " . implode(' ', $out) . "\n");
            return null;
        }
        return $dmg;
    } finally {
        myphpRemoveTree($stage);
    }
}

function myphpRemoveTree(string $path): void
{
    if (is_link($path) || is_file($path)) { unlink($path); return; }
    if (!is_dir($path)) return;
    foreach (scandir($path) as $f) if ($f !== '.' && $f !== '..') myphpRemoveTree("$path/$f");
    rmdir($path);
}
