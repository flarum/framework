<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Host;

/**
 * Where the Extension Manager keeps its own files, under Flarum's storage
 * directory: runs, rollback copies, the update check, caches and locks.
 */
class StorageDir
{
    public const NAME = 'extension-manager';

    /**
     * Before it shipped with Flarum this extension was Millwright, and a site
     * that ran it keeps its history, rollback copies and any run in progress
     * here. That directory is used where it is, never moved: the update that
     * swaps Millwright for this package is itself a run, and its journal names
     * the files it set aside by their full path.
     */
    public const LEGACY = 'millwright';

    public static function of(string $storage): string
    {
        $legacy = $storage.'/'.self::LEGACY;

        return is_dir($legacy) && ! is_dir($storage.'/'.self::NAME) ? $legacy : $storage.'/'.self::NAME;
    }
}
