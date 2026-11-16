<?php 

/**
 * @generate-function-entries
 * @generate-class-entries
 */

namespace GL\Texture
{
    class Texture2D {
        // public const CHANNEL_R = 1;
        // public const CHANNEL_GRAY = 1;
        // public const CHANNEL_RG = 2;
        // public const CHANNEL_GRAY_ALPHA = 2;
        // public const CHANNEL_RGB = 3;
        // public const CHANNEL_RGBA = 4;

        public static function fromDisk(string $path, int $requestedChannelCount = 0, bool $flipVertically = true) : Texture2D {}
        public static function fromBuffer(int $width, int $height, \GL\Buffer\UByteBuffer $buffer, int $channels = Texture2D::CHANNEL_RGBA) : Texture2D {}
        public static function fromBufferHDR(int $width, int $height, \GL\Buffer\FloatBuffer $buffer, int $channels = Texture2D::CHANNEL_RGBA) : Texture2D {}
        public function buffer() : \GL\Buffer\UByteBuffer|\GL\Buffer\FloatBuffer {}
        public function width() : int {}
        public function height() : int {}
        public function channels() : int {}
        public function isHDR() : bool {}

        public function writeJPG(string $path, int $quality = 100) : void {}
        public function writePNG(string $path) : void {}
        public function writeBMP(string $path) : void {}
        public function writeTGA(string $path) : void {}
        public function writeHDR(string $path) : void {}
    }
}

namespace GL
{
    class Noise {
        public static function perlin(float $x, float $y, float $z, int $wrapX = 0, int $wrapY = 0, int $wrapZ = 0, int $seed = 0) : float {}
        public static function ridge(float $x, float $y, float $z, float $lacunarity = 2.0, float $gain = 0.5, float $offset = 1.0, int $octaves = 6) : float {}
        public static function fbm(float $x, float $y, float $z, float $lacunarity = 2.0, float $gain = 0.5, int $octaves = 6) : float {}
        public static function turbulence(float $x, float $y, float $z, float $lacunarity = 2.0, float $gain = 0.5, int $octaves = 6) : float {}

        public static function perlinFill2D(\GL\Buffer\FloatBuffer $buffer, int $width, int $height, float $scale, float $offsetX = 0, float $offsetY = 0, int $wrapX = 0, int $wrapY = 0, int $seed = 0) : void {}
        public static function ridgeFill2D(\GL\Buffer\FloatBuffer $buffer, int $width, int $height, float $scale, float $offsetX = 0, float $offsetY = 0, float $lacunarity = 2.0, float $gain = 0.5, float $offset = 1.0, int $octaves = 6) : void {}
        public static function fbmFill2D(\GL\Buffer\FloatBuffer $buffer, int $width, int $height, float $scale, float $offsetX = 0, float $offsetY = 0, float $lacunarity = 2.0, float $gain = 0.5, int $octaves = 6) : void {}
        public static function turbulenceFill2D(\GL\Buffer\FloatBuffer $buffer, int $width, int $height, float $scale, float $offsetX = 0, float $offsetY = 0, float $lacunarity = 2.0, float $gain = 0.5, int $octaves = 6) : void {}
        public static function islandFill2D(\GL\Buffer\FloatBuffer $buffer, int $width, int $height, int $islandseed = 42, float $scale = 1.0, float $islandmix = 0.7, float $lacunarity = 2.0, float $gain = 0.5, int $octaves = 6) : void {}
    }
}

