<?php

namespace Flarum\Realtime\Push\Post;

use Flarum\Realtime\Push\Jobs\SendFlaggedJob;
use Flarum\Realtime\Push\Jobs\SendTriggerJob;
use Flarum\Realtime\Push\Subscriber;
use FoF\Gamification\Events\PostWasVoted;
use FoF\Reactions\Event\PostWasReacted;
use FoF\Reactions\Event\PostWasUnreacted;
use Flarum\Flags\Event\Created as Flagged;
use Flarum\Flags\Event\Deleting as FlagDismissed;
use Flarum\Likes\Event\PostWasLiked;
use Flarum\Likes\Event\PostWasUnliked;
use Flarum\Post\Event\Revised;
use Illuminate\Contracts\Events\Dispatcher;

class NewActivity extends Subscriber
{
    public function subscribe(Dispatcher $events)
    {
        $this->listen(Flagged::class, [$this, 'flagged']);
        $this->listen(FlagDismissed::class, [$this, 'flagged']);

        $this->listen(PostWasLiked::class, [$this, 'likes']);
        $this->listen(PostWasUnliked::class, [$this, 'likes']);

        $this->listen(PostWasVoted::class, [$this, 'voted']);

        $this->listen(PostWasReacted::class, [$this, 'reactions']);
        $this->listen(PostWasUnreacted::class, [$this, 'reactions']);

        $this->listen(Revised::class, [$this, 'revised']);
    }

    /**
     * @param Flagged|FlagDismissed $event
     */
    public function flagged($event)
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
    public function likes($event)
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
     */
    public function voted(PostWasVoted $event)
    {
        $this->queue()->push(new SendTriggerJob(
            'votedMutation',
            $event->vote->post
        ));
    }

    /**
     * @param PostWasReacted|PostWasUnreacted $event
     */
    public function reactions($event)
    {
        $this->queue()->push(new SendTriggerJob(
            'reactionsMutation',
            $event->post,
            $event->user
        ));
    }

    public function revised(Revised $event)
    {
        $this->queue()->push(new SendTriggerJob(
            'revisedEvent',
            $event->post,
            $event->actor
        ));
    }
}
