<?php

declare(strict_types=1);

namespace PhpUi;

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
