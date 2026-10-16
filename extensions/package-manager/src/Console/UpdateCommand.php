<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Console;

use Flarum\ExtensionManager\Host\Capability;
use Flarum\ExtensionManager\Host\StorageDir;
use Flarum\ExtensionManager\Plan\Nightly;
use Flarum\ExtensionManager\Plan\Repin;
use Flarum\ExtensionManager\Run\Run;
use Flarum\ExtensionManager\Run\RunStore;
use Flarum\ExtensionManager\Run\StepRunner;
use Flarum\ExtensionManager\Work\UpdateCheck;
use Flarum\ExtensionManager\Work\WorkDir;
use Flarum\Console\AbstractCommand;
use Flarum\Foundation\Paths;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * `php flarum extension-manager:update vendor/name` — the update the admin screen
 * does, driven from a terminal.
 *
 * 🚨 It is the SAME run. Not a second implementation that happens to call
 * Composer: it writes the same work directory, advances the same phases through
 * the same StepRunner, journals the same way and rolls back the same way. A
 * console command that reimplemented any of that would be a second set of bugs
 * and, worse, a second answer to "what actually happened", which is the one
 * thing this extension exists to keep singular.
 *
 * The difference is only who turns the handle. The screen polls `step` because
 * a browser request may be cut off at thirty seconds; here nothing is going to
 * cut us off, so this loops until the run finishes and prints the log as it
 * goes.
 *
 * 🚨 It deliberately does NOT nudge a queue worker. On a forum with Horizon,
 * handing the run to a worker as well would mean two drivers on one run — the
 * lock in StepRunner makes that safe rather than corrupting, but it also means
 * this process would sit watching someone else work and could not report a
 * failure as its own exit code. Driving it here keeps the exit code truthful,
 * which is the entire reason to want this in a script.
 *
 * Updates only. Installing something new and removing something installed are
 * both available on the Extension Manager screen, and removal in particular has guards
 * — is it a direct requirement, is it still enabled — that belong with the
 * screen that can explain them.
 */
class UpdateCommand extends AbstractCommand
{
    /**
     * Log lines already printed, by index.
     *
     * 🚨 By index and by value, not by counting. A waiting step REPLACES the
     * last line rather than appending (Run::waiting), so a counter would either
     * miss it or repeat everything after it.
     *
     * @var array<int,string>
     */
    private array $printed = [];

