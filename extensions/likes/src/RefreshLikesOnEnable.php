<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes;

use Flarum\Extend\ExtenderInterface;
use Flarum\Extend\LifecycleInterface;
use Flarum\Extension\Extension;
use Flarum\Likes\Jobs\RefreshLikeCounts;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\Queue;

class RefreshLikesOnEnable implements ExtenderInterface, LifecycleInterface
{
    /**
     * Set by the migration that backfills the counts, which runs just before
     * this extension's first enable.
     */
    public static bool $backfilled = false;

    public function onEnable(Container $container, Extension $extension): void
    {
        // The counts can be stale after likes were deleted while the extension
        // was disabled, so repair them on every enable except the first, where
        // the migration has just counted them. The repair is queued: on the
        // sync queue it still runs within this request, but with a worker it
        // leaves the request.
        if (self::$backfilled) {
            self::$backfilled = false;

            return;
        }

        $this->queue($container)->push(new RefreshLikeCounts());
    }

    private function queue(Container $container): Queue
    {
        return $container->make(Queue::class);
    }

    public function onDisable(Container $container, Extension $extension): void
    {
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
    }
}
