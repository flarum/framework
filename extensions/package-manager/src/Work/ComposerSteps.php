<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Work;

use Flarum\ExtensionManager\Host\StorageDir;
use Flarum\ExtensionManager\Lang;
use Flarum\ExtensionManager\Apply\Applier;
use Flarum\ExtensionManager\Apply\Journal;
use Flarum\ExtensionManager\Apply\Restore;
use Flarum\ExtensionManager\Host\CoreVersion;
use Flarum\ExtensionManager\Host\ErrorLog;
use Flarum\ExtensionManager\Host\FlarumUpdater;
use Flarum\ExtensionManager\Host\Opcache;
use Flarum\ExtensionManager\Host\SiteHealth;
use Flarum\ExtensionManager\Host\Verdict;
use Flarum\ExtensionManager\Plan\Change;
use Flarum\ExtensionManager\Plan\LockDiff;
use Flarum\ExtensionManager\Prune\Pruner;
use Flarum\ExtensionManager\Prune\Retention;
use Flarum\ExtensionManager\Run\NotYet;
use Flarum\ExtensionManager\Run\Reverted;
use Flarum\ExtensionManager\Run\Run;
use Flarum\ExtensionManager\Run\Steps;
use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\Config;
use Illuminate\Database\ConnectionInterface;
use RuntimeException;

/**
 * A real update, broken into pieces small enough that no single one needs a long
 * request.
 *
 * This is where the four phases stop being an idea and become Composer, HTTP and
 * renames. Nothing here is clever: the difficulty was all in making each unit
 * small, repeatable and honest, and that work is in LockDiff, Fetcher and
 * Applier. This class only says what the units are and in what order.
 *
 * 🚨 Every doItem() has to be safe to run twice. The driver saves progress AFTER
 * the work, so a process killed in between asks for the same item again on
 * resume. Fetch checks whether the package is already staged; apply's stash is a
 * no-op when the source is already gone; the finalise commands are all
 * idempotent by nature. That is a property to preserve, not an accident.
 */
class ComposerSteps implements Steps
{
    public function __construct(
        private string $installPath,
        private string $workDir,
        private ComposerRunner $composer,
        private Fetcher $fetcher,
        private Applier $applier,
        private Journal $journal,
        /** @var list<string> the packages the user asked to change */
        private array $requested = [],
        /** 'update', 'install' for something new, or 'remove' to take one out */
        private string $mode = 'update',
        /*
         * 🚨 Optional, and every one of them degrades to "cannot check" rather
         * than to "fine". A host that cannot be asked whether its site is up
         * must not have that read as a yes — see verify().
         */
        private string $vendorPath = '',
        private string $storagePath = '',
        private string $siteUrl = '',
        /**
         * 🚨 Requirements this run is authorised to RAISE, package => version.
         *
         * A forum that pins `page-builder` to `3.5.1` cannot be moved to 3.6.0
         * by a resolve, however many times somebody presses Update — the
         * constraint forbids it. Raising the pin is the only thing that helps,
         * and it edits composer.json, so it is never inferred: the admin is
         * shown the exact requirement change and it is recorded with the run.
         *
         * @var array<string,string>
         */
        private array $repin = [],
    ) {
    }

    public function itemsFor(string $phase, Run $run): array
    {
        return match ($phase) {
            'plan' => ['check the site', 'work out what changes', 'check file permissions'],
            'fetch' => array_map(fn (Change $c) => $c->package, $this->downloadable()),
            'apply' => array_map(fn (Change $c) => $c->package, $this->applyOrder()),
            /*
             * 🚨 'tidy the trash' is LAST, after the site has been checked: an
             * update that is about to be undone automatically must not have its
             * housekeeping run first. Its copies would be kept anyway — this
             * run is the newest — but there is no reason to find out.
             */
            /*
             * 🚨 Nothing, when the plan moved nothing. Migrating, publishing
             * assets and clearing every cache on a site that did not change is
             * all cost and no update, and it is what let a run that moved
             * nothing look exactly like one that did (live box, 2026-10-13).
             */
            'finalise' => is_file($this->workDir.'/plan.json') && $this->plan() === []
                ? []
                : ['register', 'migrations', 'assets', 'caches', 'code cache', 'check the site again', 'tidy the trash'],
            default => [],
        };
    }

    public function doItem(string $phase, string $item, Run $run): ?string
    {
        return match ($phase) {
            'plan' => match ($item) {
                'check the site' => $this->baseline(),
                'check file permissions' => $this->checkPermissions(),
                default => $this->resolve(),
            },
            'fetch' => $this->fetchOne($item),
            'apply' => $this->applyOne($item),
            'finalise' => $this->finalise($item, $run),
            default => null,
        };
    }

