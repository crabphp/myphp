<?php

declare(strict_types=1);

namespace PhpUi;

/** Export a bundled asset for native APIs and child processes that cannot read phar://. */
function realFile(string $path): string
{
    if (!str_starts_with($path, 'phar://')) return $path;
    $data = file_get_contents($path);
    if ($data === false) throw new \RuntimeException("Cannot read bundled asset: $path");
    $base = getenv('XDG_CACHE_HOME') ?: (getenv('HOME') ? getenv('HOME') . (PHP_OS_FAMILY === 'Darwin' ? '/Library/Caches' : '/.cache') : sys_get_temp_dir());
    $dir = "$base/phpui/" . substr(hash('sha256', $data), 0, 20);
    $out = "$dir/" . basename($path);
    if (!is_file($out)) {
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) throw new \RuntimeException('Cannot create asset cache');
        $tmp = tempnam($dir, '.asset-');
        if ($tmp === false) throw new \RuntimeException('Cannot create asset cache file');
        try {
            if (file_put_contents($tmp, $data) !== strlen($data) || !rename($tmp, $out)) throw new \RuntimeException('Cannot export bundled asset');
        } finally { if (is_file($tmp)) unlink($tmp); }
    }
    return $out;
}

// Primitives ----------------------------------------------------------------

function div(): Element
{
    return new Element('div');
}

function row(): Element
{
    return div()->row();
}

function col(): Element
{
    return div()->col();
}

function text(string $text): Element
{
    return new Element('text', $text);
}

/** A one-line text field. Its state lives in your code: pass the value, update it in onInput. */
function input(string $id, string $value, callable $onInput): Element
{
    return (new Element('input', $value))->id($id)->onInput($onInput);
}

/** A box you draw yourself: $draw(VGContext $vg, Element $box, Painter $painter). */
function canvas(callable $draw): Element
{
    $e = new Element('canvas');
    $e->draw = $draw(...);
    return $e;
}

/** A Lucide icon in a box of its size. */
function icon(string $name, float $size = 16, ?string $color = null): Element
{
    return div()->size($size)->icon($name, $color ?? t('foreground'), $size);
}

function spacer(): Element
{
    return div()->grow();
}
