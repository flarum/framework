<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Approval\Listener;

use Flarum\Event\PostWillBeSaved;
use Flarum\Flags\Flag;
use Illuminate\Contracts\Events\Dispatcher;

class UnapproveNewContent
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(PostWillBeSaved::class, [$this, 'unapproveNewPosts']);
    }

    /**
     * @param PostWillBeSaved $event
     */
    public function unapproveNewPosts(PostWillBeSaved $event)
    {
        $post = $event->post;

        if (! $post->exists) {
            if ($event->actor->can('replyWithoutApproval', $post->discussion)) {
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
