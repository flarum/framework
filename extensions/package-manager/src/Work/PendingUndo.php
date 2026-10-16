<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Work;

use Flarum\ExtensionManager\Run\Run;
use Flarum\Database\Migrator;
use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\Paths;
use Illuminate\Database\ConnectionInterface;
use Throwable;

/**
 * The second half of an undo: reversing the database, in a request booted
 * from the restored code. See MigrationLedger for why it cannot happen in the
 * undo request itself. Called by whatever the admin page asks next — its
 * state, or the next step — so it finishes even if the page was closed.
 */
final class PendingUndo
{
    /** @return string|null why it could not finish, or null when there was nothing to do or it is done */
    public static function finish(?Run $run, Paths $paths): ?string
    {
        if ($run === null || $run->state !== Run::ROLLBACK) {
            return null;
        }

        $ledger = new MigrationLedger((new WorkDir($paths->storage, $run->id))->root());

        if (! $ledger->pending()) {
            return null;
        }

        try {
            $ledger->finish(
                resolve(ConnectionInterface::class),
                resolve(Migrator::class),
                resolve(ExtensionManager::class),
                $paths->vendor
            );

            return null;
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    }
}
