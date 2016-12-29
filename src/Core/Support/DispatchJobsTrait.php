<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Support;

use Flarum\Core\Queue\AbstractJob;
use Illuminate\Contracts\Bus\Dispatcher;

trait DispatchJobsTrait
{
    /**
     * @var Dispatcher
     */
    protected $queue;

    /**
     * @param AbstractJob $job
     * @return mixed
     */
    public function dispatch(AbstractJob $job)
    {
        return $this->queue->dispatch($job);
    }
}
