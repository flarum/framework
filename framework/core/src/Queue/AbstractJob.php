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
     * If a serialized model on this job has been deleted between dispatch and
     * worker pickup, treat the job as a no-op and remove it from the queue
     * rather than calling failed(), which re-deserializes the payload and
     * throws ModelNotFoundException a second time — producing duplicate error
     * log entries for what is in fact a handled-correctly race.
     *
     * Subclasses that should be retried or marked failed in this scenario can
     * override with `public bool $deleteWhenMissingModels = false;`.
     */
    public bool $deleteWhenMissingModels = true;
}
