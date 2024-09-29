<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AbstractJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The name of the queue on which the job should be placed.
     *
     * This is only effective on jobs that extend `\Flarum\Queue\AbstractJob` and dispatched via Redis.
     *
     * @var string|null
     */
    public static $sendOnQueue = null;

    public function __construct()
    {
        if (static::$sendOnQueue) {
            $this->onQueue(static::$sendOnQueue);
        }
    }
}
