<?php

declare(strict_types=1);

/** The release file for this computer, e.g. myphp-linux-x86_64. */
function myphpAsset(): ?string
{
    $arch = strtolower(php_uname('m'));
    return match (true) {
        PHP_OS_FAMILY === 'Linux' && $arch === 'x86_64' => 'myphp-linux-x86_64',
        PHP_OS_FAMILY === 'Darwin' && in_array($arch, ['arm64', 'aarch64'], true) => 'myphp-macos-arm64',
        PHP_OS_FAMILY === 'Darwin' && $arch === 'x86_64' => 'myphp-macos-x86_64',
        PHP_OS_FAMILY === 'Windows' && in_array($arch, ['amd64', 'x86_64'], true) => 'myphp-windows-x86_64.exe',
        default => null,
    };
}

/** Download with curl or wget: the runtime has no TLS of its own. file:// URLs work too. */
function myphpDownload(string $url, string $to): bool
{
    foreach ([['curl', '-fsSL', '-o', $to, $url], ['wget', '-qO', $to, $url]] as $command) {
        $process = @proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) continue;
        stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        if (proc_close($process) === 0 && is_file($to) && filesize($to) > 0) return true;
        @unlink($to);
        // curl ran and failed: wget would fail the same way.
        if ($command[0] === 'curl' && myphpHas('curl')) return false;
    }
    return false;
}

function myphpHas(string $program): bool
{
    foreach (explode(PATH_SEPARATOR, getenv('PATH') ?: '') as $dir) {
        foreach (PHP_OS_FAMILY === 'Windows' ? ["$dir/$program.exe"] : ["$dir/$program"] as $file) {
            if (is_file($file)) return true;
        }
    }
    return false;
}

/** The output of `<binary> version`, or null when it does not run. */
function myphpVersionOf(string $binary): ?string
{
    $process = @proc_open([$binary, 'version'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) return null;
    $out = trim((string) stream_get_contents($pipes[1]));
    stream_get_contents($pipes[2]);
    return proc_close($process) === 0 && preg_match('/^myphp \S+ \(PHP [0-9.]+\)$/', $out) ? $out : null;
}

/**
 * Replace this binary with the latest release, a version, or the nightly build:
 * download next to it, check the checksum and that it runs, then swap atomically.
 */
function cmdUpgrade(array $args): int
{
    $version = null;
    $nightly = false;
    foreach ($args as $arg) {
        if ($arg === '--nightly') $nightly = true;
        elseif (preg_match('/^v?(\d+\.\d+\.\d+)$/D', $arg, $m)) $version = $m[1];
        else return myphpError('usage: myphp upgrade [--nightly | X.Y.Z]', 2);
    }
    if ($nightly && $version) return myphpError('usage: myphp upgrade [--nightly | X.Y.Z]', 2);
    if (PHP_SAPI !== 'micro') return myphpError('upgrade replaces the installed myphp binary; a source checkout updates with git pull');
    $asset = myphpAsset() ?? myphpError('no myphp build for this system');
    if (is_int($asset)) return $asset;

    $base = rtrim(getenv('MYPHP_RELEASES') ?: 'https://github.com/crabphp/myphp/releases', '/');
    $url = match (true) {
        $nightly => "$base/download/nightly/$asset",
        $version !== null => "$base/download/v$version/$asset",
        default => "$base/latest/download/$asset",
    };
    $self = Phar::running(false);
    $dir = dirname($self);
    if (!is_writable($dir)) return myphpError("cannot write to $dir; reinstall with the install script or run as its owner");
    $temp = "$dir/.myphp-upgrade-" . getmypid() . (PHP_OS_FAMILY === 'Windows' ? '.exe' : '');
    $sum = "$temp.sha256";
    try {
        fwrite(STDERR, "myphp: downloading $url\n");
        if (!myphpDownload($url, $temp)) return myphpError("download failed: $url (needs curl or wget)");
        if (myphpDownload("$url.sha256", $sum)) {
            $expected = strtok((string) file_get_contents($sum), " \t\r\n");
            if (!hash_equals((string) $expected, hash_file('sha256', $temp))) return myphpError('checksum mismatch; nothing was changed');
        } else {
            fwrite(STDERR, "myphp: warning: checksum unavailable; installing without verification\n");
        }
        @chmod($temp, 0755);
        $new = myphpVersionOf($temp) ?? myphpError('the downloaded myphp does not run; nothing was changed');
        if (is_int($new)) return $new;
        $old = 'myphp ' . myphpVersion();
        if (!$nightly && $new === "$old (PHP " . PHP_VERSION . ')') {
            echo "Already up to date: $new\n";
            return 0;
        }
        // Windows cannot replace a running program, but it can rename it out of the way.
        if (PHP_OS_FAMILY === 'Windows') {
            @unlink("$self.old");
            if (!@rename($self, "$self.old")) return myphpError("cannot replace $self");
        }
        if (!@rename($temp, $self)) {
            if (PHP_OS_FAMILY === 'Windows') @rename("$self.old", $self);
            return myphpError("cannot replace $self");
        }
        echo "Upgraded $old -> " . preg_replace('/^myphp /', '', $new) . "\n";
        return 0;
    } finally {
        @unlink($temp);
        @unlink($sum);
    }
}
