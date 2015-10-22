<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Listener;

use DateTime;
use Flarum\Core\Exception\FloodingException;
use Flarum\Core\Post;
use Flarum\Core\User;
use Flarum\Event\DiscussionWillBeSaved;
use Flarum\Event\PostWillBeSaved;
use Illuminate\Contracts\Events\Dispatcher;

class FloodController
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(DiscussionWillBeSaved::class, [$this, 'whenDiscussionWillBeSaved']);
        $events->listen(PostWillBeSaved::class, [$this, 'whenPostWillBeSaved']);
    }

    /**
     * @param DiscussionWillBeSaved $event
     */
    public function whenDiscussionWillBeSaved(DiscussionWillBeSaved $event)
    {
        if ($event->discussion->exists) {
            return;
        }

        $this->assertNotFlooding($event->actor);
    }

    /**
     * @param PostWillBeSaved $event
     */
    public function whenPostWillBeSaved(PostWillBeSaved $event)
    {
        if ($event->post->exists) {
            return;
        }

        $this->assertNotFlooding($event->actor);
    }

    /**
     * @param User $actor
     * @throws FloodingException
     */
    protected function assertNotFlooding(User $actor)
    {
        if ($this->isFlooding($actor)) {
            throw new FloodingException;
        }
    }

    /**
     * @param User $actor
     * @return bool
     */
    protected function isFlooding(User $actor)
    {
        return Post::where('user_id', $actor->id)->where('time', '>=', new DateTime('-10 seconds'))->exists();
    }
}
