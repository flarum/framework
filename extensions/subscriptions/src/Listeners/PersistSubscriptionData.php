<?php namespace Flarum\Subscriptions\Listeners;

use Flarum\Events\DiscussionWillBeSaved;

class PersistSubscriptionData
{
    public function subscribe($events)
    {
        $events->listen(DiscussionWillBeSaved::class, __CLASS__.'@whenDiscussionWillBeSaved');
    }

    public function whenDiscussionWillBeSaved(DiscussionWillBeSaved $event)
    {
        $discussion = $event->discussion;
        $data = $event->data;

        if (isset($data['attributes']['subscription'])) {
            $actor = $event->actor;
            $subscription = $data['attributes']['subscription'];

            $state = $discussion->stateFor($actor);

            if (! in_array($subscription, ['follow', 'ignore'])) {
                $subscription = null;
            }

            $state->subscription = $subscription;
            $state->save();
        }
    }
}
