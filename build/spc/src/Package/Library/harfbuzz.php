<?php

declare(strict_types=1);

namespace Package\Library;

use StaticPHP\Attribute\Package\BuildFor;
use StaticPHP\Attribute\Package\Library;
use StaticPHP\Package\LibraryPackage;
use StaticPHP\Runtime\Executor\UnixCMakeExecutor;
use StaticPHP\Runtime\Executor\WindowsCMakeExecutor;

/**
 * HarfBuzz for phpsdl's text: shaping, and glyph rasterizing (harfbuzz-raster), with
 * no FreeType, GLib, ICU or platform text APIs.
 */
#[Library('harfbuzz')]
class harfbuzz
{
    private const array ARGS = [
        '-DBUILD_SHARED_LIBS=OFF',
        '-DCMAKE_POSITION_INDEPENDENT_CODE=ON',
        '-DHB_BUILD_SUBSET=OFF',
        '-DHB_BUILD_VECTOR=OFF',
        '-DHB_BUILD_GPU=OFF',
        '-DHB_BUILD_RASTER=ON',
        '-DHB_BUILD_UTILS=OFF',
        '-DHB_HAVE_FREETYPE=OFF',
        '-DHB_HAVE_GLIB=OFF',
        '-DHB_HAVE_ICU=OFF',
        '-DHB_HAVE_CORETEXT=OFF',
        '-DHB_HAVE_DIRECTWRITE=OFF',
        '-DHB_HAVE_GDI=OFF',
        '-DHB_HAVE_UNISCRIBE=OFF',
    ];

    #[BuildFor('Linux')]
    #[BuildFor('Darwin')]
    public function build(LibraryPackage $lib): void
    {
        UnixCMakeExecutor::create($lib)->addConfigureArgs(...self::ARGS)->build();
        $lib->patchPkgconfPrefix(['harfbuzz.pc', 'harfbuzz-raster.pc'], PKGCONF_PATCH_PREFIX);
    }

    #[BuildFor('Windows')]
    public function buildWindows(LibraryPackage $lib): void
    {
        WindowsCMakeExecutor::create($lib)->addConfigureArgs(...self::ARGS)->build();
    }
}
