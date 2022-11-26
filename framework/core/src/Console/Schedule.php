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
    public function dueEvents($container)
    {
        return (new Collection($this->events))->filter->isDue(new class($container) {
            /** @var Config */
            protected $config;

            public function __construct($container)
            {
                $this->config = $container->make(Config::class);
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