namespace GL\Math 
{
    class GLM {
        public static function radians(float $degrees) : float {}
        public static function angle(float $radians) : float {}
        public static function triangleNormal(Vec3 $p1, Vec3 $p2, Vec3 $p3) : Vec3 {}
        public static function normalize(Vec2|Vec3|Vec4 $vec) : Vec2|Vec3|Vec4 {}
    }

 
    class Vec2 {
        public function __construct(?float $x = null, ?float $y = null) {}
        public function copy() : Vec2 {}
        // static
        public static function normalized(Vec2 $vec) : Vec2 {}
        public static function distance(Vec2 $left, Vec2 $right) : float {}
        public static function distance2(Vec2 $left, Vec2 $right) : float {}
        public static function dot(Vec2 $left, Vec2 $right) : float {}
        public static function mix(Vec2 $left, Vec2 $right, float $t) : Vec2 {}
        public static function lerp(Vec2 $left, Vec2 $right, float $t) : Vec2 {}
        public static function slerp(Vec2 $left, Vec2 $right, float $t) : Vec2 {}

        // member function
        public function length() : float {}
        public function distanceTo(Vec2 $right) : float {}
        public function distance2To(Vec2 $right) : float {}
        public function normalize() : void {}
        public function abs() : void {}
        public function __toString() : string {}
    }
 
    class Vec3 {
        public function __construct(?float $x = null, ?float $y = null, ?float $z = null) {}
        public function copy() : Vec3 {}
        // static
        public static function normalized(Vec3 $vec) : Vec3 {}
        public static function distance(Vec3 $left, Vec3 $right) : float {}
        public static function distance2(Vec3 $left, Vec3 $right) : float {}
        public static function dot(Vec3 $left, Vec3 $right) : float {}
        public static function mix(Vec3 $left, Vec3 $right, float $t) : Vec3 {}
        public static function lerp(Vec3 $left, Vec3 $right, float $t) : Vec3 {}
        public static function slerp(Vec3 $left, Vec3 $right, float $t) : Vec3 {}

        // member function
        public function length() : float {}
        public function distanceTo(Vec3 $right) : float {}
        public function distance2To(Vec3 $right) : float {}
        public function normalize() : void {}
        public function abs() : void {}
        public static function cross(Vec3 $left, Vec3 $right) : Vec3 {}
        public static function multiplyQuat(Vec3 $left, Quat $right) : Vec3 {}
        public function __toString() : string {}
    }
 
    class Vec4 {
        public function __construct(?float $x = null, ?float $y = null, ?float $z = null, ?float $w = null) {}
        public function copy() : Vec4 {}
        // static
        public static function normalized(Vec4 $vec) : Vec4 {}
        public static function distance(Vec4 $left, Vec4 $right) : float {}
        public static function distance2(Vec4 $left, Vec4 $right) : float {}
        public static function dot(Vec4 $left, Vec4 $right) : float {}
        public static function mix(Vec4 $left, Vec4 $right, float $t) : Vec4 {}
        public static function lerp(Vec4 $left, Vec4 $right, float $t) : Vec4 {}
        public static function slerp(Vec4 $left, Vec4 $right, float $t) : Vec4 {}

        // member function
        public function length() : float {}
        public function distanceTo(Vec4 $right) : float {}
        public function distance2To(Vec4 $right) : float {}
        public function normalize() : void {}
        public function abs() : void {}
        public function __toString() : string {}
    }
 
    class Quat {
        public function __construct(?float $w = null, ?float $x = null, ?float $y = null, ?float $z = null) {}
        public function copy() : Quat {}
        public static function fromMat4(Mat4 $matrix) : Quat {}
        public static function fromVec4(Vec4 $vec) : Quat {}
        public static function inverted(Quat $quat) : Quat {}
        public static function normalized(Quat $quat) : Quat {} 
        public static function mix(Quat $left, Quat $right, float $t) : Quat {}
        public static function lerp(Quat $left, Quat $right, float $t) : Quat {}
        public static function slerp(Quat $left, Quat $right, float $t) : Quat {}
        public static function dot(Quat $left, Quat $right) : float {}
        public static function multiply(Quat $left, Quat $right) : Quat {}
        public static function multiplyVec3(Quat $quat, Vec3 $vec) : Vec3 {}
        public static function multiplyMat4(Quat $quat, Mat4 $mat) : Mat4 {}
        public function normalize() : void {}
        public function length() : float {}
        public function eulerAngles() : Vec3 {}
        public function rotate(float $angle, Vec3 $axis) : void {}
        public function inverse() : void {}
        public function mat4() : Mat4 {}
        public function __toString() : string {}
    }
 
