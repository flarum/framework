<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Approval\Listener;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Flags\Flag;
use Flarum\Post\CommentPost;
use Flarum\Post\Event\Saving;
use Illuminate\Contracts\Events\Dispatcher;

class UnapproveNewContent
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(Saving::class, [$this, 'unapproveNewPosts']);
    }

    /**
     * @param Saving $event
     */
    public function unapproveNewPosts(Saving $event)
    {
        $post = $event->post;

        if (! $post->exists) {
            $ability = $post->discussion->first_post_id === null ? 'startWithoutApproval' : 'replyWithoutApproval';

            if ($event->actor->can($ability, $post->discussion)) {
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
                $flag->created_at = Carbon::now();

                $flag->save();
            });
        }
    }

    /**
     * @param Discussion|CommentPost $instance
     * @return bool|null
     */
    public static function markUnapprovedContentAsPrivate($instance)
    {
        if (! $instance->is_approved) {
            return true;
        }
    }
}
