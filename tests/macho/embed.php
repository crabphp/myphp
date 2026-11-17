<?php
// php tests/macho/embed.php <runtime> <payload text> <output>: myphpMachoEmbed, as myphp build does.
const MYPHP_ROOT = __DIR__ . '/../..';
require __DIR__ . '/../../cli/package.php';
$out = myphpMachoEmbed(file_get_contents($argv[1]), $argv[2]);
if ($out === null) { fwrite(STDERR, "cannot embed\n"); exit(1); }
file_put_contents($argv[3], $out);
chmod($argv[3], 0755);
