<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Carbon\Carbon;
use Flarum\Locale\Translator;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\SessionManager;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use SessionHandlerInterface;

class ApplicationInfoProvider
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected Translator $translator,
        protected Schedule $schedule,
        protected ConnectionInterface $db,
        protected Config $config,
        protected SessionManager $session,
        protected SessionHandlerInterface $sessionHandler,
        protected Queue $queue
    ) {
    }

    public function scheduledTasksRegistered(): bool
    {
        return count($this->schedule->events()) > 0;
    }

    public function getSchedulerStatus(): string
    {
        $status = $this->settings->get('schedule.last_run');

        if (! $status) {
            return $this->translator->trans('core.admin.dashboard.status.scheduler.never-run');
        }

        // If the schedule has not run in the last 5 minutes, mark it as inactive.
        return Carbon::parse($status) > Carbon::now()->subMinutes(5)
            ? $this->translator->trans('core.admin.dashboard.status.scheduler.active')
            : $this->translator->trans('core.admin.dashboard.status.scheduler.inactive');
    }

    public function identifyQueueDriver(): string
    {
        // Get class name
        $queue = $this->queue::class;
        // Drop the namespace
        $queue = Str::afterLast($queue, '\\');
        // Lowercase the class name
        $queue = strtolower($queue);
        // Drop everything like queue SyncQueue, RedisQueue
        $queue = str_replace('queue', '', $queue);

        return $queue;
    }

    public function identifyDatabaseVersion(): string
    {
        return match ($this->config['database.driver']) {
            'mysql' => $this->db->selectOne('select version() as version')->version,
            'sqlite' => $this->db->selectOne('select sqlite_version() as version')->version,
            default => 'Unknown',
        };
    }

    public function identifyDatabaseDriver(): string
    {
        return match ($this->config['database.driver']) {
            'mysql' => 'MySQL',
            'sqlite' => 'SQLite',
            default => $this->config['database.driver'],
        };
    }

    /**
     * Reports on the session driver in use based on three scenarios:
     *  1. If the configured session driver is valid and in use, it will be returned.
     *  2. If the configured session driver is invalid, fallback to the default one and mention it.
     *  3. If the actual used driver (i.e `session.handler`) is different from the current one (configured or default), mention it.
     */
    public function identifySessionDriver(bool $forWeb = false): string
    {
        /*
         * Get the configured driver and fallback to the default one.
         */
        $defaultDriver = $this->session->getDefaultDriver();
        $configuredDriver = Arr::get($this->config, 'session.driver', $defaultDriver);
        $driver = $configuredDriver;

        try {
            // Try to get the configured driver instance.
            // Driver instances are created on demand.
            $this->session->driver($configuredDriver);
        } catch (InvalidArgumentException) {
            // An exception is thrown if the configured driver is not a valid driver.
            // So we fallback to the default driver.
            $driver = $defaultDriver;
        }

        /*
         * Get actual driver name from its class name.
         * And compare that to the current configured driver.
         */
        // Get class name
        $handlerName = $this->sessionHandler::class;
        // Drop the namespace
        $handlerName = Str::afterLast($handlerName, '\\');
        // Lowercase the class name
        $handlerName = strtolower($handlerName);
        // Drop everything like sessionhandler FileSessionHandler, DatabaseSessionHandler ..etc
        $handlerName = str_replace('sessionhandler', '', $handlerName);

        if ($driver !== $handlerName) {
            return $forWeb ? $handlerName : "$handlerName <comment>(Code override. Configured to <options=bold,underscore>$configuredDriver</>)</comment>";
        }

        if ($driver !== $configuredDriver) {
            return $forWeb ? $driver : "$driver <comment>(Fallback default driver. Configured to invalid driver <options=bold,underscore>$configuredDriver</>)</comment>";
        }

        return $driver;
    }

    public function identifyPHPVersion(): string
    {
        return PHP_VERSION;
    }
}
