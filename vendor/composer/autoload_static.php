<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitd5f7db44e672ba298d5fe6b060ad7e53
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'Psr\\Log\\' => 8,
        ),
        'L' => 
        array (
            'LoggerWp\\' => 9,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Psr\\Log\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/log/Psr/Log',
        ),
        'LoggerWp\\' => 
        array (
            0 => __DIR__ . '/..' . '/veronalabs/logger-wp/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitd5f7db44e672ba298d5fe6b060ad7e53::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitd5f7db44e672ba298d5fe6b060ad7e53::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitd5f7db44e672ba298d5fe6b060ad7e53::$classMap;

        }, null, ClassLoader::class);
    }
}
