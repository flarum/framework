<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Work;

use Flarum\Database\Migrator;
use Flarum\Extension\ExtensionManager;
use Flarum\ExtensionManager\Host\StorageDir;
use Flarum\ExtensionManager\Run\Run;
use Flarum\ExtensionManager\Run\RunLock;
use Flarum\Foundation\Paths;
use Flarum\Locale\TranslatorInterface;
use Illuminate\Database\ConnectionInterface;
use Throwable;

/**
 * The second half of an undo: reversing the database, in a request booted
 * from the restored code. See MigrationLedger for why it cannot happen in the
 * undo request itself. Called by whatever the admin page asks next — its
 * state, or the next step — so it finishes even if the page was closed.
 */
class PendingUndo
{
    /** @return string|null why it could not finish, or null when there was nothing to do or it is done */
    public static function finish(TranslatorInterface $translator, ?Run $run, Paths $paths): ?string
    {
        if ($run === null || $run->state !== Run::ROLLBACK) {
            return null;
        }

        $ledger = new MigrationLedger($translator, (new WorkDir($translator, $paths->storage, $run->id))->root());

        if (! $ledger->pending()) {
            return null;
        }

        /*
         * Reached from the page's polling, so two tabs (or the dashboard banner
         * beside the page) can arrive together. Under the run's lock only one
         * reverses anything; the other finds it busy and the next poll sees the
         * work done.
         */
        $lock = new RunLock($translator, StorageDir::of($paths->storage).'/locks');

        try {
            $held = $lock->acquire($run->id);

            if ($held === null) {
                return null;
            }

            try {
                $ledger->finish(
                    resolve(ConnectionInterface::class),
                    resolve(Migrator::class),
                    resolve(ExtensionManager::class),
                    $paths->vendor
                );
            } finally {
                $lock->release($held);
            }

            return null;
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    }
}
