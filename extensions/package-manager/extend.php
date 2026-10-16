<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\ExtensionManager\Api\Controller;
use Flarum\ExtensionManager\Console\CheckCommand;
use Flarum\ExtensionManager\Console\PruneCommand;
use Flarum\ExtensionManager\Console\RebuildAssetsCommand;
use Flarum\ExtensionManager\Console\RepairFormatterCommand;
use Flarum\ExtensionManager\Console\UpdateCommand;
use Flarum\ExtensionManager\ExtensionManagerServiceProvider;
use Flarum\Extend;

return [
    (new Extend\ServiceProvider())
        ->register(ExtensionManagerServiceProvider::class),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    (new Extend\Console())
        ->command(CheckCommand::class)
        /*
         * 🚨 Registered as a command so it runs in its OWN process, after the
         * files have moved. Repairing the formatter means building it, and
         * building it inside the request that just replaced Flarum's files
         * loads a half-old class map.
         */
        ->command(RepairFormatterCommand::class)
        ->command(RebuildAssetsCommand::class)
        /*
         * 🚨 The same run the admin screen drives, with a terminal turning the
         * handle instead of a browser. Deliberately not scheduled, and never
         * will be: an update that starts itself is an update nobody chose to be
         * present for.
         */
        ->command(UpdateCommand::class)
        /*
         * 🚨 Daily, and cheap enough to mean it. This is one HTTP call per
         * installed package with no Composer involved — a resolve on a schedule
         * would be 165 MB on somebody else's shared host, every night, for a
         * question they may not have asked.
         */
        ->schedule(CheckCommand::class, function ($event): void { $event->daily(); })
        /*
         * 🚨 Tidies the rollback copies no rollback can reach. Also runs at the
         * end of every update; nightly catches the forum that has not updated
         * in a month and is still carrying the copies from the last one.
         *
         * 🚨 NO arguments, and it must stay that way: a scheduled
         * `['--dry-run' => true]` renders as `--dry-run='1'`, which a no-value
         * option refuses — every night, into /dev/null.
         */
        ->command(PruneCommand::class)
        ->schedule(PruneCommand::class, function ($event): void { $event->dailyAt('04:20'); }),

    new Extend\Locales(__DIR__.'/locale'),

    /*
     * 🚨 Two endpoints, and the split matters.
     *
     * `state` is a read: the admin screen polls it, and it must stay cheap
     * enough to poll every second or two without being noticed.
     *
     * `step` does exactly one unit of work and returns. That is the whole
     * design — progress is a function of how many times this is called, so a
     * host that cuts every request at thirty seconds can still finish an update
     * that takes ten minutes. Nothing here ever loops.
     */
    (new Extend\Routes('api'))
        ->get('/extension-manager/state', 'extension-manager.state', Controller\StateController::class)
        ->post('/extension-manager/check', 'extension-manager.check', Controller\CheckController::class)
        ->get('/extension-manager/release-notes', 'extension-manager.release-notes', Controller\ReleaseNotesController::class)
        ->post('/extension-manager/update', 'extension-manager.update', Controller\StartController::class)
        ->post('/extension-manager/step', 'extension-manager.step', Controller\StepController::class)
        ->post('/extension-manager/rollback', 'extension-manager.rollback', Controller\RollbackController::class)
        /*
         * GET is a dry run (what a prune would free, for the confirm); POST
         * prunes or saves the retention settings. Kept off /state, which is
         * polled during an update — sizing the trash walks every file in it.
         */
        ->get('/extension-manager/trash', 'extension-manager.trash', Controller\TrashController::class)
        ->post('/extension-manager/trash', 'extension-manager.trash.act', Controller\TrashController::class)
        /*
         * 🚨 Discovery is two endpoints, and the split is deliberate. `discover`
         * is one call to Packagist's search. `compat` is one call PER PACKAGE to
         * work out whether each result fits the Flarum actually installed —
         * doing both in one request would mean a dozen round trips before
         * anything appeared on a screen somebody is typing into.
         */
        ->get('/extension-manager/core', 'extension-manager.core', Controller\CoreController::class)
        /*
         * 🚨 One route, both verbs. GET reports where Composer looks, what it
         * accepts and which hosts have a credential — never the credentials
         * themselves. POST carries an action. Keeping them together means there
         * is one place that asserts admin for all of it.
         */
        ->get('/extension-manager/config', 'extension-manager.config', Controller\ConfigController::class)
        ->post('/extension-manager/config', 'extension-manager.config.set', Controller\ConfigController::class)
        ->get('/extension-manager/discover', 'extension-manager.discover', Controller\DiscoverController::class)
        ->post('/extension-manager/discover/compat', 'extension-manager.compat', Controller\CompatController::class)
        /*
         * 🚨 Millwright's paths, for the update that replaces Millwright with
         * this package. The admin tab that started it is still running
         * Millwright's script, and once the files swap that script's next
         * poll and step land here. Without them the run stalls at finalise.
         * Remove in Flarum 3.0.
         */
        ->get('/millwright/state', 'extension-manager.legacy.state', Controller\StateController::class)
        ->post('/millwright/step', 'extension-manager.legacy.step', Controller\StepController::class)
        ->post('/millwright/rollback', 'extension-manager.legacy.rollback', Controller\RollbackController::class),
];
