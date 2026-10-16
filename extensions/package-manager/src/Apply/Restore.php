<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Apply;

use Flarum\ExtensionManager\Lang;
use Flarum\ExtensionManager\Host\Opcache;
use Flarum\ExtensionManager\Work\ComposerRunner;
use Flarum\ExtensionManager\Work\FlarumCommand;
use Flarum\ExtensionManager\Work\InstalledRecord;
use Flarum\ExtensionManager\Work\MigrationLedger;
use Flarum\ExtensionManager\Work\StaleCache;
use Flarum\Database\Migrator;
use Flarum\Extension\ExtensionManager;
use Illuminate\Database\ConnectionInterface;
use Throwable;

/**
 * Put everything back the way it was, once, in one place.
 *
 * 🚨 Extracted because there are now TWO callers — the admin pressing the
 * button, and the run undoing itself when the site stops answering — and two
 * copies of "put it back" is the last thing this extension should have. The
 * subtle half is not moving the files: it is reconciling Composer's record
 * afterwards, and a second copy that forgot to would leave a site that looks
 * fine and carries a phantom package which is fatal to enable, months later,
 * with nothing pointing back here.
 */
class Restore
{
    public function __construct(
        private string $vendorPath,
        private string $basePath,
        private string $workDirRoot,
        private string $trashPath,
        private Journal $journal,
        private ComposerRunner $composer,
        private string $storagePath = '',
    ) {
    }

    /** Is there anything saved to put back? */
    public function possible(): bool
    {
        return $this->journal->exists() || is_file($this->workDirRoot.'/composer.lock.before');
    }

    /**
     * @return array{undone: list<string>, note: ?string}
     */
    public function run(): array
    {
        /*
         * The database is checked first — refusing, changing nothing, when any
         * migration cannot be reversed — and the version Flarum records goes
         * back. The reversal itself waits for the next request, booted from
         * the restored code. See MigrationLedger.
         */
        $ledger = new MigrationLedger($this->workDirRoot);
        $pending = $ledger->prepare(
            resolve(ConnectionInterface::class),
            resolve(Migrator::class),
            resolve(ExtensionManager::class),
            $this->vendorPath
        );

        $undone = array_merge($pending ? [Lang::trans('restore.migrations_queued')] : [], (new Rollback(
            $this->vendorPath,
            $this->trashPath,
            $this->journal,
            $this->basePath,
            $this->workDirRoot
        ))->run());

        $note = null;

        if ($undone !== []) {
            /*
             * 🚨 `install`, not `dump-autoload`. Moving the files back leaves
             * vendor/composer/installed.json still claiming a package whose
             * directory is gone, and dump-autoload regenerates the autoloader
             * FROM that record — it faithfully rebuilds the wrong thing.
             *
             * A subprocess where the host allows, so no Flarum boots inside the
             * process that has just moved its files out from under it. In-process
             * otherwise: Composer's classes are not loaded by Flarum's boot, so
             * they come from the restored files.
             */
            try {
                // 🚨 Record first, or `install` re-extracts the packages just
                // put back — with a Composer that lives in this same vendor/,
                // which is how an update took wowcraft.online down. Synced
                // from the restored lock, install only rebuilds the autoloader.
                (new InstalledRecord($this->basePath))->syncFromLock();

                $result = $this->composer->run(['install', '--no-scripts']);

                if ($result['code'] === 0) {
                    $undone[] = Lang::trans('restore.record_put_back');
                } else {
                    $note = Lang::trans('restore.record_failed');
                }
            } catch (Throwable $e) {
                $note = Lang::trans('restore.composer_failed', ['error' => $e->getMessage()]);
            }
        }

        if ($undone !== [] && $note === null) {
            $note = $this->refresh($undone);
        }

        return ['undone' => $undone, 'note' => $note];
    }

    /**
     * Make the forum SERVE what was just put back.
     *
     * 🚨 Putting the files back is not the same as undoing the update. The
     * update's last phase published the new version's assets and rebuilt the
     * caches from it, and none of that lives in vendor/. Proved on the demo
     * forum: after rolling fof/polls back from rc.4 to rc.3, vendor and the
     * lock said rc.3 and public/assets/forum.js was still rc.4's code — the
     * browser running one version's JavaScript against the other's PHP, which
     * is the exact mismatch an update finishes by preventing.
     *
     * Same commands, same order, same runner as the update's finish.
     *
     * @param list<string> $undone appended to as each one succeeds
     */
    private function refresh(array &$undone): ?string
    {
        if (! is_file($this->basePath.'/flarum')) {
            return null;   // not a forum (the tests' trees)
        }

        /*
         * 🚨 In-process, the formatter is FORGOTTEN, not rebuilt. This request
         * booted the version being rolled back, so building the formatter
         * here would bake that version's extenders into the cache the older
         * code then renders with. Forgotten, the next request — running the
         * restored code — builds it from the right ones. Publishing assets and
         * clearing caches only copy and delete files, so they are safe here.
         */
        // A root-owned cache would fail cache:clear below; see StaleCache.
        $setAside = StaleCache::describe(StaleCache::setAside($this->storagePath));
        if ($setAside !== '') {
            $undone[] = $setAside;
        }

        $flarum = new FlarumCommand($this->basePath, $this->composer);
        $inProcess = ! $this->composer->processes();
        $commands = [
            ['assets:publish', []],
            ['cache:clear', []],
            ['extension-manager:repair-formatter', $inProcess ? ['--flush-only' => true] : []],
            // Or the browser keeps the undone version's JavaScript and strings.
            ['extension-manager:rebuild-assets', $inProcess ? ['--mark-dirty' => true] : []],
        ];

        foreach ($commands as [$command, $options]) {
            try {
                $result = $flarum->run($command, $options);
            } catch (Throwable $e) {
                $result = ['code' => 1, 'output' => $e->getMessage()];
            }

            if ($result['code'] !== 0) {
                return Lang::trans('restore.command_failed', ['command' => $command]);
            }
        }

        $undone[] = Lang::trans('restore.assets_rebuilt');

        /*
         * 🚨 And the web server's compiled-code cache, as an update's finish
         * does. Without it, Flarum-in-a-box (2026-10-07) kept serving the
         * newer version's extend.php after the files were put back: every page
         * 500'd on a class the restored version never had, until PHP-FPM was
         * restarted by hand.
         */
        $undone[] = (new Opcache())->clear()['why'];

        return null;
    }
}
