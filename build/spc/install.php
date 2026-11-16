<?php
// Copy myphp's static-php-cli definitions (SDL3, ext-phpsdl) into a static-php-cli v3
// checkout: php build/spc/install.php SPC_DIR
$spc = rtrim($argv[1] ?? '', '/');
if (!is_dir("$spc/config/pkg")) { fwrite(STDERR, "usage: install.php STATIC_PHP_CLI_DIR\n"); exit(2); }
$root = __DIR__;
$ext = realpath("$root/../../ext/phpsdl");
$walk = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($walk as $file) {
    $relative = substr($file->getPathname(), strlen($root) + 1);
    if ($relative === 'install.php') continue;
    $target = "$spc/$relative";
    @mkdir(dirname($target), 0777, true);
    file_put_contents($target, str_replace('@MYPHP_EXT_PHPSDL@', $ext, file_get_contents($file->getPathname())));
    echo "$relative\n";
}
