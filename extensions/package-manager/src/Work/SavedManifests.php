<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Work;

use Flarum\ExtensionManager\Run\Run;

/**
 * composer.json and composer.lock as they were before a run, put back.
 *
 * The plan phase lets Composer rewrite the lock (and composer.json, for an
 * install, a removal or a raised pin) long before a single file in vendor/
 * moves. Until the apply phase has finished, the new manifests describe a site
 * that does not exist yet. A run that stops before then and leaves them in
 * place hands the next run a lock that already says "new": that run saves it
 * as its own starting point, resolves "nothing changed" and reports the site
 * up to date while vendor/ still holds the old code. The update check reads
 * the same lock and agrees with it.
 */
class SavedManifests
{
    /**
     * The phases in which the new manifests are not yet true. From the
     * finalise phase on, every package is in place and Composer's record is
     * built from the new lock, so the lock is meant to stay; finishing up is
     * retried or rolled back, never re-planned.
     */
    public const BEFORE_LOCK_STAYS = ['plan', 'fetch', 'apply'];

    /**
     * Written in the run's work directory once its manifests have been put
     * back: the hash of each live file as the restore left it.
     */
    public const RESTORED = 'manifests.restored';

    protected const FILES = ['composer.lock', 'composer.json'];

    /**
     * Copy each saved manifest back over the live one, where they differ.
     *
     * Copied beside and renamed into place, so a reader never sees half a
     * composer.json, and the live file keeps its own mode. The saved copy is
     * left where it is: the rollback reads it too.
     *
     * @param list<string> $only the manifests to consider
     * @return list<string> the files that were put back
     */
    public static function putBack(string $workDir, string $installPath, array $only = self::FILES): array
    {
        $done = [];

        foreach (array_intersect(self::FILES, $only) as $file) {
            $saved = $workDir.'/'.$file.'.before';
            $live = $installPath.'/'.$file;

            if (! is_file($saved) || @file_get_contents($saved) === @file_get_contents($live)) {
                continue;
            }

            $mode = @fileperms(is_file($live) ? $live : $saved);
            $tmp = $live.'.extension-manager-'.bin2hex(random_bytes(3));
            $handle = @fopen($tmp, 'x');

            if ($handle === false) {
                continue;
            }

            fclose($handle);

            // Owner only while it is written: composer.json can carry a token.
            if (! @chmod($tmp, 0600) || ! @copy($saved, $tmp) || ! @chmod($tmp, $mode === false ? 0644 : $mode & 0777) || ! @rename($tmp, $live)) {
                @unlink($tmp);

                continue;
            }

            $done[] = $file;
        }

        return $done;
    }

    /**
     * Put back what a run that is failing left behind, and remember having
     * done it, so the next start does not do it again over somebody's later
     * edit. For a failure the run catches itself (ComposerSteps::doItem).
     *
     * @return list<string> the files that were put back
     */
    public static function restore(string $workDir, string $installPath): array
    {
        $done = self::putBack($workDir, $installPath);
        self::remember($workDir, $installPath);

        return $done;
    }

    /**
     * Put back what a FAILED run left behind, before another run starts.
     *
     * A failure the run could catch has already done this itself and said so
     * (see restore()). Then a file is only put back if it is still exactly as
     * the restore left it, which only matters when the restore could not
     * write it: anything changed since, by the configuration screen or by
     * hand, is somebody's edit and is kept.
     *
     * Otherwise the run could not catch it: a queue worker killed mid-item, a
     * request cut off by the host, or a run that failed on a version of this
     * code that did not put anything back. Then each file is only put back if
     * it has not been written since the run stopped. A lock written after
     * that was most likely a `composer update` by hand, which is theirs to
     * keep along with the composer.json it read; a composer.json written
     * after that alone (a repository added on the configuration screen) is
     * kept while the lock still goes back.
     *
     * Either way it is done once: what this leaves is remembered, and a later
     * start only touches a file still exactly as this left it.
     *
     * @return list<string> the files that were put back
     */
    public static function afterFailedRun(?Run $run, string $workDir, string $installPath): array
    {
        if ($run === null || $run->state !== Run::FAILED || ! in_array($run->phase, self::BEFORE_LOCK_STAYS, true)) {
            return [];
        }

        clearstatcache();
        $left = self::remembered($workDir);
        $files = [];

        foreach (self::FILES as $file) {
            $live = $installPath.'/'.$file;

            if ($left !== null) {
                if (array_key_exists($file, $left) && self::hash($live) === $left[$file]) {
                    $files[] = $file;
                }

                continue;
            }

            $changed = @filemtime($live);

            if ($changed !== false && $changed > $run->movedAt) {
                if ($file === 'composer.lock') {
                    return [];
                }

                continue;
            }

            $files[] = $file;
        }

        $done = self::putBack($workDir, $installPath, $files);
        self::remember($workDir, $installPath);

        return $done;
    }

    /** What the live manifests are now, written down in the run's work directory. */
    protected static function remember(string $workDir, string $installPath): void
    {
        $hashes = [];

        foreach (self::FILES as $file) {
            $hashes[$file] = self::hash($installPath.'/'.$file);
        }

        @file_put_contents($workDir.'/'.self::RESTORED, json_encode($hashes));
    }

    /** @return array<string,string|null>|null null when no restore was ever done */
    protected static function remembered(string $workDir): ?array
    {
        $raw = @file_get_contents($workDir.'/'.self::RESTORED);
        $hashes = $raw === false ? null : json_decode($raw, true);

        return is_array($hashes) ? $hashes : null;
    }

    protected static function hash(string $path): ?string
    {
        return is_file($path) ? (@sha1_file($path) ?: null) : null;
    }
}
