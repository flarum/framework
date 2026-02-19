<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;

class ScheduleRunCommand extends \Illuminate\Console\Scheduling\ScheduleRunCommand
{
    /**
     * @var CacheRepository
     */
    protected $cache;

    /**
     * {@inheritdoc}
     */
    public function __construct(CacheRepository $cache)
    {
        parent::__construct();

        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Schedule $schedule, Dispatcher $dispatcher, ExceptionHandler $handler)
    {
        parent::handle($schedule, $dispatcher, $handler);

        // Store in cache instead of persistent settings (1 hour TTL)
        $this->cache->put('schedule:last_run', $this->startedAt, 3600);
    }
}