    class Mat4 {
        public function __construct() {}
        public function copy() : Mat4 {}
        public static function fromArray(array $values) : Mat4 {}
        public static function inverted(Mat4 $matrix) : Mat4 {}
        public static function multiplyQuat(Mat4 $left, Quat $right) : Mat4 {}
        public function row(int $index) : Vec4 {}
        public function setRow(int $index, Vec4 $row) : void {}
        public function col(int $index) : Vec4 {}
        public function setCol(int $index, Vec4 $col) : void {}
        public function lookAt(Vec3 $eye, Vec3 $center, Vec3 $up) : void {}
        public function perspective(float $fov, float $aspect, float $near, float $far) : void {}
        public function ortho(float $left, float $right, float $bottom, float $top, float $near, float $far) : void {}
        public function transpose() : void {}
        public function inverse() : void {}
        public function scale(Vec3 $scale) : void {}
        public function translate(Vec3 $translation) : void {}
        public function rotate(float $angle, Vec3 $axis) : void {}
        public function determinant() : float {}
        public function __toString() : string {}
    }
};

namespace GL\Buffer 
{   
    interface BufferInterface {
        public function __construct(?array $initalData = null) {}
        public function clear() : void {}
        public function size() : int {}
        public function capacity() : int {}
        public function reserve(int $size) : void {}
    }

    class FloatBuffer implements BufferInterface {
        public function __construct(?array $initalData = null) {}
        public function __toString() : string {}
        public function push(float $value) : void {}
        public function pushArray(array $values) : void {}
        public function append(\GL\Buffer\FloatBuffer $buffer) : void {}
        public function pushVec2(GL\Math\Vec2 $vec) : void {}
        public function pushVec3(GL\Math\Vec3 $vec) : void {}
        public function pushVec4(GL\Math\Vec4 $vec) : void {}
        public function pushMat4(GL\Math\Mat4 $matrix) : void {}
        public function quantizeToUChar(bool $autoNormalize = true, float $lowerBound = 0.0, float $upperBound = 1.0) : \GL\Buffer\UByteBuffer {}
        public function fill(int $count, float $value) : void {}
        public function clear() : void {}
        public function size() : int {}
        public function capacity() : int {}
        public function reserve(int $size) : void {}
        public function dump() : string {}
    }

    class HFloatBuffer implements BufferInterface {
        public function __construct(?array $initalData = null) {}
        public function __toString() : string {}
        public function push(int $value) : void {}
        public function pushArray(array $values) : void {}
        public function append(\GL\Buffer\HFloatBuffer $buffer) : void {}
        public function fill(int $count, int $value) : void {}
        public function clear() : void {}
        public function size() : int {}
        public function capacity() : int {}
        public function reserve(int $size) : void {}
        public function dump() : string {}
    }

    class DoubleBuffer implements BufferInterface {
        public function __construct(?array $initalData = null) {}
        public function __toString() : string {}
        public function push(float $value) : void {}
        public function pushArray(array $values) : void {}
        public function append(\GL\Buffer\DoubleBuffer $buffer) : void {}
        public function fill(int $count, float $value) : void {}
        public function clear() : void {}
        public function size() : int {}
        public function capacity() : int {}
        public function reserve(int $size) : void {}
        public function dump() : string {}
    }

    class IntBuffer implements BufferInterface {
        public function __construct(?array $initalData = null) {}
        public function __toString() : string {}
        public function push(int $value) : void {}
        public function pushArray(array $values) : void {}
        public function append(\GL\Buffer\IntBuffer $buffer) : void {}
        public function fill(int $count, int $value) : void {}
        public function clear() : void {}
        public function size() : int {}
        public function capacity() : int {}
        public function reserve(int $size) : void {}
        public function dump() : string {}
    }

