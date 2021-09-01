<?php

namespace SychO\PackageManager\Extension;

class ExtensionUtils
{
    public static function nameToId(string $name): string
    {
        [$vendor, $package] = explode('/', $name);
        $package = str_replace(['flarum-ext-', 'flarum-'], '', $package);

        return "$vendor-$package";
    }
}
