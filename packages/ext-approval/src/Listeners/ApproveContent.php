<?php namespace Flarum\Approval\Listeners;

use Flarum\Events\PostWillBeSaved;
use Flarum\Approval\Events\PostWasApproved;
use Illuminate\Contracts\Events\Dispatcher;

class ApproveContent
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(PostWillBeSaved::class, [$this, 'approvePost']);
        $events->listen(PostWasApproved::class, [$this, 'approveDiscussion']);
    }

    public function approvePost(PostWillBeSaved $event)
    {
        $attributes = $event->data['attributes'];
        $post = $event->post;

        if (isset($attributes['isApproved'])) {
            $post->assertCan($event->actor, 'approve');

            $isApproved = (bool) $attributes['isApproved'];
        } elseif (! empty($attributes['isHidden']) && $post->can($event->actor, 'approve')) {
            $isApproved = true;
        }

        if (! empty($isApproved)) {
            $post->is_approved = true;

            $post->raise(new PostWasApproved($post));
        }
    }

    public function approveDiscussion(PostWasApproved $event)
    {
        $post = $event->post;

        $post->discussion->refreshCommentsCount();
        $post->discussion->refreshLastPost();

        if ($post->number == 1) {
            $post->discussion->is_approved = true;
            $post->discussion->save();
        }
    }
}