    class UIntBuffer implements BufferInterface {
        public function __construct(?array $initalData = null) {}
        public function __toString() : string {}
        public function push(int $value) : void {}
        public function pushArray(array $values) : void {}
        public function append(\GL\Buffer\UIntBuffer $buffer) : void {}
        public function fill(int $count, int $value) : void {}
        public function clear() : void {}
        public function size() : int {}
        public function capacity() : int {}
        public function reserve(int $size) : void {}
        public function dump() : string {}
    }

    class ShortBuffer implements BufferInterface {
        public function __construct(?array $initalData = null) {}
        public function __toString() : string {}
        public function push(int $value) : void {}
        public function pushArray(array $values) : void {}
        public function append(\GL\Buffer\ShortBuffer $buffer) : void {}
        public function fill(int $count, int $value) : void {}
        public function clear() : void {}
        public function size() : int {}
        public function capacity() : int {}
        public function reserve(int $size) : void {}
        public function dump() : string {}
    }

    class UShortBuffer implements BufferInterface {
        public function __construct(?array $initalData = null) {}
        public function __toString() : string {}
        public function push(int $value) : void {}
        public function pushArray(array $values) : void {}
        public function append(\GL\Buffer\UShortBuffer $buffer) : void {}
        public function fill(int $count, int $value) : void {}
        public function clear() : void {}
        public function size() : int {}
        public function capacity() : int {}
        public function reserve(int $size) : void {}
        public function dump() : string {}
    }

    class ByteBuffer implements BufferInterface {
        public function __construct(?array $initalData = null) {}
        public function __toString() : string {}
        public function push(int $value) : void {}
        public function pushArray(array $values) : void {}
        public function append(\GL\Buffer\ByteBuffer $buffer) : void {}
        public function fill(int $count, int $value) : void {}
        public function clear() : void {}
        public function size() : int {}
        public function capacity() : int {}
        public function reserve(int $size) : void {}
        public function dump() : string {}
    }

    class UByteBuffer implements BufferInterface {
        public function __construct(?array $initalData = null) {}
        public function __toString() : string {}
        public function push(int $value) : void {}
        public function pushArray(array $values) : void {}
        public function append(\GL\Buffer\UByteBuffer $buffer) : void {}
        public function pushString(string $str) : void {}
        public function fill(int $count, int $value) : void {}
        public function clear() : void {}
        public function size() : int {}
        public function capacity() : int {}
        public function reserve(int $size) : void {}
        public function dump() : string {}
    }
};

namespace GL\VectorGraphics
{
    class VGColor {
        public static function rgb(float $r, float $g, float $b) : VGColor {}
        public static function rgba(float $r, float $g, float $b, float $a) : VGColor {}
        public static function hsl(float $h, float $s, float $l) : VGColor {}
        public static function hsla(float $h, float $s, float $l, float $a) : VGColor {}
        public static function irgb(int $r, int $g, int $b) : VGColor {}
        public static function irgba(int $r, int $g, int $b, int $a) : VGColor {}
        //public static function fromVec4(\GL\Math\Vec4 $vec) : VGColor {}
        //public static function fromVec3(\GL\Math\Vec3 $vec) : VGColor {}

        public static function hex(string $hex) : VGColor {}

        public static function red() : VGColor {}
        public static function green() : VGColor {}
        public static function blue() : VGColor {}
        public static function white() : VGColor {}
        public static function black() : VGColor {}
        public static function transparent() : VGColor {}
        public static function yellow() : VGColor {}
        public static function cyan() : VGColor {}
        public static function magenta() : VGColor {}
        public static function orange() : VGColor {}
        public static function pink() : VGColor {}
        public static function purple() : VGColor {}
        public static function brown() : VGColor {}
        public static function gray() : VGColor {}
        public static function darkGray() : VGColor {}
        public static function lightGray() : VGColor {}
        public static function random() : VGColor {}
        public static function randomGray() : VGColor {}

