<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post;

use DateTime;
use Flarum\Post\Event\CheckingForFlooding;
use Flarum\Post\Exception\FloodingException;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * @deprecated beta 14, removed beta 15 in favor of Floodgate middleware
 */
class Floodgate
{
    /**
     * @var Dispatcher
     */
    protected $events;

    public function __construct(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * @param User $actor
     * @throws FloodingException
     */
    public function assertNotFlooding(User $actor)
    {
        if ($actor->can('postWithoutThrottle')) {
            return;
        }

        if ($this->isFlooding($actor)) {
            throw new FloodingException;
        }
    }

    /**
     * @param User $actor
     * @return bool
     */
    public function isFlooding(User $actor): bool
    {
        $isFlooding = $this->events->until(
            new CheckingForFlooding($actor)
        );

        return $isFlooding ?? Post::where('user_id', $actor->id)->where('created_at', '>=', new DateTime('-10 seconds'))->exists();
    }
}
