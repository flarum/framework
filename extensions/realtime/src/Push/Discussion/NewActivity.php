<?php

namespace Flarum\Realtime\Push\Discussion;

use Flarum\Realtime\Push\Jobs\SendTriggerJob;
use Flarum\Realtime\Push\Subscriber;
use Flarum\Discussion\Event\Renamed;
use Flarum\Discussion\Event\Started;
use Flarum\Lock\Event as Lock;
use Flarum\Post\Event\Posted;
use FoF\BestAnswer\Events as BestAnswer;
use Illuminate\Contracts\Events\Dispatcher;

class NewActivity extends Subscriber
{
    public function subscribe(Dispatcher $events)
    {
        // Created and Posted both are lined up for dispatching
        // after saving by Discussion::create and CommentPost::reply
        // and the `raise()` function.
        $this->listen(Started::class, [$this, 'started']);
        $this->listen(Posted::class, [$this, 'replied']);

        // Best answer setting or unsetting its best answer.
        $this->listen([BestAnswer\BestAnswerSet::class, BestAnswer\BestAnswerUnset::class], [$this, 'bestAnswer']);

        // Locking or unlocking a discussion via `flarum/lock`.
        $this->listen([Lock\DiscussionWasLocked::class, Lock\DiscussionWasUnlocked::class], [$this, 'locked']);

        $this->listen(Renamed::class, [$this, 'renamed']);
    }

    /**
     * @param Started $event
     * @return void
     */
    public function started(Started $event)
    {
        $this->queue()->push(new SendTriggerJob(
            get_class($event),
            $event->discussion,
            $event->actor
        ));
    }

    public function replied(Posted $event)
    {
        // Prevent sending for the OP, because the Created event
        // was also fired.
        if ($event->post->number === 1) return;

        $this->queue()->push(new SendTriggerJob(
            get_class($event),
            $event->post,
            $event->actor
        ));
    }

    public function bestAnswer($event)
    {
        $this->queue()->push(new SendTriggerJob(
            'bestAnswerMutation',
            $event->discussion,
            $event->actor
        ));
    }

    /**
     * @param Lock\DiscussionWasLocked|Lock\DiscussionWasUnlocked $event
     * @return void
     */
    public function locked($event)
    {
        $this->queue()->push(new SendTriggerJob(
            'lockedEvent',
            $event->discussion,
            $event->user
        ));
    }

    public function renamed(Renamed $event)
    {
        $this->queue()->push(new SendTriggerJob(
            'discussionRenamed',
            $event->discussion,
            $event->actor
        ));
    }
}
