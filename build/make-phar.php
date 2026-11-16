<?php
// Packs the UI, assets and runnable examples into one dashboard.phar.
$out = __DIR__ . '/dashboard.phar';
@unlink($out);
$phar = new Phar($out);
$phar->startBuffering();
$root = dirname(__DIR__);
foreach (['src', 'assets'] as $dir) $phar->buildFromDirectory($root, '#^' . preg_quote($root, '#') . "/$dir/#");
foreach (['showcase', 'desktop', 'todo'] as $example) $phar->addFile("$root/examples/$example.php", "examples/$example.php");
$phar->setStub(<<<'PHP'
#!/usr/bin/env php
<?php
Phar::mapPhar('dashboard.phar');
$example = getenv('PHPUI_EXAMPLE') ?: 'showcase';
if (!in_array($example, ['showcase', 'desktop', 'todo'], true)) throw new InvalidArgumentException('Unknown PHPUI_EXAMPLE');
require 'phar://dashboard.phar/examples/' . $example . '.php';
__HALT_COMPILER();
PHP);
$phar->stopBuffering();
chmod($out, 0755);
