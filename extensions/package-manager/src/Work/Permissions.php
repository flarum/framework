<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Work;

use Flarum\ExtensionManager\Lang;
use Flarum\ExtensionManager\Plan\Change;
use FilesystemIterator;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

/**
 * Everything the update will have to change, checked before it changes any of it.
 *
 * 🚨 Both of these were found mid-run on real forums, 2026-10-02, and each was
 * worse for being found late:
 *
 * - A package directory owned by root. Moving a directory to another parent
 *   needs write permission on the DIRECTORY ITSELF (its `..` entry changes),
 *   not just on vendor/ — so the web user could not move ramon/chat aside, and
 *   the run stopped with four packages already swapped and Composer's record
 *   still naming the old ones.
 * - A root-owned file under storage/cache. Every package was in place, then
 *   the formatter could not be rebuilt and every discussion page 500'd until
 *   it was fixed by hand.
 *
 * Root-owned files appear whenever somebody runs `php flarum ...` or Composer
 * as root, which is routine on a VPS. They are cheap to find and impossible to
 * work around from inside PHP, so the answer is to refuse up front and say
 * exactly what to chown.
 */
class Permissions
{
    /** Enough to show the pattern without burying the instruction. */
    private const SHOWN = 10;

    public function __construct(
        private string $basePath,
        private string $vendorPath,
        private string $storagePath,
    ) {
    }

    /**
     * @param Change[] $changes
     * @return string[] paths this process cannot change, empty when it can proceed
     */
    public function blocked(array $changes): array
    {
        $blocked = [];

        foreach (['composer.json', 'composer.lock'] as $file) {
            $this->need($this->basePath.'/'.$file, $blocked);
        }
        $this->need($this->vendorPath.'/composer', $blocked);

        foreach ($changes as $change) {
            $dir = $this->vendorPath.'/'.$change->relativePath();

            if ($change->op === Change::ADD && ! file_exists($dir)) {
                // Created inside its vendor directory, or vendor/ when that
                // does not exist yet either.
                $parent = dirname($dir);
                $this->need(is_dir($parent) ? $parent : $this->vendorPath, $blocked);
                continue;
            }

            // The directory itself and its parent: both entries change on a move.
            $this->need($dir, $blocked);
            $this->need(dirname($dir), $blocked);
        }

        $this->walkInto($this->storagePath.'/cache', $blocked);
        $this->walkInto($this->storagePath.'/formatter', $blocked);

        return array_values(array_unique($blocked));
    }

    /** The refusal, worded so the fix can be copied out of it. */
    public function explain(array $blocked): string
    {
        $user = function_exists('posix_geteuid') && function_exists('posix_getpwuid')
            ? (posix_getpwuid(posix_geteuid())['name'] ?? Lang::trans('permissions.web_user'))
            : Lang::trans('permissions.web_user');

        $shown = array_slice($blocked, 0, self::SHOWN);
        $more = count($blocked) - count($shown);

        return Lang::trans('permissions.blocked', ['count' => count($blocked), 'user' => $user])
            ."\n  chown -R $user ".escapeshellarg($this->vendorPath).' '
            .escapeshellarg($this->storagePath)."\n"
            .implode("\n", array_map(fn ($p) => '  '.$p, $shown))
            .($more > 0 ? "\n  ".Lang::trans('permissions.and_more', ['count' => $more]) : '');
    }

    private function need(string $path, array &$blocked): void
    {
        if (file_exists($path) && ! is_writable($path)) {
            $blocked[] = $path;
        }
    }

    /** Every entry under $root this process cannot change, appended to $blocked. */
    public function walkInto(string $root, array &$blocked): void
    {
        if (! is_dir($root)) {
            return;
        }

        $this->need($root, $blocked);

        try {
            // What StaleCache set aside is never read again, so it blocks nothing.
            $items = new RecursiveIteratorIterator(
                new RecursiveCallbackFilterIterator(
                    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
                    fn ($item) => ! StaleCache::isSetAside($item->getFilename())
                ),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($items as $item) {
                if (! $item->isWritable()) {
                    $blocked[] = $item->getPathname();
                }
            }
        } catch (Throwable) {
            // An unreadable directory is itself the problem.
            $blocked[] = $root;
        }
    }
}
