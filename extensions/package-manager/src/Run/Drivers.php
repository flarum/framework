<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Run;

use Flarum\ExtensionManager\Lang;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Illuminate\Queue\SyncQueue;

/**
 * Decides what, if anything, should be turning the handle in the background.
 *
 * 🚨 The queue is used when there is one and ignored when there is not, and
 * neither case is special. Flarum ships with the sync queue, which runs a
 * "queued" job inline in the request that dispatched it — so dispatching there
 * would produce exactly the long request this design exists to avoid, on the
 * hosts least able to survive one. So it is detected and skipped.
 *
 * Whatever is decided here, the admin page keeps polling. The queue makes an
 * update continue after the tab is closed; it is never what makes it work.
 */
class Drivers
{
    public function __construct(
        private QueueFactory $queue,
        private Dispatcher $bus,
    ) {
    }

    /**
     * True when a real worker exists to carry a run on in the background.
     */
    public function hasWorker(): bool
    {
        try {
            return ! ($this->unwrap($this->queue->connection()) instanceof SyncQueue);
        } catch (\Throwable) {
            // A misconfigured queue is not a reason to refuse to update. Fall
            // back to the driver that always works.
            return false;
        }
    }

    /**
     * 🚨 Flarum wraps the real queue in RoutingQueue, so an `instanceof
     * SyncQueue` check on what the container hands back is ALWAYS false — even
     * on a stock install where the thing inside is the sync queue.
     *
     * Caught by running this against a real forum rather than by reading the
     * code: the check reported "a worker is available" on an install that had
     * none, which would have dispatched a job that ran inline in the request —
     * precisely the long request this design exists to avoid, on precisely the
     * hosts least able to survive one.
     */
    private function unwrap(mixed $queue): mixed
    {
        // getDriver() is public on RoutingQueue; anything else is already the
        // driver, and anything unfamiliar is left exactly as it came.
        if (is_object($queue) && method_exists($queue, 'getDriver')) {
            $inner = $queue->getDriver();

            return is_object($inner) ? $inner : $queue;
        }

        return $queue;
    }

    /**
     * Ask a worker to help, if one exists. Safe to call when none does.
     */
    public function nudge(string $runId): bool
    {
        if (! $this->hasWorker()) {
            return false;
        }

        try {
            $this->bus->dispatch(new StepJob($runId));

            return true;
        } catch (\Throwable) {
            /*
             * 🚨 Swallowed on purpose, and this is the one place that is right.
             * A queue that cannot be dispatched to is a reason to fall back to
             * polling, not a reason to fail the update — and the update is
             * already running, so the user is not left wondering. The alternative
             * is what the current tooling does: refuse the work entirely because
             * a background system nobody asked about is unhappy.
             */
            return false;
        }
    }

    /**
     * What to tell the admin about how this will proceed.
     */
    public function describe(): string
    {
        return $this->hasWorker()
            ? Lang::trans('run.worker_available')
            : Lang::trans('run.no_worker');
    }
}
