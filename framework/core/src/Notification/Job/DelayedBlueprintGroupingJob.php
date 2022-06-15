<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification\Job;

use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\Driver\NotificationDriverInterface;
use Flarum\Queue\AbstractJob;
use Flarum\User\User;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class DelayedBlueprintGroupingJob extends AbstractJob
{
    private BlueprintInterface $blueprint;
    private NotificationDriverInterface $driver;
    private User $user;

    public function __construct(BlueprintInterface $blueprint, User $user, NotificationDriverInterface $driver)
    {
        $this->blueprint = $blueprint;
        $this->driver = $driver;
        $this->user = $user;
    }

    public function handle()
    {
    }

    public function middleware()
    {
        return [
            (new WithoutOverlapping('delayed-blueprint-grouping:'.$this->user->id))->dontRelease()
        ];
    }
}
