<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Support;

use Illuminate\Support\Str;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;

class Util
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
        if (str_starts_with($currentVersion, 'v')) {
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

    public static function readableConsoleInput(InputInterface $input): string
    {
        if ($input instanceof ArrayInput) {
            $input = explode(' ', $input->__toString());

            foreach ($input as $key => $value) {
                if (str_starts_with($value, '--')) {
                    if (! str_contains($value, '=')) {
                        unset($input[$key]);
                    } else {
                        $input[$key] = Str::before($value, '=');
                    }
                }

                if (is_numeric($value) && isset($input[$key - 1]) && str_starts_with($input[$key - 1], '-') && ! str_starts_with($input[$key - 1], '--')) {
                    unset($input[$key]);
                }
            }

            return implode(' ', $input);
        } elseif (method_exists($input, '__toString')) {
            return $input->__toString();
        }

        return '';
    }
}
