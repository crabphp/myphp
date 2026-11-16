<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';
require __DIR__ . '/../src/autoload.php';
use PhpUi\App;
use function PhpUi\{col, image, row};

$tmp = getenv('PHPUI_TEST_TMP');
// A 200x100 picture: left half red, right half blue.
$png = "$tmp/picture.png";
exec('magick -size 100x100 xc:#ff0000 -size 100x100 xc:#0000ff +append ' . escapeshellarg($png), result_code: $code);
same(0, $code, 'make a test picture');
exec('magick -size 20x20 xc:#00ff00 ' . escapeshellarg("$tmp/picture.webp"), result_code: $code);

foreach (["$tmp/missing.png" => 'missing file', "$tmp/picture.webp" => 'unsupported format'] as $path => $what) {
    try { image($path); check(false, "image() rejects a $what"); }
    catch (InvalidArgumentException) { check(true, "image() rejects a $what"); }
}

$app = new App('Image', 400, 600, fn () => col()->full()->bg('#ffffff')->gap(10)->itemsStart()
    ->child(image($png)->id('natural'))
    ->child(image($png)->w(100)->id('by-width'))
    ->child(image($png)->h(40)->id('by-height'))
    ->child(col()->w(300)->child(image($png)->w('100%')->id('percent')))
    ->child(row()->gap(10)
        ->child(image($png)->size(80)->fit('cover')->id('cover'))
        ->child(image($png)->size(80)->id('contain'))
        ->child(image($png)->size(80)->fit('fill')->rounded(40)->id('round'))));
$app->frame();
$box = fn (string $id) => [$app->find($id)->w, $app->find($id)->h];
same([200.0, 100.0], $box('natural'), 'an image shows at its pixel size by default');
same([100.0, 50.0], $box('by-width'), 'a width keeps the aspect ratio');
same([80.0, 40.0], $box('by-height'), 'a height keeps the aspect ratio');
same([300.0, 150.0], $box('percent'), 'a percent width keeps the aspect ratio');

$shot = "$tmp/image.png";
$app->screenshot($shot, 400, 600, 1);
$at = function (string $id, float $fx, float $fy) use ($app, $shot) {
    $e = $app->find($id);
    return pixel($shot, (int) ($e->x + $e->w * $fx), (int) ($e->y + $e->h * $fy));
};
same('#ff0000', $at('natural', 0.25, 0.5), 'the left half is red');
same('#0000ff', $at('natural', 0.75, 0.5), 'the right half is blue');
same('#ff0000', $at('cover', 0.1, 0.1), 'cover fills the whole box');
same('#ffffff', $at('contain', 0.5, 0.1), 'contain leaves the box edges empty');
same('#ff0000', $at('contain', 0.25, 0.5), 'contain shows the picture in the middle');
same('#ffffff', $at('round', 0.03, 0.03), 'rounded corners clip the picture');
same('#0000ff', $at('round', 0.75, 0.5), 'fill stretches the picture');
