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
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * {@inheritdoc}
     */
    public function __construct(SettingsRepositoryInterface $settings)
    {
        parent::__construct();

        $this->settings = $settings;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Schedule $schedule, Dispatcher $dispatcher, ExceptionHandler $handler)
    {
        parent::handle($schedule, $dispatcher, $handler);

        $this->settings->set('schedule.last_run', $this->startedAt);
    }
}
