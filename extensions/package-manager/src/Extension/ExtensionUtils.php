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

    public static function isMajorUpdate(string $currentVersion, string $latestVersion): bool
    {
        // Drop any v prefixes
        if(str_starts_with($currentVersion, 'v')) {
            $currentVersion = substr($currentVersion, 1);
        }

        $currentVersion = explode('.', $currentVersion);
        $latestVersion = explode('.', $latestVersion);

        if (! is_numeric($currentVersion[0]) || ! is_numeric($latestVersion[0])) {
            return false;
        }

        if (intval($currentVersion[0]) < intval($latestVersion[0])) {
            return true;
        }

        return false;
    }
}
