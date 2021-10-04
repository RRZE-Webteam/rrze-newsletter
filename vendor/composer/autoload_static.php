<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit185c936252b12398c113014d70cefb07
{
    public static $prefixLengthsPsr4 = array (
        'R' => 
        array (
            'RRule\\' => 6,
            'RRZE\\Newsletter\\' => 16,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'RRule\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
            1 => __DIR__ . '/..' . '/rlanvin/php-rrule/src',
        ),
        'RRZE\\Newsletter\\' => 
        array (
            0 => __DIR__ . '/../..' . '/includes',
        ),
    );

    public static $prefixesPsr0 = array (
        'I' => 
        array (
            'ICal' => 
            array (
                0 => __DIR__ . '/../..' . '/src',
                1 => __DIR__ . '/..' . '/johngrogg/ics-parser/src',
            ),
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit185c936252b12398c113014d70cefb07::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit185c936252b12398c113014d70cefb07::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit185c936252b12398c113014d70cefb07::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit185c936252b12398c113014d70cefb07::$classMap;

        }, null, ClassLoader::class);
    }
}