        public float $r;
        public float $g;
        public float $b;
        public float $a;

        public function __construct(float $r, float $g, float $b, float $a) {}

        public function getHSLA() : \GL\Math\Vec4 {}
        public function getHSL() : \GL\Math\Vec3 {}
        public function getVec4() : \GL\Math\Vec4 {}
        public function getVec3() : \GL\Math\Vec3 {}

        public function darken(float $amount) : VGColor {}
        public function lighten(float $amount) : VGColor {}
        public function invert() : VGColor {}
        public function withAlpha(float $alpha) : VGColor {}
        public function copy() : VGColor {}
        public function contrast() : VGColor {}
    }
    
    class VGPaint {
        //public static function fromImage(VGImage $image, float $cx, float $cy, float $w, float $h, float $angle, float $alpha = 1.0) : VGPaint {}
    }

    class VGImage {
        // public const REPEAT_NONE = 0;
        // public const REPEAT_X = 1;
        // public const REPEAT_Y = 2;
        // public const REPEAT_XY = 3;

        // public const FILTER_LINEAR = 0;
        // public const FILTER_NEAREST = 1;

        public function makePaint(float $x, float $y, float $w, float $h, float $angle = 0.0, float $alpha = 1.0) : VGPaint {}
        public function makePaintCentered(float $cx, float $cy, float $w, float $h, float $angle = 0.0, float $alpha = 1.0) : VGPaint {}
        //public function makePaintAABB(float $minx, float $miny, float $maxx, float $maxy, float $angle = 0.0, float $alpha = 1.0) : VGPaint {}
    }

    class SVGImage {
        public readonly float $width;
        public readonly float $height;

        public static function fromDisk(string $path) : SVGImage {}
        public static function fromString(string $svg) : SVGImage {}
    }

    class VGAlign {
        // public const LEFT = 1;
        // public const CENTER = 2;
        // public const RIGHT = 4;
        // public const TOP = 8;
        // public const MIDDLE = 16;
        // public const BOTTOM = 32;
        // public const BASELINE = 64;
    }

    class VGContext {
        // public const ANTIALIAS = 1;
        // public const STENCIL_STROKES = 2;
        // public const DEBUG = 4;

        // public const CCW = 1;
        // public const CW = 2;

        // public const SOLID = 1;
        // public const HOLE = 2;

        // public const LINECAP_BUTT = 0;
        // public const LINECAP_ROUND = 1;
        // public const LINECAP_SQUARE = 2;
        // public const LINEJOIN_BEVEL = 3;
        // public const LINEJOIN_MITER = 4;

        public function __construct(int $flags = 0) {}
        public function fillColori(int $r, int $g, int $b, int $a) : void {}
        public function strokeColori(int $r, int $g, int $b, int $a) : void {}
        public function fillColorVec4(\GL\Math\Vec4 $vec) : void {}
        public function strokeColorVec4(\GL\Math\Vec4 $vec) : void {}
        public function transformPoint(float $x, float $y) : \GL\Math\Vec2 {}
        public function transformVec2(\GL\Math\Vec2 $vec) : \GL\Math\Vec2 {}

        public function imageFromTexture(
            \GL\Texture\Texture2D $texture,
            int $repeatMode = VGImage::REPEAT_NONE,
            int $filterMode = VGImage::FILTER_LINEAR
        ) : VGImage {}

        public function imageFromHandle(
            int $handle,
            int $width,
            int $height,
            int $repeatMode = VGImage::REPEAT_NONE,
            int $filterMode = VGImage::FILTER_LINEAR
        ) : VGImage {}

