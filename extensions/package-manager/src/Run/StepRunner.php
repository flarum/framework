<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Run;

use Flarum\ExtensionManager\Lang;
use Throwable;

/**
 * Advances a run by exactly one unit of work.
 *
 * 🚨 This is the answer to the constraint that actually matters. Memory was
 * never the thing stopping updates on shared hosting — `max_execution_time` was,
 * and every other limit that punishes one long operation. So nothing here loops:
 * a call does one item and returns. Progress becomes a function of how many
 * times something calls step(), and it stops mattering whether that something is
 * the admin screen polling, a cron tick, or a queue worker.
 *
 * A host with a thirty second ceiling can therefore complete an update that
 * takes ten minutes, which no amount of optimisation would have achieved.
 *
 * Two rules it never breaks:
 *
 *   - **It always says what it did.** Calling step() on a finished run returns
 *     that run unchanged rather than doing nothing quietly. Silence is what makes
 *     a stuck update indistinguishable from a working one.
 *   - **It never skips work because something looks busy.** A stale run is
 *     reported to the caller with its age; it is not a reason to refuse. That
 *     refusal, done silently, is why one dead the previous Extension Manager task blocks every
 *     later update forever.
 */
class StepRunner
{
    /**
     * True when the last call to step() did nothing because another driver was
     * already working on the run. Not an error — the caller should report it and
     * carry on polling.
     */
    private bool $busy = false;

    /**
     * True when the last call to step() left the run on the same item on
     * purpose — the work is done but its effect is not live yet. The caller
     * should keep polling, and should not treat the pause as progress.
     */
    private bool $waiting = false;

    /**
     * True when the wait can only be ended by a web request — see
     * NeedsWebRequest. A queue worker or terminal should stop asking.
     */
    private bool $needsWeb = false;

    /**
     * 🚨 A factory is preferred over a fixed Steps, so the run id is an argument
     * rather than something the container decided once and remembered. Inside a
     * queue worker — a long-lived process — a remembered one carries the
     * previous run's staging directory and journal into the next run. A bare
     * Steps is still accepted, because tests supply deterministic work with no
     * run of its own.
     *
     * @param callable():int $clock
     */
    public function __construct(
        private RunStore $store,
        private Steps|StepsFactory $steps,
        private $clock,
        private ?string $lockDir = null,
    ) {
    }

    private function stepsFor(string $runId): Steps
    {
        return $this->steps instanceof StepsFactory
            ? $this->steps->for($runId)
            : $this->steps;
    }

    public function wasBusy(): bool
    {
        return $this->busy;
    }

    public function wasWaiting(): bool
    {
        return $this->waiting;
    }

    public function needsWebRequest(): bool
    {
        return $this->needsWeb;
    }

    public function begin(string $id): Run
    {
        $run = Run::start($id, $this->now());
        $this->store->save($run);

        return $run;
    }

    /**
     * One unit of work. Safe to call as often as you like, from anywhere.
     */
    public function step(string $id): Run
    {
        $this->busy = false;
        $this->waiting = false;
        $this->needsWeb = false;

        $run = $this->store->load($id);

        if ($run === null) {
            throw new \RuntimeException("No run called $id");
        }

        if ($run->isFinished()) {
            return $run;
        }

        /*
         * 🚨 One driver at a time.
         *
         * The whole point of the design is that anything can turn the handle —
         * the admin page polling, a cron tick, a queue worker — and on a forum
         * that has a queue, two of them WILL run at once. Without this, both
         * read the same index, both do the same item, and a package gets applied
         * twice.
         *
         * Non-blocking on purpose. A second driver arriving mid-step should say
         * "someone else has it" and let the caller poll again, not queue up
         * behind a lock and pile requests on a host that is already busy.
         */
        $lock = $this->acquire($id);

        if ($lock === null) {
            $this->busy = true;

            return $run;
        }

        try {
            return $this->work($run);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /** @return resource|null */
    private function acquire(string $id)
    {
        $dir = $this->lockDir;

        if ($dir === null) {
            return fopen('php://memory', 'r+') ?: null;   // no lock configured
        }

        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            return fopen('php://memory', 'r+') ?: null;
        }

        $handle = fopen($dir.'/'.preg_replace('/[^A-Za-z0-9_-]/', '', $id).'.lock', 'c');

        if ($handle === false) {
            return null;
        }

        if (! flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);

            return null;
        }

        return $handle;
    }

