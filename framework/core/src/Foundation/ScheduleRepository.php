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
use Illuminate\Console\Scheduling\Schedule;

class ScheduleRepository
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var Schedule
     */
    protected $schedule;

    public function __construct(SettingsRepositoryInterface $settings, Translator $translator, Schedule $schedule)
    {
        $this->settings = $settings;
        $this->translator = $translator;
        $this->schedule = $schedule;
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
        return Carbon::parse($status) > Carbon::now()->subMinutes(5) ? $this->translator->trans('core.admin.dashboard.status.scheduler.active') : $this->translator->trans('core.admin.status.scheduler.inactive');
    }
}
