<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Work;

use Flarum\ExtensionManager\Lang;
/**
 * A cache somebody else owns, moved out of the way instead of refused.
 *
 * 🚨 Found on a Flarum-in-a-box container, 2026-10-07: its entrypoint runs
 * `php flarum` as root, so storage/cache and storage/formatter fill with
 * root-owned entries. Permissions refused every update over them, and "Put
 * everything back" failed at `cache:clear` for the same reason, on a host
 * where the operator may not have a shell to chown from.
 *
 * Neither directory holds anything worth keeping. When storage/ itself is
 * writable, renaming one aside is one call (a rename within the same parent
 * needs write permission on the parent only) and Flarum rebuilds what it
 * needs into a fresh one. Both halves of the formatter, the cached renderer
 * and the Renderer_<hash>.php it names, live in storage/formatter, so they go
 * together and never disagree. The old directory is left for root to delete:
 * this process cannot.
 *
 * When storage/ is not writable, or the directory is a mount point, the
 * offending entries are renamed hidden inside it instead (see setAside).
 * Only when neither is possible does Permissions refuse, as before.
 */
final class StaleCache
{
    private const DIRS = ['cache', 'formatter'];

    private const HIDDEN = '.root-owned-';

    /** @return string[] the directories moved aside */
    public static function setAside(string $storagePath): array
    {
        if ($storagePath === '') {
            return [];
        }

        $moved = [];
        $stamp = date('YmdHis');

        foreach (self::DIRS as $name) {
            $dir = $storagePath.'/'.$name;

            if (! is_dir($dir) || ! self::hasUnwritable($dir)) {
                continue;
            }

            // The whole directory, when storage/ lets us.
            if (is_writable($storagePath) && @rename($dir, $dir.'.root-owned-'.$stamp)) {
                @mkdir($dir, 0775);
                $moved[] = $dir.'.root-owned-'.$stamp;
                continue;
            }

            /*
             * 🚨 Otherwise each offending entry, renamed hidden inside it.
             *
             * ClaudiusH's container, 2026-10-07, on 1.13.0: storage/ itself was
             * not writable (or the cache is a mounted volume), so the rename
             * above failed and the refusal came back unchanged. The cache
             * directory WAS writable, and renaming within one directory needs
             * nothing more. A hidden name is never read again — entries live
             * at hashed paths — and both cache:clear's flush (Symfony Finder)
             * and its formatter glob skip dot-entries, so it no longer fails.
             */
            if (! is_writable($dir)) {
                continue;
            }

            foreach (scandir($dir) ?: [] as $entry) {
                $path = $dir.'/'.$entry;

                if ($entry[0] === '.' || ! self::blocks($path)) {
                    continue;
                }

                $aside = $dir.'/'.self::HIDDEN.$entry.'-'.$stamp;

                if (@rename($path, $aside)) {
                    $moved[] = $aside;
                }
            }
        }

        return $moved;
    }

    /** A hidden entry this class put aside: Permissions does not count it. */
    public static function isSetAside(string $name): bool
    {
        return str_starts_with($name, self::HIDDEN);
    }

    /** This entry, or anything under it, cannot be changed by this process. */
    private static function blocks(string $path): bool
    {
        if (! is_writable($path)) {
            return true;
        }

        return is_dir($path) && ! is_link($path) && self::hasUnwritable($path);
    }

    /** One line for a step result, or '' when nothing moved. */
    public static function describe(array $moved): string
    {
        return $moved === [] ? '' : Lang::trans('stale_cache.set_aside').' rm -rf '
            .implode(' ', array_map('escapeshellarg', $moved));
    }

    private static function hasUnwritable(string $root): bool
    {
        $found = [];
        (new Permissions('', '', ''))->walkInto($root, $found);

        return $found !== [];
    }
}
