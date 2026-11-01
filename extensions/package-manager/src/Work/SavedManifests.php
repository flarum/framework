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
     * Copy each saved manifest back over the live one, where they differ.
     *
     * Copied beside and renamed into place, so a reader never sees half a
     * composer.json, and the live file keeps its own mode. The saved copy is
     * left where it is: the rollback reads it too.
     *
     * @return list<string> the files that were put back
     */
    public static function putBack(string $workDir, string $installPath): array
    {
        $done = [];

        foreach (['composer.lock', 'composer.json'] as $file) {
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
     * Put back what a FAILED run left behind, before another run starts.
     *
     * A failure the run could catch has already done this itself (see
     * ComposerSteps::doItem). This is for the ones it could not: a queue
     * worker killed mid-item, a request cut off by the host, or a run that
     * failed on a version of this code that did not put anything back.
     *
     * Only when the live lock has not been touched since the run stopped. A
     * lock written after that was written by somebody else, most likely a
     * `composer update` by hand, and is theirs to keep.
     *
     * @return list<string> the files that were put back
     */
    public static function afterFailedRun(?Run $run, string $workDir, string $installPath): array
    {
        if ($run === null || $run->state !== Run::FAILED || ! in_array($run->phase, self::BEFORE_LOCK_STAYS, true)) {
            return [];
        }

        clearstatcache();
        $changed = @filemtime($installPath.'/composer.lock');

        if ($changed !== false && $changed > $run->movedAt) {
            return [];
        }

        return self::putBack($workDir, $installPath);
    }
}
