<?php

declare(strict_types=1);

/** Copy regular files only; never follow symlinks out of a project. */
function myphpPack(Phar $phar, string $root, string $prefix, ?string $output = null, bool $project = false): void
{
    $walk = function (string $dir, string $relative = '') use (&$walk, $phar, $prefix, $output, $project): void {
        foreach (new DirectoryIterator($dir) as $file) {
            if ($file->isDot() || $file->isLink()) continue;
            $name = $file->getFilename();
            if ($project && (str_starts_with($name, '.') || in_array($name, ['vendor', 'build'], true) && $file->isDir())) continue;
            $path = $file->getPathname();
            if ($path === $output) continue;
            $entry = $relative . $name;
            if ($file->isDir()) $walk($path, "$entry/");
            elseif ($file->isFile()) $phar->addFile($path, "$prefix/$entry");
        }
    };
    $walk($root);
}

/** ELF, 64-bit Mach-O (thin or universal), or a Windows executable. */
function myphpIsExecutable(string $bytes): bool
{
    return str_starts_with($bytes, 'MZ') || in_array(substr($bytes, 0, 4), ["\x7fELF", "\xcf\xfa\xed\xfe", "\xca\xfe\xba\xbe"], true);
}

/**
 * The runtime prefix of this executable. The micro SAPI hides that prefix when a script
 * opens the executable by its own path, so read it through /proc or a temporary link.
 */
function myphpRuntime(int $size): string
{
    $read = fn (string $path): string => (string) @file_get_contents($path, length: $size);
    if (is_readable('/proc/self/exe')) return $read('/proc/self/exe');
    $self = Phar::running(false);
    // Hard links need the same volume, so also try next to the executable.
    foreach ([['symlink', sys_get_temp_dir()], ['link', sys_get_temp_dir()], ['link', dirname($self)]] as [$make, $dir]) {
        $alias = $dir . '/myphp-self-' . getmypid() . '-' . bin2hex(random_bytes(4));
        if (!@$make($self, $alias)) continue;
        try { $bytes = $read($alias); } finally { @unlink($alias); }
        if (strlen($bytes) === $size && myphpIsExecutable($bytes)) return $bytes;
    }
    return '';
}

function cmdBuild(array $args): int
{
    $file = null;
    $output = null;
    for ($i = 0; $i < count($args); $i++) {
        if ($args[$i] === '-o' && $output === null && isset($args[$i + 1]) && !str_starts_with($args[$i + 1], '-')) {
            $output = $args[++$i];
        } elseif (!str_starts_with($args[$i], '-') && $file === null) {
            $file = $args[$i];
        } else {
            return myphpError('usage: myphp build [file] [-o output]', 2);
        }
    }
    $file ??= 'app.php';
    if (!is_file($file)) return myphpError("$file not found");
    $entry = realpath($file);
    if (str_starts_with(basename($entry), '.')) return myphpError('entry file cannot be a dotfile');
    $project = dirname($entry);
    $output ??= (dirname($file) === '.' ? '' : dirname($file) . '/') . 'build/' . basename($project);
    if (PHP_OS_FAMILY === 'Windows' && !str_ends_with(strtolower($output), '.exe')) $output .= '.exe';
    if (PHP_SAPI === 'micro') {
        $size = (int) file_get_contents(MYPHP_ROOT . '/sfx-size');
        if ($size < 4) return myphpError('invalid embedded runtime size');
        $sfx = myphpRuntime($size);
        if (strlen($sfx) !== $size) return myphpError('cannot read the embedded runtime');
    } else {
        $path = getenv('MYPHP_SFX');
        if (!$path || !is_file($path) || ini_get('phar.readonly')) {
            return myphpError('build needs the myphp binary (or MYPHP_SFX and -d phar.readonly=0)');
        }
        $sfx = file_get_contents($path);
    }
    if (!myphpIsExecutable($sfx)) return myphpError('runtime is not an ELF, Mach-O or Windows executable');
    $dir = dirname($output);
    if (!is_dir($dir) && !mkdir($dir, 0777, true)) return myphpError("cannot create $dir");
    $target = realpath($dir) . '/' . basename($output);
    if ($target === $entry || realpath($target) === $entry) return myphpError('output would overwrite the entry file');
    $temp = tempnam(sys_get_temp_dir(), 'myphp-');
    unlink($temp);
    $archive = "$temp.phar";
    $staged = null;
    try {
        $phar = new Phar($archive);
        $phar->startBuffering();
        myphpPack($phar, MYPHP_ROOT . '/src', 'src');
        myphpPack($phar, MYPHP_ROOT . '/assets', 'assets');
        myphpPack($phar, $project, 'app', $target, true);
        $app = var_export('phar://app.phar/app/' . basename($entry), true);
        $phar->setStub("<?php Phar::mapPhar('app.phar'); require 'phar://app.phar/src/autoload.php'; require $app; __HALT_COMPILER();");
        $phar->stopBuffering();
        // Same INI as the CLI where apps need it: FFI for screen reader support.
        $ini = "ffi.enable=1\n";
        $bytes = $sfx . "\xfd\xf6\x69\xe6" . pack('N', strlen($ini)) . $ini . file_get_contents($archive);
        $staged = tempnam(realpath($dir), '.myphp-');
        if (file_put_contents($staged, $bytes) !== strlen($bytes) || !chmod($staged, 0755) || !rename($staged, $target)) {
            return myphpError("cannot write $output");
        }
        printf("Built %s (%.1f MB)\n", $output, strlen($bytes) / 1000000);
        return 0;
    } finally {
        unset($phar);
        if (is_file($archive)) unlink($archive);
        if ($staged !== null && is_file($staged)) unlink($staged);
    }
}
