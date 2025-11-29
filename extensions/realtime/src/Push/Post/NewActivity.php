<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Push\Post;

use Flarum\Flags\Event\Created as Flagged;
use Flarum\Flags\Event\Deleting as FlagDismissed;
use Flarum\Likes\Event\PostWasLiked;
use Flarum\Likes\Event\PostWasUnliked;
use Flarum\Post\Event\Revised;
use Flarum\Realtime\Push\Jobs\SendFlaggedJob;
use Flarum\Realtime\Push\Jobs\SendTriggerJob;
use Flarum\Realtime\Push\Subscriber;
use FoF\Gamification\Events\PostWasVoted;
use FoF\Reactions\Event\PostWasReacted;
use FoF\Reactions\Event\PostWasUnreacted;
use Illuminate\Contracts\Events\Dispatcher;

class NewActivity extends Subscriber
{
    public function subscribe(Dispatcher $events): void
    {
        $this->listen(Flagged::class, [$this, 'flagged']);
        $this->listen(FlagDismissed::class, [$this, 'flagged']);

        $this->listen(PostWasLiked::class, [$this, 'likes']);
        $this->listen(PostWasUnliked::class, [$this, 'likes']);

        if (class_exists(PostWasVoted::class)) {
            /** @phpstan-ignore-next-line */
            $this->listen(PostWasVoted::class, [$this, 'voted']);
        }

        if (class_exists(PostWasReacted::class) && class_exists(PostWasUnreacted::class)) {
            /** @phpstan-ignore-next-line */
            $this->listen(PostWasReacted::class, [$this, 'reactions']);
            /** @phpstan-ignore-next-line */
            $this->listen(PostWasUnreacted::class, [$this, 'reactions']);
        }

        $this->listen(Revised::class, [$this, 'revised']);
    }

    /**
     * @param Flagged|FlagDismissed $event
     */
    public function flagged(object $event): void
    {
        $discussion = $event->flag->post->discussion;

        // We manually delete the flag here, because:
        // in the parent logic the flag is deleted using the eloquent relationship, which triggers
        // no deleted callback on the AbstractModel
        if ($event instanceof FlagDismissed) {
            $event->flag->delete();
        }

        $this->queue()->push(new SendFlaggedJob($discussion));
    }

    /**
     * @param PostWasUnliked|PostWasLiked $event
     */
    public function likes(object $event): void
    {
        $this->queue()->push(new SendTriggerJob(
            'likesMutation',
            $event->post,
            $event->user
        ));
    }

    /**
     * @param PostWasVoted $event
     * @return void
     * @phpstan-ignore-next-line
     */
    public function voted(object $event): void
    {
        /** @phpstan-ignore-next-line */
        $post = $event->vote->post;

        $this->queue()->push(new SendTriggerJob(
            'votedMutation',
            $post
        ));
    }

    /**
     * @param PostWasReacted|PostWasUnreacted $event
     * @phpstan-ignore-next-line
     */
    public function reactions(object $event): void
    {
        /** @phpstan-ignore-next-line */
        $post = $event->post;
        /** @phpstan-ignore-next-line */
        $user = $event->user;

        $this->queue()->push(new SendTriggerJob(
            'reactionsMutation',
            $post,
            $user
        ));
    }

    public function revised(Revised $event): void
    {
        $this->queue()->push(new SendTriggerJob(
            'revisedEvent',
            $event->post,
            $event->actor
        ));
    }
}
