<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Run;

use Flarum\ExtensionManager\Lang;
use Flarum\Queue\AbstractJob;
use Illuminate\Contracts\Bus\Dispatcher;

/**
 * Turns the handle from a queue worker, where a forum has one.
 *
 * 🚨 An OPTION, never the path. The previous Extension Manager's central failure is that the
 * queue is the only way work happens, so when a job dies the update dies with
 * it, the task row is left saying `running`, and every later update is silently
 * refused. Here the queue is one of three interchangeable drivers — the admin
 * page polling and a cron tick are the others — and the run is complete state on
 * disk that any of them can pick up. Kill this job and an admin refreshing the
 * page finishes the update.
 *
 * 🚨 It re-dispatches itself rather than looping to the end. A worker timeout is
 * as real a limit as a web request's, and the whole design is built on never
 * needing a long one. Each job does a few seconds of work and hands on.
 */
class StepJob extends AbstractJob
{
    /**
     * How long one job keeps taking turns before handing on to a fresh one.
     *
     * 🚨 This is a budget, not a limit: it is checked BETWEEN turns, so a
     * single long turn overruns it by however long that turn takes. Planning is
     * the one that does — see $timeout below.
     */
    private const BUDGET_SECONDS = 20;

    /**
     * 🚨 One try, on purpose.
     *
     * Retrying is what turns one dead job into "has been attempted too many
     * times" and a wedged queue. There is nothing to retry anyway: the run's
     * state is on disk, so the correct recovery is for any driver to call step()
     * again — which the admin page does every couple of seconds regardless.
     */
    public int $tries = 1;

    /**
     * 🚨 Long enough for the one step that genuinely takes minutes.
     *
     * The budget above keeps each TURN of the loop short, and the comment
     * beside it says nothing here needs to be near a worker timeout. That was
     * wrong about exactly one thing: planning. Working out what changes runs a
     * composer resolution, StepRunner deliberately gives it a whole turn of its
     * own, and on a small host that single call can run for minutes. The budget
     * is only checked BETWEEN turns, so the worker's own 60-second alarm fired
     * in the middle of it and killed the run before it had touched anything.
     *
     * A job-level timeout beats the worker's default, so this raises the alarm
     * for this job alone rather than making every other queue on the forum wait
     * fifteen minutes to notice a wedged job.
     */
    public int $timeout = 900;

    public function __construct(private string $runId)
    {
        parent::__construct();
    }

    public function handle(StepRunner $runner, RunStore $store, Dispatcher $bus): void
    {
        $until = time() + self::BUDGET_SECONDS;

        do {
            $run = $runner->step($this->runId);

            /*
             * Finished — or waiting on something only the admin page can do
             * on this host (see NeedsWebRequest). Re-dispatching would spin
             * the queue until somebody opened the page; the page carries on.
             */
            if ($run->isFinished() || $runner->needsWebRequest()) {
                return;
            }

            /*
             * 🚨 Another driver has the lock — almost always the admin page,
             * which polls from the moment the run starts. This used to RETURN,
             * and nothing ever dispatched another job: the page won the first
             * race, the worker bowed out in 3ms, and the run was driven by the
             * browser alone. Close the tab and the update stopped dead halfway,
             * on a screen that had just promised it "will carry on even if you
             * close this page". Proved on the demo: stuck in apply for minutes
             * with the worker idle.
             *
             * So it waits its turn instead. The lock already makes two drivers
             * safe; the cost is one worker sleeping between turns, which is the
             * job it was dispatched to do.
             *
             * 🚨 A waiting step is waiting on the CLOCK, not on us. Calling it
             * again immediately would spin the loop as fast as the filesystem
             * answers for the whole budget, on a host that has just been made
             * to run a Composer install.
             */
            if ($runner->wasBusy() || $runner->wasWaiting()) {
                sleep(2);
            }
        } while (time() < $until);

        // Out of budget with work left. Hand on to a fresh job rather than
        // running past a worker timeout and being killed mid-item.
        $bus->dispatch(new self($this->runId));
    }

    /**
     * 🚨 A failed job must not leave the run looking alive.
     *
     * This is the exact hole in the tooling being replaced: the job dies, the
     * task row still says `running`, and the dispatcher then refuses every later
     * update because something looks busy. Recording the failure means the admin
     * screen can show it and offer a rollback.
     */
    public function failed(\Throwable $e): void
    {
        $store = resolve(RunStore::class);
        $run = $store->load($this->runId);

        if ($run !== null && ! $run->isFinished()) {
            $store->save($run->failed(
                Lang::trans('run.worker_stopped', ['error' => $e->getMessage()]),
                Lang::trans('run.queue_worker'),
                time()
            ));
        }
    }
}
