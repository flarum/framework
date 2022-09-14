<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Queue;

use Illuminate\Contracts\Queue\Factory;

class QueueFactory implements Factory
{
    /**
     * @var callable
     */
    private $factory;

    /**
     * The cached queue instance.
     *
     * @var \Illuminate\Contracts\Queue\Queue|null
     */
    private $queue;

    /**
     * QueueFactory constructor.
     *
     * Expects a callback that will be called to instantiate the queue adapter,
     * once requested by the application.
     *
     * @param callable $factory
     */
    public function __construct(callable $factory)
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
        if (is_null($this->queue)) {
            $this->queue = ($this->factory)();
        }

        return $this->queue;
    }
}
