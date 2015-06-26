<?php namespace Flarum\Subscriptions\Handlers;

use Flarum\Core\Events\DiscussionWillBeSaved;

class SubscriptionSaver
{
    public function subscribe($events)
    {
        $events->listen('Flarum\Core\Events\DiscussionWillBeSaved', __CLASS__.'@whenDiscussionWillBeSaved');
    }

    public function whenDiscussionWillBeSaved(DiscussionWillBeSaved $event)
    {
        $discussion = $event->discussion;
        $data = $event->command->data;

        if (isset($data['subscription'])) {
            $user = $event->command->user;
            $subscription = $data['subscription'];

            $state = $discussion->stateFor($user);

            if (! in_array($subscription, ['follow', 'ignore'])) {
                $subscription = null;
            }

            $state->subscription = $subscription;
            $state->save();
        }
    }
}
