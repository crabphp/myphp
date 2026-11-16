<?php

declare(strict_types=1);

/*
 * Two runtimes. The core one (the myphp you install, about 12 MB) has PHP and the
 * UI toolkit. The full one (myphp-full, about 35 MB) adds HTTPS, SQLite, XML and
 * other extensions. Apps that need the full runtime are found from composer.json
 * and their code; run and build then hand over to myphp-full, downloaded once.
 */

/** Extensions only the full runtime has. */
const MYPHP_FULL_EXTENSIONS = ['openssl', 'curl', 'pdo', 'pdo_sqlite', 'sqlite3', 'dom', 'xml', 'simplexml', 'xmlreader',
    'xmlwriter', 'libxml', 'fileinfo', 'iconv', 'bcmath', 'sockets'];

/** Functions, by prefix, and classes that come from those extensions. */
const MYPHP_FULL_FUNCTIONS = ['curl_', 'openssl_', 'simplexml_', 'xml_', 'xmlwriter_', 'libxml_', 'finfo_', 'iconv', 'socket_',
    'bcadd', 'bcsub', 'bcmul', 'bcdiv', 'bcmod', 'bcpow', 'bcsqrt', 'bccomp', 'bcscale', 'mime_content_type', 'dom_import_simplexml'];
const MYPHP_FULL_CLASSES = ['sqlite3', 'domdocument', 'domxpath', 'domelement', 'simplexmlelement', 'xmlreader', 'xmlwriter', 'finfo', 'curlfile'];

function myphpFlavor(): string
{
    $file = MYPHP_ROOT . '/flavor';
    return is_file($file) ? trim((string) file_get_contents($file)) : (extension_loaded('openssl') ? 'full' : 'core');
}

/**
 * Why the project needs the full runtime, or null when the core one is enough.
 * composer.json can also say so: "extra": {"myphp": {"runtime": "full"}} (or "core").
 */
function myphpNeedsFull(string $project): ?string
{
    $json = is_file("$project/composer.json") ? json_decode((string) file_get_contents("$project/composer.json"), true) : null;
    $choice = $json['extra']['myphp']['runtime'] ?? null;
    if ($choice === 'full') return 'composer.json asks for it';
    if ($choice === 'core') return null;

    // Composer's own way to ask for an extension, from the app and its packages.
    $lock = is_file("$project/composer.lock") ? json_decode((string) file_get_contents("$project/composer.lock"), true) : null;
    $requires = [['composer.json', $json['require'] ?? []]];
    foreach ($lock['packages'] ?? [] as $package) $requires[] = [$package['name'] ?? 'a package', $package['require'] ?? []];
    foreach ($requires as [$who, $require]) {
        foreach (array_keys((array) $require) as $name) {
            if (str_starts_with($name, 'ext-') && in_array(strtolower(substr($name, 4)), MYPHP_FULL_EXTENSIONS, true)) {
                return "$who requires $name";
            }
        }
    }
    return myphpScanForFull($project);
}

/**
 * Look through the project's PHP code for full-runtime functions, classes, sockets and SQLite DSNs:
 * the app's own files token by token, vendor/ with a quick text search. Cached
 * until a file changes.
 */
function myphpScanForFull(string $project): ?string
{
    $files = [];
    $walk = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($project, FilesystemIterator::SKIP_DOTS),
        fn (SplFileInfo $f) => !str_starts_with($f->getFilename(), '.') && !($f->isDir() && $f->getPathname() === "$project/build"),
    ));
    foreach ($walk as $file) {
        if ($file->isFile() && strtolower($file->getExtension()) === 'php') $files[$file->getPathname()] = $file->getMTime() . ':' . $file->getSize();
        if (count($files) > 50000) break;
    }
    $cache = sys_get_temp_dir() . '/myphp-scan-' . md5($project) . '.json';
    // The scanner itself is part of the signature, so a new myphp scans again.
    $signature = md5(md5_file(__FILE__) . serialize($files));
    $cached = is_file($cache) ? json_decode((string) file_get_contents($cache), true) : null;
    if (($cached['signature'] ?? null) === $signature) return $cached['reason'];
    $reason = myphpScanFiles($project, array_keys($files));
    @file_put_contents($cache, json_encode(['signature' => $signature, 'reason' => $reason]));
    return $reason;
}

function myphpScanFiles(string $project, array $paths): ?string
{
    $vendorPattern = '/\\b(?:curl_\\w+|openssl_\\w+|simplexml_\\w+|finfo_\\w+|bcadd|bcmul)\\s*\\(|new\\s+\\\\?(?:SQLite3|DOMDocument|XMLReader|XMLWriter|finfo)\\b|[\'"](?:ssl|tls):\\/\\/|[\'"]sqlite:/i';
    foreach ($paths as $path) {
        $where = substr($path, strlen($project) + 1);
        if (str_starts_with($where, 'vendor/')) {
            if (str_starts_with($where, 'vendor/crabphp/myphp/') || str_starts_with($where, 'vendor/composer/')) continue;
            if (preg_match($vendorPattern, (string) file_get_contents($path), $m)) return "$where uses " . trim($m[0], "('\" ");
            continue;
        }
        if ($reason = myphpScanCode((string) file_get_contents($path), $where)) return $reason;
    }
    return null;
}

