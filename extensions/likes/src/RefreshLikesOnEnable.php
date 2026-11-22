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
use Illuminate\Contracts\Container\Container;

class RefreshLikesOnEnable implements ExtenderInterface, LifecycleInterface
{
    public function onEnable(Container $container, Extension $extension): void
    {
        // The repair only writes stale counts and can run again, so it stays in
        // the request: the queue driver is sync unless a forum configures one,
        // and a queued job would wait for a worker that many forums never run.
        $container->make(LikeCount::class)->refreshAll();
    }

    public function onDisable(Container $container, Extension $extension): void
    {
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
    }
}
