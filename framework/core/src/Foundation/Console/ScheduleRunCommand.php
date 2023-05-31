<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\Console;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;

class ScheduleRunCommand extends \Illuminate\Console\Scheduling\ScheduleRunCommand
{
    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
        parent::__construct();
    }

    public function handle(Schedule $schedule, Dispatcher $dispatcher, ExceptionHandler $handler): void
    {
        parent::handle($schedule, $dispatcher, $handler);

        $this->settings->set('schedule.last_run', $this->startedAt);
    }
}