    private function work(Run $run): Run
    {
        $id = $run->id;

        /*
         * Re-read inside the lock. Between loading it above and taking the lock,
         * another driver may have finished a step — carrying on from the stale
         * copy would repeat that item.
         */
        $run = $this->store->load($id) ?? $run;

        if ($run->isFinished()) {
            return $run;
        }

        $phase = $run->phase;
        $item = null;
        $steps = $this->stepsFor($id);

        try {
            /*
             * Deciding what a phase consists of is itself a unit of work — on a
             * constrained host, planning IS the expensive step, so it must be
             * allowed a whole request of its own rather than being squeezed in
             * before the first item.
             */
            if ($run->total() === 0 && $run->index === 0) {
                $items = $steps->itemsFor($phase, $run);

                if ($items === []) {
                    return $this->leavePhase($run);
                }

                $run = $run->withItems($items, $this->now());
                $this->store->save($run);

                return $run;
            }

            $item = $run->current();

            if ($item === null) {
                return $this->leavePhase($run);
            }

            try {
                $note = $steps->doItem($phase, $item, $run);
            } catch (Reverted $reverted) {
                /*
                 * The step has already put the tree back; all that is left is
                 * to record it as such. Deliberately NOT the ordinary failure
                 * path, which would leave the screen offering a rollback of a
                 * tree that is already correct.
                 */
                $run = $run->revertedAfter(
                    $reverted->getMessage(),
                    "$phase → $item",
                    $reverted->undone,
                    $this->now()
                );
                $this->store->save($run);

                return $run;
            } catch (NotYet $waiting) {
                /*
                 * 🚨 Not done, not failed — and the index does NOT move.
                 *
                 * A step that has done everything it can but whose effect is
                 * not live yet used to have no way to say so, so it said "done"
                 * and put the caveat in the log. The run then went green while
                 * the site was still serving code that did not match its own
                 * database. Every driver calls step() again on its own, so
                 * staying on the item costs nothing.
                 */
                $this->waiting = true;
                $this->needsWeb = $waiting instanceof NeedsWebRequest;
                $run = $run->waiting($this->now(), $waiting->getMessage());
                $this->store->save($run);

                return $run;
            }

            /*
             * 🚨 Saved AFTER the work, which means a process killed in between
             * repeats this item on resume. That is deliberate and it is why
             * doItem must be idempotent: repeating work is recoverable, skipping
             * it silently is not.
             */
            $run = $run->advanced($this->now(), $note);
            $this->store->save($run);

            if ($run->index >= $run->total()) {
                return $this->leavePhase($run);
            }

            return $run;
        } catch (Throwable $e) {
            $where = $item === null ? "$phase (planning)" : "$phase → $item";
            $run = $run->failed($e->getMessage(), $where, $this->now());
            $this->store->save($run);

            return $run;
        }
    }

    /** Move to the next phase, or finish if that was the last one. */
    private function leavePhase(Run $run): Run
    {
        $order = Run::PHASES;
        $at = array_search($run->phase, $order, true);
        $next = $at === false ? null : ($order[$at + 1] ?? null);

        $run = $next === null
            ? $run->finished($this->now())
            : $run->enteredPhase($next, $this->now(), Lang::trans('run.finished_phase', ['phase' => $run->phase]));

        $this->store->save($run);

        return $run;
    }

    private function now(): int
    {
        return ($this->clock)();
    }
}
