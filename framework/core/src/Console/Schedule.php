<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Console;

use Flarum\Foundation\Config;
use Illuminate\Console\Scheduling\Schedule as LaravelSchedule;
use Illuminate\Support\Collection;

class Schedule extends LaravelSchedule
{
    public function dueEvents($app)
    {
        return (new Collection($this->events))->filter->isDue(new class($app) {
            protected Config $config;

            public function __construct($app)
            {
                $this->config = $app->make(Config::class);
            }

            public function isDownForMaintenance(): bool
            {
                return $this->config->inMaintenanceMode();
            }

            public function environment(): string
            {
                return '';
            }
        });
    }
}
