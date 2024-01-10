<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Job;

use Carbon\Carbon;
use Flarum\Bus\Dispatcher as Bus;
use Flarum\Extension\ExtensionManager;
use Flarum\ExtensionManager\Command\AbstractActionCommand;
use Flarum\ExtensionManager\Task\Task;
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
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * Overrides the user setting for execution mode if set.
     * Runs synchronously regardless of user setting if set true.
     * Asynchronously if set false.
     *
     * @var bool|null
     */
    protected $runSyncOverride;

    public function __construct(Bus $bus, Queue $queue, SettingsRepositoryInterface $settings, ExtensionManager $extensions)
    {
        $this->bus = $bus;
        $this->queue = $queue;
        $this->settings = $settings;
        $this->extensions = $extensions;
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

    public function dispatch(AbstractActionCommand $command): DispatcherResponse
    {
        $queueJobs = ($this->runSyncOverride === false) || ($this->runSyncOverride !== true && $this->settings->get('flarum-extension-manager.queue_jobs'));

        // Skip if there is already a pending or running task.
        if ($queueJobs && Task::query()->whereIn('status', [Task::PENDING, Task::RUNNING])->exists()) {
            return new DispatcherResponse(true, null);
        }

        if ($queueJobs && (! $this->queue instanceof SyncQueue)) {
            $extension = $command->extensionId ? $this->extensions->getExtension($command->extensionId) : null;

            $task = Task::build($command->getOperationName(), $command->package ?? ($extension ? $extension->name : null));

            $command->task = $task;

            $this->queue->push(
                new ComposerCommandJob($command, PHP_VERSION)
            );
        } else {
            $data = $this->bus->dispatch($command);
        }

        $this->clearOldTasks();

        return new DispatcherResponse($queueJobs, $data ?? null);
    }

    protected function clearOldTasks(): void
    {
        $days = $this->settings->get('flarum-extension-manager.task_retention_days');

        if ($days === null || ((int) $days) === 0) {
            return;
        }

        Task::query()
            ->where('created_at', '<', Carbon::now()->subDays($days))
            ->delete();
    }
}
