<?php

declare(strict_types=1);

namespace Package\Library;

use StaticPHP\Attribute\Package\BuildFor;
use StaticPHP\Attribute\Package\Library;
use StaticPHP\Package\LibraryPackage;
use StaticPHP\Runtime\Executor\UnixCMakeExecutor;
use StaticPHP\Runtime\Executor\WindowsCMakeExecutor;

/**
 * SDL3 for myphp's phpsdl extension: video and input with input methods, and SDL_GPU
 * (Vulkan, Metal, Direct3D 12) to draw; no OpenGL, no audio (miniaudio plays sound), camera, haptics,
 * sensors, joysticks or SDL's 2D renderers. On Linux SDL loads X11, Wayland, D-Bus and
 * Vulkan at run time, so only their headers are needed to build.
 */
#[Library('sdl3')]
class sdl3
{
    private const array ARGS = [
        '-DSDL_SHARED=OFF',
        '-DSDL_STATIC=ON',
        '-DSDL_TEST_LIBRARY=OFF',
        '-DSDL_TESTS=OFF',
        '-DSDL_EXAMPLES=OFF',
        '-DSDL_AUDIO=OFF',
        '-DSDL_CAMERA=OFF',
        '-DSDL_HAPTIC=OFF',
        '-DSDL_SENSOR=OFF',
        // Not used by myphp yet; each adds size to every app.
        '-DSDL_JOYSTICK=OFF',
        '-DSDL_HIDAPI=OFF',
        '-DSDL_RENDER=OFF',
        '-DSDL_POWER=OFF',
        '-DSDL_GPU=ON',
        '-DSDL_OPENGL=OFF',
        '-DSDL_OPENGLES=OFF',
        '-DSDL_KMSDRM=OFF',
        '-DSDL_OFFSCREEN=OFF',
        '-DSDL_DIALOG=ON',
        '-DCMAKE_POSITION_INDEPENDENT_CODE=ON',
    ];

    /**
     * Linux: SDL finds D-Bus and IBus (input methods) with the system's pkg-config.
     * static-php-cli's CMake toolchain pins pkg-config to its own build root, where
     * they are not, and SDL would quietly build without input-method support. Only
     * headers are needed: SDL loads X11, Wayland and D-Bus at run time.
     * MYPHP_SDL_PKG_CONFIG_PATH adds folders, e.g. headers unpacked without root.
     */
    #[BuildFor('Linux')]
    public function buildLinux(LibraryPackage $lib): void
    {
        $system = '/usr/lib/' . php_uname('m') . '-linux-gnu/pkgconfig:/usr/lib64/pkgconfig:/usr/lib/pkgconfig:/usr/share/pkgconfig';
        $paths = trim((getenv('MYPHP_SDL_PKG_CONFIG_PATH') ?: '') . ':' . $system, ':');
        $root = $lib->getBuildRootPath();
        $args = implode(' ', [...self::ARGS, '-DSDL_X11=ON', '-DSDL_X11_XSCRNSAVER=OFF', '-DSDL_X11_XTEST=OFF', '-DSDL_DBUS=ON', '-DSDL_IBUS=ON']);
        shell()->cd($lib->getSourceDir())->initializeEnv($lib)
            ->setEnv(['PKG_CONFIG_PATH' => $paths, 'PKG_CONFIG' => '/usr/bin/pkg-config'])
            ->exec('rm -rf build-myphp')
            ->exec("cmake -S . -B build-myphp -DCMAKE_BUILD_TYPE=Release -DCMAKE_INSTALL_PREFIX={$root} -DCMAKE_INSTALL_LIBDIR=lib "
                . "-DPKG_CONFIG_EXECUTABLE=/usr/bin/pkg-config {$args}")
            ->exec('grep -q "define SDL_USE_IME 1" build-myphp/include-config-release/build_config/SDL_build_config.h '
                . '|| { echo "SDL3 was configured without input-method support: install the D-Bus and IBus development headers" >&2; exit 1; }')
            ->exec('cmake --build build-myphp -j' . (int) (getenv('SPC_CONCURRENCY') ?: 4))
            ->exec('cmake --install build-myphp');
        $lib->patchPkgconfPrefix(['sdl3.pc'], PKGCONF_PATCH_PREFIX);
    }

    #[BuildFor('Darwin')]
    public function buildMac(LibraryPackage $lib): void
    {
        UnixCMakeExecutor::create($lib)->addConfigureArgs(...self::ARGS)->build();
        $lib->patchPkgconfPrefix(['sdl3.pc'], PKGCONF_PATCH_PREFIX);
    }

    #[BuildFor('Windows')]
    public function buildWindows(LibraryPackage $lib): void
    {
        WindowsCMakeExecutor::create($lib)->addConfigureArgs(...self::ARGS)->build();
    }
}
