<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';
require __DIR__ . '/../src/autoload.php';

// canvas(): the HTML canvas 2D API, drawn and probed pixel by pixel.
use PhpUi\{App, Canvas, CssColor, Path2D};
use function PhpUi\{canvas, col};

$GLOBALS['tmp'] = $tmp = getenv('PHPUI_TEST_TMP') ?: sys_get_temp_dir();
$draw = null;
$app = new App('Canvas', 400, 300, function () use (&$draw) {
    return col()->bg('#ffffff')->schema([canvas(function (Canvas $c) use (&$draw) { ($draw)($c); })->w(400)->h(300)]);
});

/** Draws with $fn and returns a pixel probe for the frame (1:1 pixels). */
$shot = function (callable $fn, string $name) use ($app, $tmp, &$draw): callable {
    $draw = $fn;
    $file = "$tmp/canvas-$name.png";
    $app->screenshot($file, 400, 300, 1);
    return fn (int $x, int $y) => pixel($file, $x, $y);
};
$near = function (string $hex, array $rgb, int $tolerance = 12): bool {
    $c = sscanf($hex, '#%02x%02x%02x');
    foreach ($rgb as $i => $v) if (abs($c[$i] - $v) > $tolerance) return false;
    return true;
};

// Fills, rules and boxes.
$px = $shot(function (Canvas $c) {
    $c->fillStyle = 'red';
    $c->fillRect(10, 10, 50, 50);
    $c->fillStyle = '#0000ff';
    $c->beginPath();
    $c->arc(120, 35, 25, 0, 2 * M_PI);
    $c->fill();
    // A star: the middle is inside by non-zero, outside by even-odd.
    foreach ([[200, 'nonzero'], [280, 'evenodd']] as [$x, $rule]) {
        $c->beginPath();
        for ($i = 0; $i < 5; $i++) {
            $a = -M_PI / 2 + $i * 4 * M_PI / 5;
            $i ? $c->lineTo($x + 30 * cos($a), 40 + 30 * sin($a)) : $c->moveTo($x + 30 * cos($a), 40 + 30 * sin($a));
        }
        $c->closePath();
        $c->fillStyle = 'green';
        $c->fill($rule);
    }
}, 'fill');
check($near($px(35, 35), [255, 0, 0]), 'fillRect fills');
check($near($px(70, 35), [255, 255, 255]), 'fillRect stops at its edge');
check($near($px(120, 35), [0, 0, 255]), 'arc fills a circle');
check($near($px(120, 5), [255, 255, 255]), 'the circle stays round');
check($near($px(200, 42), [0, 128, 0]), 'non-zero fills the star middle');
check($near($px(280, 42), [255, 255, 255]), 'even-odd leaves the star middle out');

// Strokes: width, dashes, joins.
$px = $shot(function (Canvas $c) {
    $c->strokeStyle = 'black';
    $c->lineWidth = 10;
    $c->beginPath();
    $c->moveTo(20, 50);
    $c->lineTo(180, 50);
    $c->stroke();
    $c->setLineDash([20, 20]);
    $c->beginPath();
    $c->moveTo(20, 100);
    $c->lineTo(180, 100);
    $c->stroke();
    $c->setLineDash([]);
    $c->lineJoin = 'miter';
    $c->beginPath();
    $c->moveTo(220, 150);
    $c->lineTo(260, 60);
    $c->lineTo(300, 150);
    $c->stroke();
}, 'stroke');
check($near($px(100, 50), [0, 0, 0]) && $near($px(100, 46), [0, 0, 0]), 'a stroke covers its width');
check($near($px(100, 40), [255, 255, 255]), 'a stroke stops at half its width');
check($near($px(30, 100), [0, 0, 0]) && $near($px(50, 100), [255, 255, 255]), 'dashes leave gaps');
check($near($px(260, 52), [0, 0, 0]), 'a miter join reaches its tip');

