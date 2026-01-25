<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Queue\Console;

use Carbon\Carbon;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Queue\Worker;

class WorkCommand extends \Illuminate\Queue\Console\WorkCommand
{
    public function __construct(Worker $worker, Cache $cache, protected SettingsRepositoryInterface $settings)
    {
        parent::__construct($worker, $cache);
    }

    public function handle()
    {
        $this->settings->set('database_queue.working', Carbon::now()->toIso8601String());

        try {
            return parent::handle();
        } catch (\Exception $e) {
            $this->settings->delete('database_queue.working');

            throw $e;
        }
    }
}