function myphpScanCode(string $code, string $where): ?string
{
    $tokens = @token_get_all($code);
    foreach ($tokens as $i => $token) {
        if (!is_array($token)) continue;
        [$type, $text] = $token;
        if ($type === T_CONSTANT_ENCAPSED_STRING) {
            $value = strtolower(substr($text, 1, 10));
            // Not https:// itself: apps open links in the browser without TLS of their own.
            foreach (['ssl://', 'tls://', 'sqlite:'] as $scheme) {
                if (str_starts_with($value, $scheme)) return "$where uses " . rtrim($scheme, '/');
            }
            continue;
        }
        if ($type !== T_STRING && $type !== T_NAME_FULLY_QUALIFIED) continue;
        $name = strtolower(ltrim($text, '\\'));
        $next = $tokens[$i + 1] ?? null;
        $called = $next === '(' || (is_array($next) && $next[0] === T_WHITESPACE && ($tokens[$i + 2] ?? null) === '(');
        // Not a declaration (function curl_x) or a method ($obj->curl_x(), Foo::curl_x()).
        for ($j = $i - 1; $j >= 0 && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE; $j--);
        $before = $j >= 0 && is_array($tokens[$j]) ? $tokens[$j][0] : null;
        if (in_array($before, [T_FUNCTION, T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_CONST], true)) continue;
        if ($called) {
            foreach (MYPHP_FULL_FUNCTIONS as $prefix) if (str_starts_with($name, $prefix)) return "$where calls $text()";
        }
        if (in_array($name, MYPHP_FULL_CLASSES, true)) return "$where uses $text";
    }
    return null;
}

/** The myphp-full binary matching this version: MYPHP_FULL, the cache, or a download. */
function myphpFullBinary(): ?string
{
    if ($path = getenv('MYPHP_FULL')) return is_file($path) ? $path : null;
    $version = myphpVersion();
    $asset = myphpAsset();
    if ($asset === null || $version === 'dev') return null;
    $asset = str_replace('myphp-', 'myphp-full-', $asset);
    $base = getenv('XDG_CACHE_HOME') ?: match (true) {
        PHP_OS_FAMILY === 'Windows' => getenv('LOCALAPPDATA') ?: sys_get_temp_dir(),
        PHP_OS_FAMILY === 'Darwin' => getenv('HOME') . '/Library/Caches',
        default => getenv('HOME') . '/.cache',
    };
    $dir = "$base/myphp/$version";
    $binary = "$dir/" . (PHP_OS_FAMILY === 'Windows' ? 'myphp-full.exe' : 'myphp-full');
    if (is_file($binary)) return $binary;

    $releases = rtrim(getenv('MYPHP_RELEASES') ?: 'https://github.com/crabphp/myphp/releases', '/');
    $url = str_starts_with($version, 'nightly-') ? "$releases/download/nightly/$asset" : "$releases/download/v$version/$asset";
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) return null;
    fwrite(STDERR, "myphp: downloading the full runtime (once): $url\n");
    $temp = "$binary.part";
    try {
        if (!myphpDownload($url, $temp)) return null;
        if (myphpDownload("$url.sha256", "$temp.sha256")) {
            $expected = strtok((string) file_get_contents("$temp.sha256"), " \t\r\n");
            if (!hash_equals((string) $expected, hash_file('sha256', $temp))) { myphpError('checksum mismatch for the full runtime'); return null; }
        }
        @chmod($temp, 0755);
        return rename($temp, $binary) ? $binary : null;
    } finally {
        @unlink($temp);
        @unlink("$temp.sha256");
    }
}

/** Run a myphp command with the full runtime, passing the terminal through. */
function myphpDelegate(array $args, string $reason): int
{
    $full = myphpFullBinary();
    if ($full === null) {
        return myphpError("this app needs the full runtime ($reason), and myphp-full is not available: "
            . 'install a released myphp, or set MYPHP_FULL to a myphp-full binary');
    }
    fwrite(STDERR, "myphp: using the full runtime: $reason\n");
    $process = proc_open([$full, ...$args], [STDIN, STDOUT, STDERR], $pipes, null, ['MYPHP_DELEGATED' => '1'] + getenv());
    return is_resource($process) ? proc_close($process) : myphpError('cannot start the full runtime');
}

/** Take --full and --core out of the arguments: 'full', 'core' or null. */
function myphpRuntimeFlag(array &$args): ?string
{
    $flag = null;
    $args = array_values(array_filter($args, function ($arg) use (&$flag) {
        if ($arg !== '--full' && $arg !== '--core') return true;
        $flag = substr($arg, 2);
        return false;
    }));
    return $flag;
}

/** A hint for errors that mean an extension is missing from the core runtime. */
function myphpMissingExtensionHint(Throwable $e): ?string
{
    if (myphpFlavor() === 'full') return null;
    $message = $e->getMessage();
    $missing = preg_match('/^Call to undefined function (\w+)\(/', $message, $m) && array_filter(MYPHP_FULL_FUNCTIONS, fn ($p) => str_starts_with(strtolower($m[1]), $p))
        || preg_match('/^Class "\\\\?(\w+)" not found/', $message, $m) && (in_array(strtolower($m[1]), MYPHP_FULL_CLASSES, true) || $m[1] === 'PDO')
        || str_contains($message, 'could not find driver')
        || str_contains($message, 'Unable to find the wrapper "https"');
    return $missing ? 'This needs the full runtime (HTTPS, SQLite, XML...): run with "myphp run --full", build with "myphp build --full",'
        . ' or add "extra": {"myphp": {"runtime": "full"}} to composer.json.' : null;
}