// Gradients, alpha, shadows, clips, transforms.
$px = $shot(function (Canvas $c) {
    $g = $c->createLinearGradient(10, 0, 210, 0);
    $g->addColorStop(0, '#ff0000');
    $g->addColorStop(1, '#0000ff');
    $c->fillStyle = $g;
    $c->fillRect(10, 10, 200, 40);
    $c->globalAlpha = 0.5;
    $c->fillStyle = 'black';
    $c->fillRect(10, 60, 40, 40);
    $c->globalAlpha = 1;
    $c->save();
    $c->beginPath();
    $c->arc(300, 80, 30, 0, 2 * M_PI);
    $c->clip();
    $c->fillStyle = 'green';
    $c->fillRect(250, 30, 100, 100);
    $c->restore();
    $c->fillStyle = 'blue';
    $c->fillRect(250, 130, 20, 20); // the clip ended with restore()
    $c->save();
    $c->translate(100, 200);
    $c->rotate(M_PI / 4);
    $c->fillStyle = 'red';
    $c->fillRect(-20, -20, 40, 40);
    $c->restore();
    $c->shadowColor = 'rgba(0, 0, 0, 1)';
    $c->shadowBlur = 0;
    $c->shadowOffsetX = 20;
    $c->fillStyle = 'white';
    $c->fillRect(200, 180, 30, 30);
}, 'paint');
check($near($px(15, 30), [250, 0, 5], 20) && $near($px(205, 30), [5, 0, 250], 20), 'a linear gradient runs between its stops');
check($near($px(110, 30), [128, 0, 128], 20), 'a gradient blends in between');
check($near($px(30, 80), [128, 128, 128], 6), 'globalAlpha fades');
check($near($px(300, 80), [0, 128, 0]) && $near($px(255, 35), [255, 255, 255]), 'clip() keeps drawing inside the path');
check($near($px(260, 140), [0, 0, 255]), 'restore() ends the clip');
check($near($px(100, 182), [255, 0, 0]) && $near($px(122, 178), [255, 255, 255]), 'rotate() turns the square into a diamond');
check($near($px(240, 195), [0, 0, 0]), 'a shadow is drawn at its offset');

// Text and images.
$px = $shot(function (Canvas $c) {
    $c->font = 'bold 40px sans-serif';
    $c->fillStyle = 'black';
    $c->textBaseline = 'top';
    $c->fillText('HHHH', 10, 10);
    $c->save();
    $c->translate(300, 150);
    $c->rotate(M_PI / 2);
    $c->fillText('HHHH', 0, 0);
    $c->restore();
    // Part of an earlier frame: its red square, scaled up, then the same turned.
    $c->drawImage($GLOBALS['tmp'] . '/canvas-fill.png', 20, 20, 30, 30, 20, 200, 60, 60);
    $c->save();
    $c->translate(150, 230);
    $c->rotate(0.3);
    $c->drawImage($GLOBALS['tmp'] . '/canvas-fill.png', 20, 20, 30, 30, -30, -30, 60, 60);
    $c->restore();
}, 'text');
$dark = fn (string $hex) => $near($hex, [0, 0, 0], 60);
check($dark($px(14, 30)), 'fillText draws text');
check(!$dark($px(14, 4)), 'textBaseline top puts the text below y');
$rotated = false;
for ($y = 160; $y < 250 && !$rotated; $y += 2) for ($x = 262; $x < 300; $x += 2) if ($dark($px($x, $y))) { $rotated = true; break; }
check($rotated, 'rotated text draws as outlines');
check($near($px(50, 230), [255, 0, 0]) && $near($px(85, 230), [255, 255, 255]), 'drawImage draws part of an image, scaled');
check($near($px(150, 230), [255, 0, 0]), 'a turned image draws as a pattern');

// The API without drawing.
$c = new Canvas(new \PhpSdl\Renderer(), 0, 0, 100, 100);
$c->fillStyle = 'red';
$c->save();
$c->fillStyle = 'blue';
$c->translate(5, 5);
$c->restore();
same('red', $c->fillStyle, 'restore() brings back the fill style');
same([1, 0, 0, 1, 0, 0], $c->getTransform(), 'restore() brings back the transform');
$c->beginPath();
$c->rect(10, 10, 20, 20);
check($c->isPointInPath(15, 15) && !$c->isPointInPath(35, 15), 'isPointInPath tests the path');
$c->lineWidth = 4;
check($c->isPointInStroke(10, 20) && !$c->isPointInStroke(20, 20), 'isPointInStroke tests the outline');
$p = new Path2D('M 10 10 h 20 v 20 h -20 Z');
check($c->isPointInPath($p, 15, 15), 'Path2D takes SVG path data');
$arc = new Path2D('M 0 50 A 50 50 0 0 1 100 50');
check($c->isPointInPath($arc, 50, 20) && !$c->isPointInPath($arc, 50, 60), 'SVG arcs bulge the right way');
$c->setLineDash([5, 10, 15]);
same([5.0, 10.0, 15.0, 5.0, 10.0, 15.0], $c->getLineDash(), 'an odd dash list repeats, as canvas does');
$c->font = '20px sans-serif';
$m = $c->measureText('Hello');
check($m->width > 30 && $m->width < 70 && $m->actualBoundingBoxAscent > 10, 'measureText measures');
same([1.0, 0.0, 0.0, 1.0], CssColor::parse('red'), 'named colors');
same([0.0, 1.0, 0.0, 0.5], CssColor::parse('rgba(0, 255, 0, 0.5)'), 'rgba() colors');
check(abs(CssColor::parse('hsl(240, 100%, 50%)')[2] - 1) < 1e-9, 'hsl() colors');
same([1.0, 1.0, 1.0, 0.0], array_map('floatval', CssColor::parse('#fff0')), '#rgba colors');

