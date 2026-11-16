<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';
require __DIR__ . '/../src/autoload.php';
use PhpUi\Theme;

// Text in the built-in themes meets WCAG AA (4.5:1); focus rings and input borders,
// which only need to be seen, meet the 3:1 of WCAG's non-text contrast rule.
function luminance(string $hex): float
{
    $c = array_map(fn ($v) => hexdec($v) / 255, str_split(substr(ltrim($hex, '#'), 0, 6), 2));
    $c = array_map(fn ($v) => $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4, $c);
    return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
}
function contrast(string $a, string $b): float
{
    [$hi, $lo] = [max(luminance($a), luminance($b)), min(luminance($a), luminance($b))];
    return ($hi + 0.05) / ($lo + 0.05);
}
$text = [
    ['foreground', 'background'], ['card-foreground', 'card'], ['popover-foreground', 'popover'],
    ['primary-foreground', 'primary'], ['secondary-foreground', 'secondary'], ['accent-foreground', 'accent'],
    ['muted-foreground', 'background'], ['muted-foreground', 'muted'], ['muted-foreground', 'card'], ['destructive', 'background'],
];
foreach (['light', 'dark'] as $mode) {
    Theme::set($mode);
    foreach ($text as [$fg, $bg]) {
        $ratio = contrast(PhpUi\t($fg), PhpUi\t($bg));
        check($ratio >= 4.5, sprintf('%s: %s on %s is %.2f:1 (text needs 4.5)', $mode, $fg, $bg, $ratio));
    }
    $ratio = contrast(PhpUi\t('ring'), PhpUi\t('background'));
    check($ratio >= 3, sprintf('%s: the focus ring color is %.2f:1 on the background (needs 3)', $mode, $ratio));
}