    public function __construct(
        private Paths $paths,
        private RunStore $runs,
        private StepRunner $runner,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('extension-manager:update')
            ->setDescription('Update installed packages, as one checkpointed run that rolls back if it fails.')
            ->addArgument(
                'packages',
                InputArgument::IS_ARRAY,
                'Packages to update, as vendor/name. Leave empty and pass --all for everything the last check found.'
            )
            ->addOption('all', null, InputOption::VALUE_NONE, 'Update every package the last check found a newer version for.')
            ->addOption(
                'repin',
                null,
                InputOption::VALUE_NONE,
                'Allow raising a requirement this site pins to an exact version. Without it, a pinned package is refused rather than silently skipped.'
            )
            ->addOption('nightly', null, InputOption::VALUE_NONE, 'Move Flarum and its bundled extensions to the nightly build ({major}.x-dev). Undo returns them to the release they were on.')
            ->addOption('resume', null, InputOption::VALUE_NONE, 'Drive the run already in progress instead of starting a new one.')
            ->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'Stop waiting after this many seconds.', '3600');
    }

    protected function fire(): int
    {
        if ($this->input->getOption('resume')) {
            return $this->resume();
        }

        $named = array_values(array_filter(array_map('strval', (array) $this->input->getArgument('packages'))));
        $all = (bool) $this->input->getOption('all');

        if ($this->input->getOption('nightly')) {
            return $this->nightly();
        }

        if ($all && $named !== []) {
            $this->error('Name the packages, or pass --all. Both together is ambiguous, so nothing was started.');

            return 1;
        }

        if ($all) {
            $named = array_keys((array) ($this->check()->current($this->paths->base.'/composer.lock')['updates'] ?? []));

            if ($named === []) {
                /*
                 * Exit 0: nothing to do is not a failure, and a script that
                 * runs this nightly should not page anyone over it.
                 */
                $this->info('Everything the last check knows about is already up to date.');

                if ($this->check()->isStale()) {
                    $this->info('That check is no longer fresh, though — run extension-manager:check --force first.');
                }

                return 0;
            }
        }

        if ($named === []) {
            $this->error('Name at least one package as vendor/name, or pass --all.');

            return 1;
        }

        foreach ($named as $package) {
            // The only value here that reaches a command line.
            if (! preg_match('#^[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*$#', $package)) {
                $this->error("That is not a package name: $package");

                return 1;
            }
        }

        if (($refusal = $this->notInstalled($named)) !== null) {
            /*
             * 🚨 Refused rather than attempted, because `composer update` on a
             * package that is not installed does nothing at all and reports
             * success. A typo would otherwise print a clean run and leave the
             * site exactly as it was.
             */
            $this->error($refusal);

            return 1;
        }

        if ((new Capability($this->paths->base, \Flarum\ExtensionManager\Host\PhpBinary::forStorage($this->paths->storage), $this->paths->storage.'/.composer'))->resolveTier() === Capability::NONE) {
            $this->error('This host does not have enough memory for Composer to work out what an update involves. '
                .'Nothing was started. Ask your host to raise memory_limit to 256 MB and try again.');

            return 1;
        }

        $existing = $this->runs->latest();

        if ($existing !== null && ! $existing->isFinished()) {
            $age = time() - $existing->movedAt;

            $this->error("An update is already in progress: {$existing->id}, at {$existing->phase}"
                .($age > 120 ? ', and nothing has moved for '.round($age / 60).' minutes' : '').'.');
            $this->error('Drive it with --resume, or abandon it on the Extension Manager screen. Nothing was started.');

            return 1;
        }

        $repinner = new Repin($this->paths->base.'/composer.json', $this->storagePath('updates.json'));
        $pins = $repinner->pins($named);

        if (array_diff_key($repinner->pinned($named), $pins) !== []) {
            /*
             * 🚨 A pin with no newer version in the stored check. Straight
             * after a release the check still predates it, so --repin had
             * nothing to raise to: the run went through every step, said
             * "Done." and moved nothing (live box, 2026-10-13, v1.21.2). Ask
             * again before deciding; refuse if there is still nothing newer.
             */
            $this->info('The last check knows of nothing newer for '
                .implode(', ', array_keys(array_diff_key($repinner->pinned($named), $pins))).', so checking again first.');
            $this->recheck();
            $pins = $repinner->pins($named);

            if (($stuck = array_diff_key($repinner->pinned($named), $pins)) !== []) {
                foreach ($stuck as $package => $constraint) {
                    $this->error("$package is pinned at $constraint and no newer version was found, so an update cannot move it.");
                }

                $this->error('Nothing was started.');

                return 1;
            }
        }

        $repin = [];

        if ($pins !== []) {
            if (! $this->input->getOption('repin')) {
                /*
                 * 🚨 The failure this command exists to end. Without the flag
                 * we could still start a run, and Composer would answer
                 * "Nothing to modify in lock file", exit 0, and the run would
                 * go green having moved nothing. Refusing, by name, with the
                 * flag that fixes it, is the only honest answer.
                 */
                $this->error(count($pins).' package(s) are pinned to an exact version, so an update cannot move them:');

                foreach ($pins as $package => $pin) {
                    $this->error("  $package is pinned at {$pin['from']} and would have to be raised to {$pin['to']}");
                }

                $this->error('Nothing was started. Pass --repin to raise those requirements to the version the check found.');

                return 1;
            }

            foreach ($pins as $package => $pin) {
                $repin[$package] = $pin['to'];
                $this->info("Raising $package from {$pin['from']} to {$pin['to']}.");
            }
        }

        $id = 'r'.date('Ymd-His').'-'.bin2hex(random_bytes(3));

        (new WorkDir($this->paths->storage, $id))->create()->remember($named, 'update', $repin);

        $this->info('Updating '.implode(', ', $named));

        return $this->pump($this->runner->begin($id));
    }

    /**
     * Take over a run somebody else started.
     *
     * 🚨 Worth having on its own: a run left behind by a browser tab that was
     * closed, or by a worker that died, is exactly the state that used to need
     * someone to know what a work directory was.
     */
    private function resume(): int
    {
        $run = $this->runs->latest();

        if ($run === null) {
            $this->error('There is no run to resume.');

            return 1;
        }

        if ($run->isFinished()) {
            $this->info("The last run ({$run->id}) already finished: {$run->state}.");

            return $run->state === Run::DONE ? 0 : 1;
        }

        $this->info("Resuming {$run->id}, at {$run->phase}.");

        return $this->pump($run);
    }

    /**
     * Turn the handle until the run stops, printing what it says as it says it.
     */
    private function pump(Run $run): int
    {
        $deadline = time() + max(60, (int) $this->input->getOption('timeout'));

        while (true) {
            $this->emit($run->log);

            if ($run->isFinished()) {
                break;
            }

            if (time() > $deadline) {
                /*
                 * 🚨 The run is NOT abandoned or rolled back here. It is on
                 * disk, mid-phase, and resumable — deciding to undo somebody's
                 * half-finished update because a timeout elapsed is not this
                 * command's call to make.
                 */
                $this->error("Gave up waiting after {$this->input->getOption('timeout')}s. "
                    ."The run is not lost: {$run->id} is at {$run->phase} and `extension-manager:update --resume` will carry on.");

                return 1;
            }

            $run = $this->runner->step($run->id);

            if ($this->runner->needsWebRequest()) {
                $this->emit($run->log);
                $this->error("This host cannot start separate processes, so the rest of {$run->id} has to run from "
                    .'the web: open the Extension Manager in the admin panel and it will carry on from here.');

                return 1;
            }

            if ($this->runner->wasBusy() || $this->runner->wasWaiting()) {
                // Another driver has it, or the work is done but not live yet.
                $this->pause();
            }
        }

        if ($run->state === Run::DONE && $this->movedNothing($run)) {
            /*
             * 🚨 Green, and nothing changed. Whatever the reason — a dependency
             * holding it back, a constraint — the exit code must not say this
             * update happened. The plan's own sentence is in the log above.
             */
            $this->error("Finished, but nothing moved: {$run->id} changed no package. See the log above for why.");

            return 1;
        }

        if ($run->state === Run::DONE) {
            $this->info('Done.');

            return 0;
        }

        if ($run->state === Run::ROLLBACK) {
            $this->error("Rolled back at {$run->errorStep}: {$run->error}");
            $this->error('The site is as it was before this started.');

            return 1;
        }

        $this->error("Failed at {$run->errorStep}: {$run->error}");
        $this->error("Nothing was rolled back automatically. Roll {$run->id} back from the Extension Manager screen, "
            .'or fix the cause and run this again.');

        return 1;
    }

    /**
     * Print log lines that are new, or that have changed in place.
     *
     * @param list<string> $log
     */
    private function emit(array $log): void
    {
        foreach ($log as $i => $line) {
            if (($this->printed[$i] ?? null) === $line) {
                continue;
            }

            $this->printed[$i] = $line;
            $this->info('  '.$line);
        }
    }

    /** Which of these the site does not actually have, if any. */
    private function notInstalled(array $packages): ?string
    {
        $lock = @file_get_contents($this->paths->base.'/composer.lock');

        if ($lock === false) {
            // Nothing to check against; the plan phase will say so properly.
            return null;
        }

        $data = (array) json_decode($lock, true);
        $have = [];

        foreach (array_merge((array) ($data['packages'] ?? []), (array) ($data['packages-dev'] ?? [])) as $package) {
            if (isset($package['name'])) {
                $have[(string) $package['name']] = true;
            }
        }

        $missing = array_values(array_filter($packages, fn (string $p): bool => ! isset($have[$p])));

        if ($missing === []) {
            return null;
        }

        return implode(', ', $missing).' is not installed, so there is nothing to update. '
            .'Install it from the Extension Manager screen first. Nothing was started.';
    }

    /** See Plan\Nightly: the server works out what moves, and to which branch. */
    private function nightly(): int
    {
        $existing = $this->runs->latest();

        if ($existing !== null && ! $existing->isFinished()) {
            $this->error('An update is already in progress. Drive it with --resume, or abandon it on the Extension Manager screen. Nothing was started.');

            return 1;
        }

        $targets = (new Nightly($this->paths->base.'/composer.json', $this->paths->base.'/composer.lock'))->targets();

        if ($targets === []) {
            $this->error('There is no nightly build to move to: Packagist has no development branch for this Flarum, or could not be reached. Nothing was started.');

            return 1;
        }

        $id = 'r'.date('Ymd-His').'-'.bin2hex(random_bytes(3));
        (new WorkDir($this->paths->storage, $id))->create()->remember(array_keys($targets), 'update', $targets);

        $this->info('Moving '.count($targets).' Flarum package(s) to '.reset($targets).': '.implode(', ', array_keys($targets)));

        return $this->pump($this->runner->begin($id));
    }

    /** An update whose plan was worked out and came to nothing. */
    private function movedNothing(Run $run): bool
    {
        $plan = (new WorkDir($this->paths->storage, $run->id))->root().'/plan.json';

        return is_file($plan) && (((array) json_decode((string) file_get_contents($plan), true))['changes'] ?? null) === [];
    }

    /**
     * Ask Packagist and the site's private repositories again, as
     * extension-manager:check --force does. Overridable so the tests need no network.
     */
    protected function recheck(): void
    {
        $this->check()->refreshSite($this->paths->base);
    }

    private function check(): UpdateCheck
    {
        return new UpdateCheck($this->storagePath('updates.json'));
    }

    private function storagePath(string $file): string
    {
        return StorageDir::of($this->paths->storage).'/'.$file;
    }

    /**
     * Overridable so the tests can drive a run without waiting on a clock.
     */
    protected function pause(): void
    {
        usleep(500_000);
    }
}
