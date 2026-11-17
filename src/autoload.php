<?php

declare(strict_types=1);

if (!extension_loaded('phpsdl')) {
    fwrite(STDERR, "myphp needs its runtime (the phpsdl extension): run apps with \"myphp run\"\n");
    exit(1);
}
if (class_exists(\PhpUi\App::class, false)) return;

require __DIR__ . '/DBus.php';
require __DIR__ . '/Desktop.php';
require __DIR__ . '/Theme.php';
require __DIR__ . '/Key.php';
require __DIR__ . '/Element.php';
require __DIR__ . '/Component.php';
require __DIR__ . '/State.php';
require __DIR__ . '/Canvas.php';
require __DIR__ . '/Painter.php';
require __DIR__ . '/HotReload.php';
require __DIR__ . '/Layout.php';
require __DIR__ . '/AccessKit.php';
require __DIR__ . '/Window.php';
require __DIR__ . '/SdlWindow.php';
require __DIR__ . '/App.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/components.php';
require __DIR__ . '/charts.php';

// HTTPS: the runtime's OpenSSL and curl find no certificates on their own on most
// systems. Point them at the system's CA bundle, or the Mozilla bundle in assets.
if (extension_loaded('openssl') && !getenv('SSL_CERT_FILE') && !ini_get('openssl.cafile')) {
    $bundle = null;
    foreach (['/etc/ssl/certs/ca-certificates.crt', '/etc/pki/tls/certs/ca-bundle.crt', '/etc/ssl/ca-bundle.pem',
        '/etc/pki/tls/cacert.pem', '/etc/ssl/cert.pem', '/usr/local/etc/openssl/cert.pem'] as $candidate) {
        if (PHP_OS_FAMILY !== 'Windows' && is_readable($candidate)) { $bundle = $candidate; break; }
    }
    $bundle ??= PhpUi\realFile(dirname(__DIR__) . '/assets/certs/cacert.pem');
    putenv("SSL_CERT_FILE=$bundle");
    unset($bundle, $candidate);
}