    /**
     * Ask Composer what would change, and write it down.
     *
     * 🚨 `--no-install`: this updates composer.lock and NOTHING else. Composer's
     * own install would rebuild a tree; all that is wanted here is the answer to
     * "what moves", which is 16 seconds and 165 MB rather than minutes and a
     * mutated vendor directory. The install is ours to do, one package at a
     * time, after the user has seen the list.
     */
    private function resolve(): string
    {
        $lockPath = $this->installPath.'/composer.lock';

        /*
         * 🚨 Saved BEFORE Composer is allowed to rewrite them, and used by the
         * rollback. `--no-install` updates the lock the moment it succeeds, so
         * without a copy taken here there is no way back to the site's own
         * record of itself — and a rollback that restores the files but not the
         * lock leaves a site whose manifest describes work that was undone.
         *
         * 🚨 Taken ONCE, and a retry starts from it. A resolve killed part-way
         * — a host's time limit, a proxy timeout — can leave composer.json
         * edited by `require` and the lock half-way to new. Copying again on
         * the retry would save THAT as "before", and the rollback would put
         * back the broken state; resolving on top of it would plan from it.
         */
        $this->startFromSaved();
        $before = $this->readJson($this->workDir.'/composer.lock.before');

        $inProcess = $this->composer->inProcess();

        if ($inProcess) {
            $blockers = (new NeedsGit($this->installPath, $this->composer->composerHome()))->blockers();

            if ($blockers !== []) {
                throw new RuntimeException(NeedsGit::explain($blockers));
            }
        }

        /*
         * 🚨 `require` for an install, `update` for an update, and they are not
         * interchangeable: running `update` on a package that is not installed
         * does nothing whatsoever and exits 0. The run would sail through every
         * phase, change nothing, and report success — the exact silent failure
         * this whole extension exists to stop.
         *
         * `require --no-install` writes composer.json as well as the lock, and
         * reverts its own edit if the resolve fails. composer.json.before is
         * saved above so a rollback after a SUCCESSFUL resolve can undo it too.
         */
        $args = match ($this->mode) {
            'install' => array_merge(['require'], $this->requested, ['--no-install', '--no-scripts']),
            /*
             * 🚨 `remove` takes the package out of composer.json AND re-resolves
             * the lock, which is what makes the dependencies it dragged in go
             * too. `--no-install` stops Composer touching vendor: the removal
             * happens through the same journalled apply as everything else, so
             * the files go to the trash rather than being deleted and an
             * uninstall is as reversible as an update.
             *
             * 🚨 `--no-update-with-dependencies`: a removal only takes things
             * away. Composer's default lets the removed package's dependencies
             * update too, so taking one extension out could move a library it
             * shared with another, which is a download and a change nobody
             * asked for (Ernest + ClaudiusH, 2026-10-13).
             */
            'remove' => array_merge(['remove'], $this->requested, ['--no-install', '--no-scripts', '--no-update-with-dependencies']),
            /*
             * 🚨 `-w`, NOT `-W`.
             *
             * `--with-all-dependencies` also updates packages that are ROOT
             * requirements — so asking to update one extension moved twenty-five
             * unrelated packages on a live forum: the whole illuminate stack,
             * commonmark, monolog. Nobody chose that, the extension being
             * updated did not need it, and on a site that had just been
             * carefully pinned it was precisely the thing the pinning existed
             * to prevent.
             *
             * `--with-dependencies` still lets a package's own dependencies move
             * when the new version needs them, which is the case that made -W
             * look necessary. What it will not do is quietly re-resolve things
             * the admin has deliberately fixed. If an update genuinely requires
             * a root requirement to move, Composer now says so and the admin
             * decides — which is the whole posture of this extension.
             */
            default => array_merge(['update'], $this->requested, ['--with-dependencies', '--no-install']),
        };

        $raised = $this->raisePins();

        $result = $inProcess ? $this->resolveInProcess($args) : $this->composer->run($args);
        $after = $this->readJson($lockPath);

        if ($result['code'] !== 0 && ! $this->didWhatWasAsked($before, $after)) {
            // Composer's own words, not a summary of them: it is usually precise
            // about which constraint could not be satisfied, and paraphrasing
            // that loses the only part anyone can act on.
            throw new RuntimeException(Lang::trans('composer.resolve_failed')."\n".$result['output']);
        }

        /*
         * 🚨 The new lock is put back immediately. Composer has already written
         * it, and leaving it in place while the packages on disk are still the
         * old ones would mean a lock that disagrees with vendor/ — which the
         * next resolve would silently build on. The plan we just computed is the
         * record of what has to happen to make them agree again.
         */
        $changes = (new LockDiff())->between($before, $after);
        $sources = (new LockDiff())->sources($after);
        $reasons = (new LockDiff())->reasons($changes, $after, $this->requested);

        file_put_contents($this->workDir.'/plan.json', json_encode([
            'changes' => array_map(fn (Change $c) => $c->toArray(), $changes),
            'sources' => $sources,
            'reasons' => $reasons,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        if ($changes === []) {
            return match ($this->mode) {
                'install' => Lang::trans('composer.nothing_changed_install'),
                'remove' => Lang::trans('composer.nothing_changed_remove'),
                default => $this->whyNothingMoved(),
            };
        }

        $shown = array_map(fn (Change $c) => $c->describe(), array_slice($changes, 0, 5));
        $note = Lang::trans('composer.will_change', ['count' => count($changes), 'list' => implode(', ', $shown)])
            .(count($changes) > 5 ? Lang::trans('composer.and_more', ['count' => count($changes) - 5]) : '');

        return $raised === [] ? $note : implode('; ', $raised).'. '.$note;
    }

    /**
     * How many times a resolve killed by the host is tried before giving up.
     *
     * 🚨 Six, from a measurement rather than a feeling: on dev's real
     * composer.json (346 packages, 22 private GitHub repositories) a cold
     * in-process resolve took 144s and a warm one 27s. Each killed attempt
     * keeps what it downloaded, so a host that cuts requests at 30 seconds gets
     * there in about five tries; three would give up on it with the cache
     * nearly full.
     */
    private const RESOLVE_ATTEMPTS = 6;

    /**
     * Copy the manifests aside the first time; put them back on every retry.
     */
    private function startFromSaved(): void
    {
        foreach (['composer.lock', 'composer.json'] as $file) {
            $saved = $this->workDir.'/'.$file.'.before';
            $live = $this->installPath.'/'.$file;

            if (is_file($saved)) {
                if (@file_get_contents($saved) !== @file_get_contents($live)) {
                    self::replaceFile($saved, $live);
                }
            } elseif (! copy($live, $saved)) {
                throw new RuntimeException(Lang::trans('composer.save_before_resolve_failed', ['file' => $file]));
            }
        }
    }

    /**
     * The resolve, inside this request — and what happens when the host kills
     * the request before Composer finishes.
     *
     * 🚨 On shared hosting the request has a time limit the Extension Manager may not be
     * allowed to lift, and a proxy in front of it may cut it off regardless. A
     * kill there is not a failure of the update, so it must not look like one:
     *
     *   - A shutdown function puts composer.json and the lock back the moment
     *     the request dies, so nothing is ever left half-written — PHP runs it
     *     after a "Maximum execution time" or memory fatal. A kill PHP never
     *     hears about (FPM's request_terminate_timeout, SIGKILL) is caught on
     *     the retry instead, by startFromSaved().
     *   - Composer keeps every package list it downloaded in COMPOSER_HOME
     *     under storage/, so the next attempt starts warm and gets further.
     *   - The admin page simply polls again; the next poll sees the attempt
     *     that never came back, says so in the run's log, and tries again — up
     *     to RESOLVE_ATTEMPTS times, then stops with a sentence naming the limit.
     *
     * @param list<string> $args
     * @return array{code:int, output:string}
     */
    private function resolveInProcess(array $args): array
    {
        $path = $this->workDir.'/resolve.attempt.json';
        $prev = is_file($path) ? (array) json_decode((string) @file_get_contents($path), true) : [];
        $attempts = (int) ($prev['attempts'] ?? 0);

        if ($attempts > 0) {
            $how = $this->howItStopped($prev);

            if ($attempts >= self::RESOLVE_ATTEMPTS) {
                @unlink($path);
                $this->startFromSaved();

                throw new RuntimeException(
                    Lang::trans('composer.resolve_gave_up', ['how' => $how, 'attempts' => self::RESOLVE_ATTEMPTS]).' '.$this->limitAdvice($prev)
                );
            }

            if (empty($prev['announced'])) {
                $prev['announced'] = true;
                @file_put_contents($path, json_encode($prev));

                throw new NotYet(
                    Lang::trans('composer.resolve_retrying', ['how' => $how, 'attempt' => $attempts + 1, 'attempts' => self::RESOLVE_ATTEMPTS])
                );
            }
        }

        $started = microtime(true);
        @file_put_contents($path, json_encode(['attempts' => $attempts + 1, 'announced' => false, 'startedAt' => $started]));

        $done = false;
        $workDir = $this->workDir;
        $installPath = $this->installPath;

        register_shutdown_function(static function () use (&$done, $path, $started, $workDir, $installPath): void {
            // @phpstan-ignore if.alwaysFalse ($done is set by reference once the work completes)
            if ($done) {
                return;
            }

            foreach (['composer.lock', 'composer.json'] as $file) {
                if (is_file("$workDir/$file.before")) {
                    self::replaceFile("$workDir/$file.before", "$installPath/$file");
                }
            }

            $error = error_get_last();
            $message = (string) ($error['message'] ?? '');
            $record = (array) json_decode((string) @file_get_contents($path), true);
            $record['stopped'] = match (true) {
                str_contains($message, 'Maximum execution time') => 'time',
                str_contains($message, 'Allowed memory size') => 'memory',
                default => 'unknown',
            };
            $record['seconds'] = (int) round(microtime(true) - $started);
            $record['timeLimit'] = (int) ini_get('max_execution_time');
            $record['memoryLimit'] = (string) ini_get('memory_limit');
            @file_put_contents($path, json_encode($record));
        });

        try {
            return $this->composer->run($args);
        } finally {
            $done = true;
            @unlink($path);
        }
    }

    /** @param array<string,mixed> $attempt */
    private function howItStopped(array $attempt): string
    {
        $seconds = (int) ($attempt['seconds'] ?? 0);

        return match ($attempt['stopped'] ?? null) {
            'time' => Lang::trans('composer.stopped_time', ['seconds' => (int) ($attempt['timeLimit'] ?? $seconds)]),
            'memory' => Lang::trans('composer.stopped_memory', ['limit' => (string) ($attempt['memoryLimit'] ?? '?')]),
            'unknown' => Lang::trans('composer.stopped_unknown', ['seconds' => $seconds]),
            default => Lang::trans('composer.stopped'),
        };
    }

    /** @param array<string,mixed> $attempt */
    private function limitAdvice(array $attempt): string
    {
        return match ($attempt['stopped'] ?? null) {
            'memory' => Lang::trans('composer.advice_memory'),
            default => Lang::trans('composer.advice_time'),
        };
    }

    /** Copy beside, then rename over: never a half-written file in place. */
    private static function replaceFile(string $from, string $to): bool
    {
        $tmp = $to.'.extension-manager-'.bin2hex(random_bytes(3));

        if (! @copy($from, $tmp) || ! @rename($tmp, $to)) {
            @unlink($tmp);

            return false;
        }

        return true;
    }

    /**
     * Raise the requirements this run was authorised to raise.
     *
     * 🚨 composer.json is edited BEFORE the resolve, and only for packages the
     * admin was shown. `composer.json.before` is already saved a few lines
     * above, and Rollback restores it — so an update that raises a pin and then
     * fails puts the requirement back with everything else.
     *
     * 🚨 It raises to an EXACT version, never to a range. A site that pinned
     * `3.5.1` deliberately stays pinned at `3.6.0`; quietly converting it to
     * `^3.6` would hand back the drift the pin was there to stop, as a side
     * effect of an unrelated click.
     *
     * @return list<string> what changed, for the log
     */
    private function raisePins(): array
    {
        if ($this->repin === [] || $this->mode !== 'update') {
            return [];
        }

        $path = $this->installPath.'/composer.json';
        $json = $this->readJson($path);
        $raised = [];

        foreach ($this->repin as $package => $version) {
            $current = $json['require'][$package] ?? null;

            // Only ever touch a package this run was actually asked about, and
            // only one the site really does require.
            if (! is_string($current) || ! in_array($package, $this->requested, true)) {
                continue;
            }

            if ($current === $version) {
                continue;
            }

            $json['require'][$package] = $version;
            $raised[] = Lang::trans('composer.pin_raised', ['package' => $package, 'version' => $version, 'current' => $current]);
        }

        if ($raised === []) {
            return [];
        }

        file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

        return $raised;
    }

    /**
     * Why an update changed nothing.
     *
     * 🚨 "Everything is already at the newest version it can be" is true and
     * useless, and on a pinned forum it is actively misleading.
     *
     * A site that pins `ernestdefoe/page-builder` to `3.5.1` will be told by the
     * update check that 3.6.0 exists — correctly, it does — and pressing Update
     * then runs a resolve that cannot move it, because the constraint forbids
     * it. The run reported Finished, the card still said an update was
     * available, and nothing anywhere said why. Somebody would press it again.
     *
     * So when a requested package did not move, name its constraint. The admin
     * can then do the one thing that would help.
     */
    private function whyNothingMoved(): string
    {
        $json = $this->readJson($this->installPath.'/composer.json');
        $pinned = [];

        foreach ($this->requested as $package) {
            $constraint = $json['require'][$package] ?? null;

            if (! is_string($constraint)) {
                continue;
            }

            /*
             * A constraint with no wildcard, caret or tilde admits exactly one
             * version, so a newer one can never satisfy it. Ranges are left out:
             * those genuinely can be "already newest".
             */
            if (preg_match('/^v?\d+\.\d+\.\d+/', $constraint) && ! preg_match('/[\^~*|]|\s-\s/', $constraint)) {
                $pinned[] = Lang::trans('composer.pinned_to', ['package' => $package, 'constraint' => $constraint]);
            }
        }

        if ($pinned === []) {
            return Lang::trans('composer.nothing_to_update');
        }

        return Lang::trans('composer.nothing_moved_pinned', ['pins' => implode('; ', $pinned)]);
    }

    /**
     * Did the command achieve what it was asked to, whatever it says?
     *
     * 🚨 Only consulted when the exit code is non-zero, and only for a removal.
     * `composer remove --no-install` updates composer.json and the lock exactly
     * as wanted, and THEN checks whether the package has gone from vendor —
     * which it has not, because --no-install is us telling Composer not to touch
     * vendor. It reports "Removal failed, X is still present" and exits 1,
     * having done the job perfectly.
     *
     * Reading the outcome rather than the exit code is the honest way out. The
     * lock is the thing that matters, we already have it before and after, and
     * a removal that did not happen fails this check and reports Composer's own
     * words as before.
     *
     * @param array<string,mixed> $before
     * @param array<string,mixed> $after
     */
    private function didWhatWasAsked(array $before, array $after): bool
    {
        if ($this->mode !== 'remove' || $this->requested === []) {
            return false;
        }

        $names = fn (array $lock) => array_column((array) ($lock['packages'] ?? []), 'name');

        $was = $names($before);
        $now = $names($after);

        foreach ($this->requested as $package) {
            if (! in_array($package, $was, true) || in_array($package, $now, true)) {
                return false;
            }
        }

        return true;
    }

    private function fetchOne(string $package): string
    {
        $plan = $this->planFile();
        $source = $plan['sources'][$package] ?? null;

        if ($source === null) {
            throw new RuntimeException(
                Lang::trans('composer.no_archive', ['package' => $package])
            );
        }

        $this->fetcher->fetch($package, $source);

        return Lang::trans('composer.downloaded', ['package' => $package]);
    }

    /**
     * The plan in the order it is applied: Flarum core LAST.
     *
     * 🚨 A host without processes applies one package per request, and the
     * moment core's files land every later request gets "Update Flarum".
     * Alphabetically core was 4th of 27 on rc.8 → nightly, so the other 23
     * swaps — and the database update after them — could never run. Last, it
     * is swapped in the same request that brings the database up to date.
     *
     * @return list<Change>
     */
    private function applyOrder(): array
    {
        $plan = $this->plan();
        usort($plan, fn (Change $a, Change $b) => (int) ($a->package === 'flarum/core') <=> (int) ($b->package === 'flarum/core'));

        return $plan;
    }

    private function applyOne(string $package): string
    {
        $plan = $this->applyOrder();

        foreach ($plan as $change) {
            if ($change->package === $package) {
                $done = $this->applier->applyOne($change);

                $this->dropStaleCode();

                // The last swap of an update that moved Flarum itself.
                if ($package === end($plan)->package && $this->movesCore($plan)) {
                    $done .= '. '.$this->bridgeCore();
                }

                return $done;
            }
        }

        throw new RuntimeException(Lang::trans('composer.not_in_plan', ['package' => $package]));
    }

    /** @param list<Change> $plan */
    private function movesCore(array $plan): bool
    {
        foreach ($plan as $change) {
            if ($change->package === 'flarum/core' && $change->op === Change::REPLACE) {
                return true;
            }
        }

        return false;
    }

    /**
     * Bring the database up to date in the SAME request that swapped core.
     *
     * 🚨 Once Flarum's files are newer than its database, every request gets
     * the "Update Flarum" page — the Extension Manager's own step endpoint included — so
     * the finalise phase could never be reached from the web, and neither
     * could the undo button. Where a process can be started, `php flarum
     * migrate` runs fresh, on the new code; where it cannot, Flarum's own
     * updater is asked to (see FlarumUpdater). If neither works, this request
     * undoes the update before it ends, because no later one could.
     */
    private function bridgeCore(): string
    {
        $db = resolve(ConnectionInterface::class);
        $ledger = new MigrationLedger($this->workDir);
        $ledger->before($db);
        $was = $db->table('settings')->where('key', 'version')->value('value');

        /*
         * 🚨 Only a new VERSION puts the forum behind "Update Flarum". A core
         * that moves without one — the nightly build (2.x-dev) to the release
         * it becomes, both of which say 2.0.0 — leaves the site answering, so
         * the migrations step later in this run is still reachable and does
         * the work. Bridging here would undo a correct
         * update: migrate cannot change a version that was already right, and
         * on a host without proc_open Flarum's updater is not there to ask.
         */
        if ($was !== null && $was === CoreVersion::onDisk($this->vendorPath !== '' ? $this->vendorPath : $this->installPath.'/vendor')) {
            return Lang::trans('composer.core_same_version', ['version' => $was]);
        }

        try {
            (new Opcache())->clear();

            if ($this->composer->canSpawn()) {
                $this->registerWith(null);
                $this->flarum('migrate', '');
            } else {
                (new FlarumUpdater($this->siteUrl, (array) (resolve(Config::class)['database'] ?? [])))->migrate();
            }

            $now = $db->table('settings')->where('key', 'version')->value('value');

            // The database is now ahead of the web server's compiled code: close
            // that window at once rather than within opcache's own timer.
            $this->clearWebCache();

            if ($now === $was) {
                throw new RuntimeException(Lang::trans('composer.core_db_unchanged', ['version' => $was]));
            }

            return Lang::trans('composer.core_moved', ['from' => $was, 'to' => $now]);
        } catch (\Throwable $e) {
            $restore = $this->restore();

            if ($restore === null || ! $restore->possible()) {
                throw new RuntimeException(Lang::trans('composer.core_db_failed', ['error' => $e->getMessage()]));
            }

            $done = $restore->run();

            throw new Reverted(
                Lang::trans('composer.core_db_failed_undone', ['error' => $e->getMessage()]).($done['note'] !== null ? ' '.$done['note'] : ''),
                $done['undone']
            );
        }
    }

    /**
     * Remove rollback copies no rollback can reach any more.
     *
     * 🚨 Never fails the run, and never takes long. The update is finished and
     * verified by now; housekeeping that throws must not turn it red and offer
     * to undo correct work, so a failure is reported in the log and left for
     * the nightly prune. The time budget keeps a host that cuts requests at 30
     * seconds from killing this step mid-way — what is left is picked up later.
     */
    private function tidyTrash(): string
    {
        if ($this->storagePath === '') {
            return Lang::trans('composer.tidy_no_storage');
        }

        try {
            $dir = StorageDir::of($this->storagePath);
            $summary = (new Pruner($dir, new Retention($dir)))->prune('finished update', 10.0);
        } catch (\Throwable $e) {
            return Lang::trans('composer.tidy_left', ['error' => $e->getMessage()]);
        }

        if ($summary['removed'] === 0) {
            return Lang::trans('composer.tidy_nothing');
        }

        return Lang::trans('composer.tidy_removed', ['count' => $summary['removed'], 'size' => Pruner::human((int) $summary['freed'])])
            .($summary['complete'] ? '' : ' '.Lang::trans('composer.tidy_rest_tonight'));
    }

    /** Longer than this and telling the truth beats parking the admin screen. */
    private const WAIT_CAP = 180;

    private function finalise(string $item, Run $run): string
    {
        return match ($item) {
            'register' => $this->register(),
            'migrations' => $this->migrate(),
            'assets' => $this->flarum('assets:publish', Lang::trans('composer.assets_published')),
            'caches' => $this->clearCaches(),
            'check the site again' => $this->verify($run),
            'tidy the trash' => $this->tidyTrash(),
            /*
             * 🚨 Last, and it is the step that decides whether any of the others
             * were visible. On a host with opcache.validate_timestamps off, every
             * phase above can succeed and the site carries on serving the old
             * compiled code until PHP is restarted — an update that reports
             * success and changed nothing anybody can see.
             */
            'code cache' => $this->codeCache(),
            default => Lang::trans('composer.nothing_to_do'),
        };
    }

    /**
     * 🚨 `install`, not `dump-autoload`, and the difference is whether the
     * update exists at all.
     *
     * Composer's record of what is installed is vendor/composer/installed.json,
     * not composer.lock — and `dump-autoload` regenerates the autoloader FROM
     * that record. So a package this pipeline placed by hand was autoloaded by
     * nothing and invisible to Flarum: the files were right, the lock was right,
     * every phase reported success, and the extension simply did not appear.
     * Found by installing one on a real forum and looking for it afterwards.
     *
     * `install` reconciles the record with the lock. It is cheap because the
     * tree already agrees — measured at 2.2s for a one-package change on a
     * 226-package forum, touching only what moved.
     *
     * 🚨 It does re-extract the packages this run changed, because Composer
     * trusts its own record rather than the disk. That is a known cost and it
     * is worth it: the staged apply is what makes the RISKY window atomic and
     * reversible, and this runs afterwards, against a tree that is already
     * consistent, where being killed costs a rerun rather than a broken site.
     */
    /**
     * Clear Flarum's caches, and leave the formatter able to render.
     *
     * 🚨 `cache:clear` on its own takes post rendering down, and this is not a
     * race — it happens every time on any forum whose cache driver is not the
     * file store, which is every forum running fof/redis.
     *
     * The formatter is two halves that must agree: a serialized renderer cached
     * under `flarum.formatter`, and the generated
     * storage/formatter/Renderer_<hash>.php it is an instance of. cache:clear
     * unlinks that file and flushes the APPLICATION cache — but core gives the
     * formatter its own FileStore, so the entry survives in a different store,
     * names a class whose file is gone, and every post render dies with
     * `__PHP_Incomplete_Class`. `rememberForever` means nothing fixes it later.
     *
     * 🚨 An earlier version of this method deleted storage/formatter/*.php
     * itself, on the theory that clearing both halves left a consistent state.
     * That was precisely backwards: deleting the file while the entry survives
     * IS the break, so it manufactured the failure it was written to prevent —
     * twice per run. Two live sites went down that way.
     *
     * The repair command forgets the entry through the formatter's OWN cache and
     * rebuilds both halves together.
     */
    private function clearCaches(): string
    {
        $this->flarum('cache:clear', 'cleared');
        $this->flarum('extension-manager:repair-formatter', 'formatter rebuilt');
        // Before Flarum 2.0.0, cache:clear leaves the compiled bundles as they
        // were: the update's new screen never reached the browser.
        $this->needsFreshCode();
        $rebuilt = (new FlarumCommand($this->installPath, $this->composer))->run('extension-manager:rebuild-assets');
        $warnings = array_values(array_filter(
            array_map('trim', explode("\n", (string) $rebuilt['output'])),
            fn (string $line) => str_starts_with($line, 'Warning:')
        ));

        return Lang::trans('composer.caches_cleared')
            .($warnings === [] ? '' : '. '.implode(' ', $warnings));
    }

    /**
     * 🚨 Never fails the run.
     *
     * By this point the files are updated, the lock is updated, and Composer's
     * record agrees with both. A compiled-code cache that could not be cleared
     * is a thing to TELL somebody about, not a reason to mark a completed update
     * as failed and offer to roll back work that was correct.
     */
    /**
     * What the site was doing BEFORE we touched anything.
     *
     * 🚨 Without this, an automatic rollback is a liability rather than a
     * safety net. A site that was already down for its own reasons — a bad
     * config, a full disk, a database that went away — would fail the check
     * afterwards too, and the update would be blamed and undone for a fault it
     * had nothing to do with. Worse, so would a host where the check itself
     * cannot run: no curl, no outbound DNS, a URL that resolves to somewhere
     * else. On any of those the honest answer is "this update will not be
     * judged by whether the site answers", and the only way to know is to have
     * asked before.
     */
    private function baseline(): string
    {
        $health = $this->health();

        if ($health === null) {
            $this->rememberHealth('unchecked');

            return Lang::trans('composer.health_no_address');
        }

        $result = $health->check(2);
        $this->rememberHealth($result['ok'] ? 'ok' : 'down');

        return $result['ok']
            ? Lang::trans('composer.health_ok_before')
            : Lang::trans('composer.health_down_before', ['why' => $result['why']]);
    }

    /**
     * Did this update break the site? If so, put it back and say why.
     *
     * 🚨 This is the last item on purpose. It runs after the code cache step
     * has waited for the web tier to be serving the new files, so what it asks
     * is a real question: a check that ran before then would be asking whether
     * the OLD code still works, and would pass every time.
     */
    private function verify(Run $run): string
    {
        $health = $this->health();
        $before = $this->rememberedHealth();

        if ($health === null || $before === 'unchecked') {
            return Lang::trans('composer.health_not_checked');
        }

        $result = $health->check(3);

        /*
         * 🚨 Flarum answers 503 with its "Update Flarum" page while php-fpm is
         * still running the old code against a database the update already
         * migrated. That's the web server not having re-read the files yet,
         * not a broken update. Within the window it can take, wait and look
         * again rather than put back an update that is about to work.
         */
        if (! $result['ok'] && ($result['status'] ?? null) === 503) {
            $situation = (new Opcache())->situation();
            $freq = (int) ($situation['freq'] ?? 0);

            if ($situation['validates'] && $freq > 0 && $freq <= self::WAIT_CAP && time() < $this->codeLiveAt($freq) + $freq) {
                throw new NotYet(Lang::trans('composer.health_503_waiting'));
            }
        }

        switch (Verdict::from($before, $result['ok'])) {
            case Verdict::HEALTHY:
                return Lang::trans('composer.health_ok_after');

            case Verdict::ALREADY_BROKEN:
                return Lang::trans('composer.health_still_down');

            case Verdict::NOT_JUDGED:
                return Lang::trans('composer.health_not_checked');
        }

        /*
         * It worked before and it does not now. The update is the difference.
         */
        $why = $this->storagePath === ''
            ? null
            : (new ErrorLog($this->storagePath))->latest(max(1, $run->startedAt));

        $said = $why === null
            ? $result['why']
            : $result['why'].' '.Lang::trans('composer.the_error_was', ['error' => $why]);

        $restore = $this->restore();

        if ($restore === null || ! $restore->possible()) {
            throw new RuntimeException(
                Lang::trans('composer.broke_site_no_restore').' '.$said
            );
        }

        // Putting it back runs Composer, which must not happen in-process
        // inside a long-lived process holding the update's classes.
        $this->needsFreshCode();

        $done = $restore->run();

        $message = Lang::trans('composer.broke_site_restored').' '.$said;

        if ($done['note'] !== null) {
            $message .= ' '.$done['note'];
        }

        throw new Reverted($message, $done['undone']);
    }

    /**
     * From the command line, ask the web server to reset its compiled code and
     * wait briefly for a request to take it up. True when it did. On the web
     * this process can reset its own, so there is nothing to ask.
     */
    private function clearWebCache(): bool
    {
        if (PHP_SAPI !== 'cli' || $this->storagePath === '' || ($health = $this->health()) === null) {
            return false;
        }

        Opcache::requestWebReset($this->storagePath);

        for ($i = 0; $i < 10; $i++) {
            $health->check(1);   // any answer will do; booting Flarum takes the flag

            if (! Opcache::webResetPending($this->storagePath)) {
                return true;
            }

            usleep(500_000);
        }

        return false;
    }

    /**
     * 🚨 Straight after the files change, not at the end of the run.
     *
     * With revalidate_freq=60 the web server kept running a package's OLD
     * compiled extend.php against its NEW files for every step between the swap
     * and 'code cache'. fof/best-answer 2.0.0 deleted a service provider its
     * beta still registered, and every page and the admin panel on fbsfb.com
     * answered "class not found" until the cache was cleared (2026-10-15).
     */
    private function dropStaleCode(): void
    {
        if (PHP_SAPI !== 'cli') {
            (new Opcache())->clear();
        } else {
            $this->clearWebCache();
        }
    }

    private function health(): ?SiteHealth
    {
        return $this->siteUrl === '' ? null : new SiteHealth($this->siteUrl);
    }

    private function restore(): ?Restore
    {
        if ($this->vendorPath === '' || $this->storagePath === '') {
            return null;
        }

        return new Restore(
            $this->vendorPath,
            $this->installPath,
            $this->workDir,
            StorageDir::of($this->storagePath).'/trash',
            $this->journal,
            $this->composer,
            $this->storagePath
        );
    }

    /**
     * 🚨 On disk, in the run's own scratch space, because the process that
     * takes the baseline is very often not the process that checks it again —
     * a queue worker starts the run and an admin page polling finishes it. In
     * memory it would simply be absent by then, which reads as "never checked"
     * and quietly disables the whole safety net.
     */
    private function rememberHealth(string $state): void
    {
        @file_put_contents($this->workDir.'/health.before', $state);
    }

    private function rememberedHealth(): string
    {
        $saved = @file_get_contents($this->workDir.'/health.before');

        return is_string($saved) && $saved !== '' ? trim($saved) : 'unchecked';
    }

    /**
     * 🚨 This step WAITS, and that is the whole point of it.
     *
     * It used to clear what it could, return a sentence, and let the run finish.
     * On a queue worker it cannot reach the web server's compiled-code cache at
     * all, so the sentence it returned was "PHP will pick them up within 60
     * seconds on its own" — and the run went green anyway.
     *
     * That is a completed update that is not in effect. An admin reads
     * "Finished", enables the extension it just installed, and for the rest of
     * that minute every request boots a database that says the extension is on
     * against an autoloader that has never heard of it. Which is not a theory:
     * it took a live forum down, and the log line explaining it was sitting
     * right there in the run the whole time.
     *
     * So the run stays in progress until the web tier can actually see the new
     * files. Finished now means in effect.
     */
    private function codeCache(): string
    {
        if ($this->clearWebCache()) {
            return Lang::trans('composer.web_cache_cleared');
        }

        $opcache = new Opcache();
        $situation = $opcache->situation();
        $result = $opcache->clear();

        if ($result['done']) {
            return $result['why'];
        }

        /*
         * Nothing to wait FOR: this host is set never to re-read files, so the
         * change becomes live when somebody restarts PHP and not a moment
         * before. Waiting would hang the run forever to no purpose — the honest
         * answer is the one the clear() call already wrote.
         */
        if (! $situation['validates']) {
            return $result['why'];
        }

        $live = $this->codeLiveAt(max(1, (int) $situation['freq']));
        $left = $live - time();

        if ($left > 0) {
            /*
             * Bounded. A host with a very long revalidate_freq would otherwise
             * park the admin screen for as long as that window, which is worse
             * than telling the truth and letting them restart PHP.
             */
            if ($left > self::WAIT_CAP) {
                return Lang::trans('composer.wait_too_long', ['seconds' => $situation['freq']]);
            }

            throw new NotYet(
                Lang::trans('composer.waiting_reread', ['seconds' => $left])
            );
        }

        return Lang::trans('composer.reread_done');
    }

    /**
     * When the web tier is guaranteed to have re-read the autoloader.
     *
     * 🚨 Measured from the AUTOLOADER, not from now. It is the file whose
     * staleness actually breaks a site — a class that exists on disk and is
     * unknown to the running process — and it stops changing at the register
     * step, so the phases in between count towards the wait instead of being
     * added to it.
     *
     * The margin is because revalidate_freq counts from when a process last
     * checked a file, not from when the file changed, so the worst case is one
     * full window after the write.
     */
    private function codeLiveAt(int $freq): int
    {
        /*
         * 🚨 Timed from Composer's install record as well as the autoloader.
         * The autoloader is only rewritten when the set of classes changes, and
         * 2.0.0-rc.8 → 2.0.0 changed none, so its timestamp was days old and the
         * wait was skipped. The site was checked while php-fpm still ran rc.8
         * against a database already at 2.0.0, answered Flarum's 503 "Update
         * Flarum" page, and a correct update was put back (wowcraft,
         * 2026-10-09). InstalledRecord rewrites installed.json on every run.
         */
        clearstatcache();
        $changed = max(
            (int) @filemtime($this->installPath.'/vendor/composer/autoload_static.php'),
            (int) @filemtime($this->installPath.'/vendor/composer/installed.json'),
        );

        return ($changed ?: time()) + $freq + 2;
    }

    /**
     * Refuse before anything is downloaded or moved if the run would hit a file
     * it cannot change. See Permissions for the two half-finished runs this
     * replaces. Skipped, not passed, when the paths were never supplied.
     */
    private function checkPermissions(): string
    {
        if ($this->vendorPath === '' || $this->storagePath === '') {
            return Lang::trans('composer.permissions_not_checked');
        }

        // A root-owned cache is moved aside rather than refused; see StaleCache.
        $setAside = StaleCache::describe(StaleCache::setAside($this->storagePath));

        $permissions = new Permissions($this->installPath, $this->vendorPath, $this->storagePath);
        $blocked = $permissions->blocked($this->plan());

        if ($blocked !== []) {
            // The resolve has already rewritten these; put them back so the
            // refusal's "Nothing was changed" is true and no rollback is owed.
            foreach (['composer.lock', 'composer.json'] as $file) {
                $saved = $this->workDir.'/'.$file.'.before';
                if (is_file($saved)) {
                    @copy($saved, $this->installPath.'/'.$file);
                }
            }

            throw new RuntimeException($permissions->explain($blocked));
        }

        return Lang::trans('composer.permissions_ok').($setAside !== '' ? '. '.$setAside : '');
    }

    /**
     * Make Composer's record and autoloader match the tree, without letting it
     * re-extract anything. See InstalledRecord for the outage this prevents.
     *
     * 🚨 The dry run is the guard, not a formality. If Composer would still
     * change files after the record is synced, something disagrees that this
     * step does not understand — and finding that out by letting Composer
     * delete its own dependencies is how a site goes down. Stopping here leaves
     * the applied tree in place and rollback available.
     */
    private function register(): string
    {
        $this->needsFreshCode();

        $this->raisePins();

        $inProcess = $this->composer->inProcess();
        $snapshot = $inProcess ? $this->snapshotAutoloader() : null;

        try {
            $done = $this->registerWith($snapshot);
            $this->dropStaleCode();

            return $done;
        } catch (\Throwable $e) {
            if ($snapshot !== null) {
                $this->restoreAutoloader($snapshot);
            }

            throw $e;
        }
    }

    private function registerWith(?string $snapshot): string
    {
        (new InstalledRecord($this->installPath))->syncFromLock();

        $dry = $this->composer->run(['install', '--no-scripts', '--dry-run']);
        if ($dry['code'] !== 0) {
            throw new RuntimeException(Lang::trans('composer.composer_failed')."\n".$dry['output']);
        }

        $planned = InstalledRecord::plannedOperations($dry['output']);
        if ($planned > 0) {
            throw new RuntimeException(
                Lang::trans('composer.still_wants_changes', ['count' => $planned])."\n"
                .$dry['output']
            );
        }

        if ($snapshot === null) {
            return $this->composerCommand(['install', '--no-scripts'], Lang::trans('composer.registered'));
        }

        /*
         * 🚨 In-process, the autoloader is written by THIS request, and a host
         * that kills it mid-write would leave vendor/composer half old, half
         * new: every page a fatal. The snapshot taken before the record was
         * synced goes back on the way down, so a kill leaves the autoloader
         * exactly as the apply phase left it — which rollback understands.
         */
        $done = false;
        register_shutdown_function(function () use (&$done, $snapshot): void {
            // @phpstan-ignore booleanNot.alwaysTrue ($done is set by reference once the work completes)
            if (! $done) {
                $this->restoreAutoloader($snapshot);
            }
        });

        try {
            return $this->composerCommand(['install', '--no-scripts'], Lang::trans('composer.registered'));
        } finally {
            $done = true;
        }
    }

    /**
     * Copy Composer's record and autoloader aside before an in-process install.
     */
    private function snapshotAutoloader(): string
    {
        $dir = $this->workDir.'/register.before';
        $source = $this->installPath.'/vendor/composer';

        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new RuntimeException(Lang::trans('composer.autoloader_copy_failed'));
        }

        foreach ([...(glob($source.'/*.php') ?: []), $source.'/installed.json', $this->installPath.'/vendor/autoload.php'] as $file) {
            if (is_file($file) && ! copy($file, $dir.'/'.$this->snapshotName($file))) {
                throw new RuntimeException(Lang::trans('composer.autoloader_copy_failed'));
            }
        }

        return $dir;
    }

    private function restoreAutoloader(string $dir): void
    {
        foreach (glob($dir.'/*') ?: [] as $saved) {
            $name = basename($saved);
            $target = $name === 'autoload.php'
                ? $this->installPath.'/vendor/autoload.php'
                : $this->installPath.'/vendor/composer/'.substr($name, strlen('composer.'));

            self::replaceFile($saved, $target);
        }
    }

    private function snapshotName(string $file): string
    {
        return basename(dirname($file)) === 'composer' ? 'composer.'.basename($file) : basename($file);
    }

    private function composerCommand(array $args, string $note): string
    {
        $raised = $this->raisePins();

        $result = $this->composer->run($args);

        if ($result['code'] !== 0) {
            throw new RuntimeException(Lang::trans('composer.composer_failed')."\n".$result['output']);
        }

        return $note;
    }

    /** `flarum migrate`, writing down what it ran so undo can reverse it. See MigrationLedger. */
    private function migrate(): string
    {
        $ledger = new MigrationLedger($this->workDir);
        $db = resolve(ConnectionInterface::class);

        $ledger->before($db);
        $this->flarum('migrate', 'migrations run');
        $added = $ledger->after($db, resolve(ExtensionManager::class), $this->vendorPath);

        return $added === [] ? Lang::trans('composer.migrations_none') : Lang::trans('composer.migrations_run', ['count' => count($added)]);
    }

    private function flarum(string $command, string $note): string
    {
        /*
         * 🚨 After the swap, and in a process that has the new code: its own
         * process where the host allows one; otherwise this request, which
         * needsFreshCode() has made sure started after the swap and after
         * opcache let go of the old files. Booting Flarum's commands inside the
         * request that replaced its files would load a half-old, half-new class
         * map.
         *
         * 🚨 Through the same PhpBinary the Composer phases use. When these
         * disagreed, an update could get through planning, downloading and the
         * swap — every expensive, risky part — and then fail on the last phase
         * because this one alone was spawned with a binary that cannot run a
         * script. Under FPM, PHP_BINARY is php-fpm.
         */
        $this->needsFreshCode();

        $result = (new FlarumCommand($this->installPath, $this->composer))->run($command);

        if ($result['code'] !== 0) {
            $lines = explode("\n", $result['output']);

            throw new RuntimeException(Lang::trans('composer.flarum_command_failed', ['command' => $command])."\n".implode("\n", array_slice($lines, -12)));
        }

        return $note;
    }

    /**
     * Wait for a fresh request before running post-swap code in this process.
     * A no-op wherever a subprocess does the work.
     */
    private function needsFreshCode(): void
    {
        if (! $this->composer->canSpawn()) {
            (new FreshCode($this->workDir, $this->installPath, new Opcache()))->ensure();
        }
    }

    /** @return list<Change> */
    private function plan(): array
    {
        return array_map(
            fn (array $row) => Change::fromArray($row),
            $this->planFile()['changes'] ?? []
        );
    }

    /** @return list<Change> */
    private function downloadable(): array
    {
        $sources = $this->planFile()['sources'] ?? [];

        return array_values(array_filter(
            $this->plan(),
            fn (Change $c) => $c->op !== Change::REMOVE && isset($sources[$c->package])
        ));
    }

    /** @return array<string,mixed> */
    private function planFile(): array
    {
        $path = $this->workDir.'/plan.json';

        if (! is_file($path)) {
            return [];
        }

        return (array) json_decode((string) file_get_contents($path), true);
    }

    /** @return array<string,mixed> */
    private function readJson(string $path): array
    {
        return (array) json_decode((string) file_get_contents($path), true);
    }
}
