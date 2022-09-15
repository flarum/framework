<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Job;

use Flarum\Bus\Dispatcher as Bus;
use Flarum\PackageManager\Command\BusinessCommandInterface;
use Flarum\PackageManager\Task\Task;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\SyncQueue;

class Dispatcher
{
    /**
     * @var Bus
     */
    protected $bus;

    /**
     * @var Queue
     */
    protected $queue;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * Overrides the user setting for execution mode if set.
     * Runs synchronously regardless of user setting if set true.
     * Asynchronously if set false.
     *
     * @var bool|null
     */
    protected $runSyncOverride;

    public function __construct(Bus $bus, Queue $queue, SettingsRepositoryInterface $settings)
    {
        $this->bus = $bus;
        $this->queue = $queue;
        $this->settings = $settings;
    }

    public function sync(): self
    {
        $this->runSyncOverride = true;

        return $this;
    }

    public function async(): self
    {
        $this->runSyncOverride = false;

        return $this;
    }

    public function dispatch(BusinessCommandInterface $command): DispatcherResponse
    {
        $queueJobs = ($this->runSyncOverride === false) || ($this->runSyncOverride !== true && $this->settings->get('flarum-package-manager.queue_jobs'));

        if ($queueJobs && (! $this->queue instanceof SyncQueue)) {
            $task = Task::build($command->getOperationName(), $command->package ?? null);

            $command->task = $task;

            $this->queue->push(
                new ComposerCommandJob($command, PHP_VERSION)
            );
        } else {
            $data = $this->bus->dispatch($command);
        }

        return new DispatcherResponse($queueJobs, $data ?? null);
    }
}
