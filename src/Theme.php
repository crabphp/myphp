<?php

declare(strict_types=1);

namespace PhpUi;

/** shadcn/ui's default theme (neutral base color), light and dark, as hex. */
final class Theme
{
    public static string $mode = 'light';

    private const LIGHT = [
        'background' => '#ffffff', 'foreground' => '#0a0a0a',
        'card' => '#ffffff', 'card-foreground' => '#0a0a0a',
        'popover' => '#ffffff', 'popover-foreground' => '#0a0a0a',
        'primary' => '#171717', 'primary-foreground' => '#fafafa',
        'secondary' => '#f5f5f5', 'secondary-foreground' => '#171717',
        'muted' => '#f5f5f5', 'muted-foreground' => '#737373',
        'accent' => '#f5f5f5', 'accent-foreground' => '#171717',
        'destructive' => '#e7000b',
        'border' => '#e5e5e5', 'input' => '#e5e5e5', 'ring' => '#a1a1a1',
        'chart-1' => '#f54a00', 'chart-2' => '#009689', 'chart-3' => '#104e64', 'chart-4' => '#ffb900', 'chart-5' => '#fe9a00',
        'sidebar' => '#fafafa', 'sidebar-foreground' => '#0a0a0a', 'sidebar-accent' => '#f5f5f5', 'sidebar-border' => '#e5e5e5',
        'success' => '#16a34a', 'overlay' => '#00000080',
    ];

    private const DARK = [
        'background' => '#0a0a0a', 'foreground' => '#fafafa',
        'card' => '#171717', 'card-foreground' => '#fafafa',
        'popover' => '#171717', 'popover-foreground' => '#fafafa',
        'primary' => '#e5e5e5', 'primary-foreground' => '#171717',
        'secondary' => '#262626', 'secondary-foreground' => '#fafafa',
        'muted' => '#262626', 'muted-foreground' => '#a1a1a1',
        'accent' => '#262626', 'accent-foreground' => '#fafafa',
        'destructive' => '#ff6467',
        'border' => '#ffffff1a', 'input' => '#ffffff26', 'ring' => '#737373',
        'chart-1' => '#1447e6', 'chart-2' => '#00bc7d', 'chart-3' => '#fe9a00', 'chart-4' => '#ad46ff', 'chart-5' => '#ff2056',
        'sidebar' => '#171717', 'sidebar-foreground' => '#fafafa', 'sidebar-accent' => '#262626', 'sidebar-border' => '#ffffff1a',
        'success' => '#22c55e', 'overlay' => '#000000a6',
    ];

    public static function get(string $token): string
    {
        return (self::$mode === 'dark' ? self::DARK : self::LIGHT)[$token]
            ?? throw new \InvalidArgumentException("Unknown theme token: $token");
    }

    public static function dark(): bool
    {
        return self::$mode === 'dark';
    }

    public static function toggle(): void
    {
        self::$mode = self::dark() ? 'light' : 'dark';
    }

    /** Blend $b over $a by $amount (0..1), as an opaque color. */
    public static function mix(string $a, string $b, float $amount): string
    {
        $rgb = fn (string $hex) => array_map('hexdec', str_split(substr(ltrim($hex, '#'), 0, 6), 2));
        [$x, $y] = [$rgb($a), $rgb($b)];
        return '#' . implode('', array_map(fn ($i) => sprintf('%02x', (int) round($x[$i] + ($y[$i] - $x[$i]) * $amount)), [0, 1, 2]));
    }

    /** A color with its alpha multiplied: alpha('#171717', 0.9) is shadcn's bg-primary/90. */
    public static function alpha(string $hex, float $alpha): string
    {
        $h = ltrim($hex, '#');
        if (strlen($h) === 3) $h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
        $a = strlen($h) === 8 ? hexdec(substr($h, 6)) / 255 : 1.0;
        return '#' . substr($h, 0, 6) . sprintf('%02x', (int) round($a * $alpha * 255));
    }
}

/** A theme color, optionally with opacity: t('primary', 0.9). */
function t(string $token, ?float $alpha = null): string
{
    $c = Theme::get($token);
    return $alpha === null ? $c : Theme::alpha($c, $alpha);
}
