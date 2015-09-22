<?php namespace Flarum\Approval\Listeners;

use Flarum\Events\PostWillBeSaved;
use Flarum\Flags\Flag;
use Illuminate\Contracts\Events\Dispatcher;

class UnapproveNewContent
{
    private $savingPost;

    public function subscribe(Dispatcher $events)
    {
        $events->listen(PostWillBeSaved::class, [$this, 'unapproveNewPosts']);
    }

    public function unapproveNewPosts(PostWillBeSaved $event)
    {
        $post = $event->post;

        if (! $post->exists) {
            if ($post->discussion->can($event->actor, 'replyWithoutApproval')) {
                if ($post->is_approved === null) {
                    $post->is_approved = true;
                }

                return;
            }

            $post->is_approved = false;

            $post->afterSave(function ($post) {
                if ($post->number == 1) {
                    $post->discussion->is_approved = false;
                    $post->discussion->save();
                }

                $flag = new Flag;

                $flag->post_id = $post->id;
                $flag->type = 'approval';
                $flag->time = time();

                $flag->save();
            });
        }
    }
}
