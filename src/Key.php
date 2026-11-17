<?php

declare(strict_types=1);

namespace PhpUi;

/** Keys, by their place on a US keyboard, as key handlers get them: `$key === Key::ENTER`. */
final class Key
{
    public const UNKNOWN = -1;
    public const SPACE = 32;
    public const APOSTROPHE = 39;
    public const COMMA = 44;
    public const MINUS = 45;
    public const PERIOD = 46;
    public const SLASH = 47;
    public const DIGIT_0 = 48;
    public const DIGIT_1 = 49;
    public const DIGIT_2 = 50;
    public const DIGIT_3 = 51;
    public const DIGIT_4 = 52;
    public const DIGIT_5 = 53;
    public const DIGIT_6 = 54;
    public const DIGIT_7 = 55;
    public const DIGIT_8 = 56;
    public const DIGIT_9 = 57;
    public const SEMICOLON = 59;
    public const EQUAL = 61;
    public const A = 65;
    public const B = 66;
    public const C = 67;
    public const D = 68;
    public const E = 69;
    public const F = 70;
    public const G = 71;
    public const H = 72;
    public const I = 73;
    public const J = 74;
    public const K = 75;
    public const L = 76;
    public const M = 77;
    public const N = 78;
    public const O = 79;
    public const P = 80;
    public const Q = 81;
    public const R = 82;
    public const S = 83;
    public const T = 84;
    public const U = 85;
    public const V = 86;
    public const W = 87;
    public const X = 88;
    public const Y = 89;
    public const Z = 90;
    public const LEFT_BRACKET = 91;
    public const BACKSLASH = 92;
    public const RIGHT_BRACKET = 93;
    public const GRAVE_ACCENT = 96;
    public const WORLD_1 = 161;
    public const WORLD_2 = 162;
    public const ESCAPE = 256;
    public const ENTER = 257;
    public const TAB = 258;
    public const BACKSPACE = 259;
    public const INSERT = 260;
    public const DELETE = 261;
    public const RIGHT = 262;
    public const LEFT = 263;
    public const DOWN = 264;
    public const UP = 265;
    public const PAGE_UP = 266;
    public const PAGE_DOWN = 267;
    public const HOME = 268;
    public const END = 269;
    public const CAPS_LOCK = 280;
    public const SCROLL_LOCK = 281;
    public const NUM_LOCK = 282;
    public const PRINT_SCREEN = 283;
    public const PAUSE = 284;
    public const F1 = 290;
    public const F2 = 291;
    public const F3 = 292;
    public const F4 = 293;
    public const F5 = 294;
    public const F6 = 295;
    public const F7 = 296;
    public const F8 = 297;
    public const F9 = 298;
    public const F10 = 299;
    public const F11 = 300;
    public const F12 = 301;
    public const F13 = 302;
    public const F14 = 303;
    public const F15 = 304;
    public const F16 = 305;
    public const F17 = 306;
    public const F18 = 307;
    public const F19 = 308;
    public const F20 = 309;
    public const F21 = 310;
    public const F22 = 311;
    public const F23 = 312;
    public const F24 = 313;
    public const F25 = 314;
    public const KP_0 = 320;
    public const KP_1 = 321;
    public const KP_2 = 322;
    public const KP_3 = 323;
    public const KP_4 = 324;
    public const KP_5 = 325;
    public const KP_6 = 326;
    public const KP_7 = 327;
    public const KP_8 = 328;
    public const KP_9 = 329;
    public const KP_DECIMAL = 330;
    public const KP_DIVIDE = 331;
    public const KP_MULTIPLY = 332;
    public const KP_SUBTRACT = 333;
    public const KP_ADD = 334;
    public const KP_ENTER = 335;
    public const KP_EQUAL = 336;
    public const LEFT_SHIFT = 340;
    public const LEFT_CONTROL = 341;
    public const LEFT_ALT = 342;
    public const LEFT_SUPER = 343;
    public const RIGHT_SHIFT = 344;
    public const RIGHT_CONTROL = 345;
    public const RIGHT_ALT = 346;
    public const RIGHT_SUPER = 347;
    public const MENU = 348;
}

/** Modifier keys held, as bits: `$mods & Mod::CONTROL`. */
final class Mod
{
    public const SHIFT = 1;
    public const CONTROL = 2;
    public const ALT = 4;
    public const SUPER = 8;
    public const CAPS_LOCK = 16;
    public const NUM_LOCK = 32;
}

/** Mouse buttons. */
final class Mouse
{
    public const BUTTON_1 = 0;
    public const BUTTON_2 = 1;
    public const BUTTON_3 = 2;
    public const BUTTON_4 = 3;
    public const BUTTON_5 = 4;
    public const BUTTON_6 = 5;
    public const BUTTON_7 = 6;
    public const BUTTON_8 = 7;
    public const LEFT = 0;
    public const RIGHT = 1;
    public const MIDDLE = 2;
}
