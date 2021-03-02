<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Queue\Console;

use Flarum\Foundation\Config;

class WorkCommand extends \Illuminate\Queue\Console\WorkCommand
{
    protected function downForMaintenance()
    {
        if ($this->option('force')) {
            return false;
        }

        /** @var Config $config */
        $config = $this->laravel->make(Config::class);

        return $config->inMaintenanceMode();
    }
}