        public function drawSVG(SVGImage $svg, float $x = 0.0, float $y = 0.0, ?float $w = null, ?float $h = null) : void {}

        public function linearGradient(float $sx, float $sy, float $ex, float $ey, VGColor $icol, VGColor $ocol) : VGPaint {}
        public function boxGradient(float $x, float $y, float $w, float $h, float $r, float $f, VGColor $icol, VGColor $ocol) : VGPaint {}
        public function radialGradient(float $cx, float $cy, float $inr, float $outr, VGColor $icol, VGColor $ocol) : VGPaint {}
        //public function imagePattern(float $cx, float $cy, float $w, float $h, float $angle, float $alpha) : VGPaint {}

        public function beginFrame(float $windowWidth, float $windowHeight, float $devicePixelRatio) : void {}
        public function cancelFrame() : void {}
        public function endFrame() : void {}
        public function globalCompositeOperation(int $op) : void {}
        public function globalCompositeBlendFunc(int $sfactor, int $dfactor) : void {}
        public function globalCompositeBlendFuncSeparate(int $srcRGB, int $dstRGB, int $srcAlpha, int $dstAlpha) : void {}
        public function save() : void {}
        public function restore() : void {}
        public function reset() : void {}
        public function shapeAntiAlias(int $enabled) : void {}
        public function strokeColor(\GL\VectorGraphics\VGColor $color) : void {}
        public function strokePaint(\GL\VectorGraphics\VGPaint $paint) : void {}
        public function fillColor(\GL\VectorGraphics\VGColor $color) : void {}
        public function fillPaint(\GL\VectorGraphics\VGPaint $paint) : void {}
        public function miterLimit(float $limit) : void {}
        public function strokeWidth(float $size) : void {}
        public function lineCap(int $cap) : void {}
        public function lineJoin(int $join) : void {}
        public function globalAlpha(float $alpha) : void {}
        public function resetTransform() : void {}
        public function transform(float $a, float $b, float $c, float $d, float $e, float $f) : void {}
        public function translate(float $x, float $y) : void {}
        public function rotate(float $angle) : void {}
        public function skewX(float $angle) : void {}
        public function skewY(float $angle) : void {}
        public function scale(float $x, float $y) : void {}
        public function currentTransform(\GL\Buffer\FloatBuffer $buffer) : void {}
        public function transformPointCurrent(float &$dstx, float &$dsty, float $srcx, float $srcy) : void {}
        public function imageSize(int $image, int &$w, int &$h) : void {}
        public function deleteImage(int $image) : void {}
        public function scissor(float $x, float $y, float $w, float $h) : void {}
        public function intersectScissor(float $x, float $y, float $w, float $h) : void {}
        public function resetScissor() : void {}
        public function beginPath() : void {}
        public function moveTo(float $x, float $y) : void {}
        public function lineTo(float $x, float $y) : void {}
        public function bezierTo(float $c1x, float $c1y, float $c2x, float $c2y, float $x, float $y) : void {}
        public function quadTo(float $cx, float $cy, float $x, float $y) : void {}
        public function arcTo(float $x1, float $y1, float $x2, float $y2, float $radius) : void {}
        public function closePath() : void {}
        public function pathWinding(int $dir) : void {}
        public function arc(float $cx, float $cy, float $r, float $a0, float $a1, int $dir) : void {}
        public function rect(float $x, float $y, float $w, float $h) : void {}
        public function roundedRect(float $x, float $y, float $w, float $h, float $r) : void {}
        public function roundedRectVarying(float $x, float $y, float $w, float $h, float $radTopLeft, float $radTopRight, float $radBottomRight, float $radBottomLeft) : void {}
        public function ellipse(float $cx, float $cy, float $rx, float $ry) : void {}
        public function circle(float $cx, float $cy, float $r) : void {}
        public function fill() : void {}
        public function fillEvenOdd() : void {}
        public function stroke() : void {}
        public function clip() : void {}
        public function resetClip() : void {}
        public function saveClip() : void {}
        public function restoreClip() : void {}
        public function createFont(string $name, string $filename) : int {}
        public function createFontAtIndex(string $name, string $filename, int $fontIndex) : int {}
        public function findFont(string $name) : int {}
        public function addFallbackFontId(int $baseFont, int $fallbackFont) : int {}
        public function addFallbackFont(string $baseFont, string $fallbackFont) : int {}
        public function resetFallbackFontsId(int $baseFont) : void {}
        public function resetFallbackFonts(string $baseFont) : void {}
        public function fontSize(float $size) : void {}
        public function fontBlur(float $blur) : void {}
        public function textLetterSpacing(float $spacing) : void {}
        public function textLineHeight(float $lineHeight) : void {}
        public function textAlign(int $align) : void {}
        public function fontFaceId(int $font) : void {}
        public function fontFace(string $font) : void {}
        public function text(float $x, float $y, string $string) : float {}
        public function textBox(float $x, float $y, float $breakRowWidth, string $string) : void {}
        public function textBounds(float $x, float $y, string $string, ?\GL\Math\Vec4 &$bounds = NULL) : float {}
        public function textBoxBounds(float $x, float $y, float $breakRowWidth, string $string, ?\GL\Math\Vec4 &$bounds = NULL) : void {}
        public function textMetrics(float &$ascender, float &$descender, float &$lineh) : void {}
        public function deleteInternal() : void {}
        public function debugDumpPathCache() : void {}
 
    }
};

