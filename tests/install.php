<?php

declare(strict_types=1);

require __DIR__ . '/lib.php';

$root = getenv('PHPUI_TEST_TMP') . '/install';
mkdir($root);
$binary = getenv('MYPHP_BIN');
if (!$binary) {
    [$code, $out, $err] = cliProcess([__DIR__ . '/../build/make-myphp.sh'], env: [
        'MYPHP_BUILD_DIR' => "$root/build", 'PHP' => PHP_BINARY,
    ]);
    check($code === 0, "build installer fixture: $out$err");
    if ($code !== 0) exit(1);
    $binary = "$root/build/myphp";
}
mkdir("$root/src");
copy($binary, "$root/src/myphp");
$checksum = hash_file('sha256', "$root/src/myphp") . "  myphp-linux-x86_64\n";
file_put_contents("$root/src/myphp.sha256", $checksum);
$env = ['MYPHP_DOWNLOAD_URL' => "file://$root/src/myphp", 'MYPHP_INSTALL_DIR' => "$root/bin"];
$installer = ['sh', __DIR__ . '/../install.sh'];
[$code, $out, $err] = cliProcess($installer, env: $env);
same(0, $code, "install local release: $err");
check(str_contains($out, 'myphp ') && str_contains($out, 'Add to your shell profile'), 'installer prints version and PATH hint');
same(0, cliProcess(["$root/bin/myphp", 'version'])[0], 'installed binary works');
same([], glob("$root/bin/.myphp.*"), 'successful install removes temporary files');
[$code, $out, $err] = cliProcess($installer, env: ['PATH' => "$root/bin:" . getenv('PATH')] + $env);
same(0, $code, "second install atomically replaces binary: $err");
check(!str_contains($out, 'Add to your shell profile'), 'no hint when install directory is on PATH');
file_put_contents("$root/src/myphp.sha256", str_repeat('0', 64) . "  ignored-filename\n");
[$code, $out, $err] = cliProcess($installer, env: ['MYPHP_INSTALL_DIR' => "$root/bad"] + $env);
same(1, $code, 'bad checksum fails');
check(str_contains($err, 'myphp: checksum mismatch'), 'checksum diagnostic');
same(['.', '..'], scandir("$root/bad"), 'bad checksum leaves no installed or temp files');
[$code] = cliProcess($installer, env: $env);
same(1, $code, 'bad upgrade fails');
same(hash_file('sha256', $binary), hash_file('sha256', "$root/bin/myphp"), 'failed upgrade preserves existing installation');
unlink("$root/src/myphp.sha256");
[$code, $out, $err] = cliProcess($installer, env: $env);
same(0, $code, 'missing checksum still installs');
check(str_contains($err, 'warning: checksum unavailable'), 'missing checksum warns');
[$code, $out, $err] = cliProcess($installer, env: ['MYPHP_DOWNLOAD_URL' => "file://$root/missing", 'MYPHP_INSTALL_DIR' => "$root/missing-bin"] + $env);
same(1, $code, 'download failure fails');
same(['.', '..'], scandir("$root/missing-bin"), 'failed download removes temporary files');
same(0, cliProcess(['sh', '-n', __DIR__ . '/../install.sh'])[0], 'POSIX shell syntax');
if (is_executable('/usr/bin/shellcheck')) {
    [$code, $out, $err] = cliProcess(['/usr/bin/shellcheck', __DIR__ . '/../install.sh']);
    same(0, $code, "shellcheck: $out$err");
}
