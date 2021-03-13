<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Approval\Listener;

use Flarum\Discussion\Discussion;
use Flarum\Flags\Flag;
use Flarum\Post\CommentPost;
use Flarum\Post\Event\Saving;

class UnapproveNewContent
{
    /**
     * @param Saving $event
     */
    public static function unapproveNewPosts(Saving $event)
    {
        $post = $event->post;

        if (! $post->exists) {
            $ability = $post->discussion->post_number_index == 0 ? 'startWithoutApproval' : 'replyWithoutApproval';

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
                $flag->created_at = time();

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