namespace GL\Audio
{
    class Engine {
        public function __construct(array $options = []) {}

        public function start() : void {}
        public function stop() : void {}

        public function soundFromDisk(string $path) : \GL\Audio\Sound {}
        
        public function setMasterVolume(float $volume) : void {}
        public function getMasterVolume() : float {}
        public function setListenerPosition(\GL\Math\Vec3 $position) : void {}
        public function setListenerDirection(\GL\Math\Vec3 $direction) : void {}
        public function setListenerWorldUp(\GL\Math\Vec3 $worldUp) : void {}
    }
    
    class Sound {
        public readonly int $sampleRate;
        public readonly int $channels;
        public readonly float $length;
        public readonly int $lengthPCM;

        public function __construct() {}

        // prop getters
        public function getSampleRate() : int {}
        public function getChannels() : int {}
        public function getLength() : float {}
        public function getLengthPCM() : int {}

        // direct ma interface
        public function play() : void {}
        public function stop() : void {}
        public function setStartMs(int $start) : void {}
        public function setStopMs(int $stop) : void {}
        public function isPlaying() : bool {}
        public function getCursor() : float {}
        public function getCursorPCM() : int {}
        public function seekTo(float $cursor) : bool {}
        public function seekToPCM(int $cursor) : bool {}

        public function setVolume(float $volume) : void {}
        public function setPitch(float $pitch) : void {}    
        public function setLoop(bool $loop) : void {}

        public function setPosition(\GL\Math\Vec3 $position) : void {}
        public function setMinDistance(float $distance) : void {}
        public function getMinDistance() : float {}
        public function setMaxDistance(float $distance) : void {}
        public function getMaxDistance() : float {}
        
        public function setDirection(\GL\Math\Vec3 $direction) : void {}
        public function getDirection() : \GL\Math\Vec3 {}
        public function setVelocity(\GL\Math\Vec3 $velocity) : void {}
        public function getVelocity() : \GL\Math\Vec3 {}
        
        public function fadeIn(float $duration) : void {}
        public function fadeOut(float $duration) : void {}
        public function setFade(float $fromVolume, float $toVolume, float $duration) : void {}
        public function getCurrentFadeVolume() : float {}
        
        public function readFrames(int $frames, \GL\Buffer\BufferInterface $buffer) : int {}
    }
};

