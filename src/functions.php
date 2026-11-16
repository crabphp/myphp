<?php

declare(strict_types=1);

namespace PhpUi;

/** Export a bundled asset for native APIs and child processes that cannot read phar://. */
function realFile(string $path): string
{
    if (!str_starts_with($path, 'phar://')) return $path;
    $data = file_get_contents($path);
    if ($data === false) throw new \RuntimeException("Cannot read bundled asset: $path");
    $base = getenv('XDG_CACHE_HOME') ?: match (true) {
        PHP_OS_FAMILY === 'Windows' => getenv('LOCALAPPDATA') ?: sys_get_temp_dir(),
        !getenv('HOME') => sys_get_temp_dir(),
        default => getenv('HOME') . (PHP_OS_FAMILY === 'Darwin' ? '/Library/Caches' : '/.cache'),
    };
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

/**
 * App state. Pass an object for autocompletion and types:
 * `$s = state(new class { public int $count = 0; })`, then `$s->count++` in a handler.
 * An array gives a quick untyped store that also redraws on changes made outside
 * event handlers. With a $key, the same state comes back on every call, so a
 * component function can keep its own state between renders.
 *
 * @template T of object
 * @param T|array<string, mixed> $initial
 * @return ($initial is array ? State : T)
 */
function state(object|array $initial = [], ?string $key = null): object
{
    if ($key !== null) return State::keyed($key, $initial);
    return is_array($initial) ? new State($initial) : $initial;
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

/**
 * A PNG, JPEG, BMP or GIF (first frame) file. Set ->w() or ->h() and the other side
 * follows the picture's aspect ratio; with neither it shows at its pixel size.
 * ->fit('cover') fills the box and crops, 'contain' (default) shows it all, 'fill' stretches.
 */
function image(string $path): Element
{
    static $sizes = [];
    if (!isset($sizes[$path])) {
        $info = @getimagesize($path);
        if (!$info || !in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_BMP], true)) {
            throw new \InvalidArgumentException("Cannot show image $path: use a PNG, JPEG, BMP or GIF file");
        }
        $sizes[$path] = [(float) $info[0], (float) $info[1]];
    }
    $e = new Element('image', $path);
    $e->imageSize = $sizes[$path];
    return $e;
}

/** A Lucide icon in a box of its size. */
function icon(string $name, float $size = 16, ?string $color = null): Element
{
    return div()->size($size)->icon($name, $color ?? t('foreground'), $size);
}

/** A grid of $columns equal columns: grid(3)->gap(16)->children($cards). */
function grid(int $columns): Element
{
    return div()->grid($columns);
}

function spacer(): Element
{
    return div()->grow();
}
