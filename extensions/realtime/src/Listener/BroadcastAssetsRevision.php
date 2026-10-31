<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Listener;

use Flarum\Frontend\Event\AssetsRecompiled;
use Flarum\Realtime\Push\Jobs\BroadcastAssetsRevisionJob;
use Illuminate\Contracts\Queue\Queue;

/**
 * When the forum's assets are recompiled in place (e.g. an extension is toggled),
 * push the new revision to connected clients so they can offer a reload — without
 * waiting for the user's next request.
 */
class BroadcastAssetsRevision
{
    public function __construct(
        protected Queue $queue
    ) {
    }

    public function handle(AssetsRecompiled $event): void
    {
        $this->queue->push(new BroadcastAssetsRevisionJob());
    }
}
