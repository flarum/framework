<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Extension;

class ExtensionUtils
{
    public static function nameToId(string $name): string
    {
        [$vendor, $package] = explode('/', $name);
        $package = str_replace(['flarum-ext-', 'flarum-'], '', $package);

        return "$vendor-$package";
    }
}
