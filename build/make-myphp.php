<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$dir = getenv('MYPHP_BUILD_DIR') ?: __DIR__;
if (!is_dir($dir) && !mkdir($dir, 0777, true)) throw new RuntimeException("Cannot create $dir");
$sfx = $argv[1] ?? '';
$bytes = file_get_contents($sfx);
$windows = str_starts_with((string) $bytes, 'MZ');
if (!$windows && !in_array(substr((string) $bytes, 0, 4), ["\x7fELF", "\xcf\xfa\xed\xfe", "\xca\xfe\xba\xbe"], true)) {
    throw new RuntimeException('micro.sfx is not an ELF, Mach-O or Windows executable');
}
$out = "$dir/myphp.phar";
if (is_file($out)) unlink($out);
$phar = new Phar($out);
$phar->startBuffering();
foreach (['src', 'assets', 'cli'] as $folder) {
    $phar->buildFromDirectory($root, '#^' . preg_quote($root, '#') . "/$folder/#");
}
$phar->addFromString('version', getenv('MYPHP_VERSION') ?: 'dev');
$phar->addFromString('sfx-size', (string) strlen($bytes));
$phar->setStub("<?php Phar::mapPhar('myphp.phar'); require 'phar://myphp.phar/cli/main.php'; __HALT_COMPILER();");
$phar->stopBuffering();
$ini = "phar.readonly=0\n";
$binary = $bytes . "\xfd\xf6\x69\xe6" . pack('N', strlen($ini)) . $ini . file_get_contents($out);
$target = "$dir/myphp" . ($windows ? '.exe' : '');
if (file_put_contents($target, $binary) !== strlen($binary)) throw new RuntimeException('Cannot write myphp binary');
chmod($target, 0755);
printf("%s (%.1f MB)\n", $target, strlen($binary) / 1000000);
