<?php
// Draws myphp's default app icon (assets/icon.png) with the toolkit's Canvas.
//   php84 build/make-icon.php assets/icon.png   (from the repository, with a display)
require dirname(__DIR__) . '/src/autoload.php';
use PhpUi\Canvas;
$w = new PhpSdl\Window('icon', 300, 300);
foreach (['regular' => 'Geist-Regular', 'bold' => 'Geist-Bold'] as $n => $f) PhpSdl\Text::addFont($n, dirname(__DIR__) . "/assets/fonts/$f.ttf");
$r = new PhpSdl\Renderer();
$w->beginCapture(1024, 1024, [0, 0, 0, 0]);
$r->begin(1024, 1024, 1);
$c = new Canvas($r, 0, 0, 1024, 1024);
// Apple's grid: an 824 px squircle in the 1024 px canvas, with a soft shadow.
$c->save();
$c->shadowColor = 'rgba(0, 0, 0, 0.30)';
$c->shadowBlur = 28;
$c->shadowOffsetY = 12;
$g = $c->createLinearGradient(0, 100, 0, 924);
$g->addColorStop(0, '#8b7cf8');
$g->addColorStop(1, '#4f46e5');
$c->fillStyle = $g;
$c->beginPath();
$c->roundRect(100, 100, 824, 824, 185);
$c->fill();
$c->restore();
// A light sheen at the top.
$sheen = $c->createLinearGradient(0, 100, 0, 520);
$sheen->addColorStop(0, 'rgba(255, 255, 255, 0.22)');
$sheen->addColorStop(1, 'rgba(255, 255, 255, 0)');
$c->fillStyle = $sheen;
$c->beginPath();
$c->roundRect(100, 100, 824, 824, 185);
$c->fill();
// An app window: frame, title bar, three dots, and content lines.
$c->strokeStyle = '#ffffff';
$c->fillStyle = '#ffffff';
$c->lineWidth = 40;
$c->lineJoin = 'round';
$c->beginPath();
$c->roundRect(262, 300, 500, 424, 56);
$c->stroke();
$c->beginPath();
$c->moveTo(262, 412);
$c->lineTo(762, 412);
$c->stroke();
foreach ([336, 396, 456] as $x) { $c->beginPath(); $c->arc($x, 356, 17, 0, 2 * M_PI); $c->fill(); }
$c->lineCap = 'round';
$c->lineWidth = 34;
foreach ([[340, 500, 600], [340, 568, 680], [340, 636, 540]] as [$x0, $y, $x1]) {
    $c->globalAlpha = 0.85;
    $c->beginPath(); $c->moveTo($x0, $y); $c->lineTo($x1, $y); $c->stroke();
}
$r->flush();
$w->endCapture($argv[1]);
