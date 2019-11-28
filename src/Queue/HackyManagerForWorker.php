<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Queue;

use Illuminate\Contracts\Queue\Factory;
use Illuminate\Queue\QueueManager;

/**
 * A hacky workaround to avoid injecting an entire QueueManager (which we don't
 * want to build) into Laravel's queue worker class.
 *
 * Laravel 6.0 will clean this up; once we upgrade, we can remove this hack and
 * directly inject the factory.
 */
class HackyManagerForWorker extends QueueManager implements Factory
{
    /**
     * @var Factory
     */
    private $factory;

    /**
     * HackyManagerForWorker constructor.
     *
     * Needs a real connection factory to delegate to.
     *
     * @param Factory $factory
     */
    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Resolve a queue connection instance.
     *
     * @param string $name
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connection($name = null)
    {
        return $this->factory->connection($name);
    }

    /**
     * Determine if the application is in maintenance mode.
     *
     * @return bool
     */
    public function isDownForMaintenance()
    {
        return false;
    }
}
