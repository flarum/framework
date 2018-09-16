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

use Flarum\Discussion\Discussion;
use Flarum\Event\ConfigureModelDefaultAttributes;
use Flarum\Event\GetModelIsPrivate;
use Flarum\Flags\Flag;
use Flarum\Post\Event\Saving;
use Flarum\Post\Post;
use Illuminate\Contracts\Events\Dispatcher;

class UnapproveNewContent
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureModelDefaultAttributes::class, [$this, 'approveByDefault']);
        $events->listen(Saving::class, [$this, 'unapproveNewPosts']);
        $events->listen(GetModelIsPrivate::class, [$this, 'markUnapprovedContentAsPrivate']);
    }

    /**
     * @param ConfigureModelDefaultAttributes $event
     */
    public function approveByDefault(ConfigureModelDefaultAttributes $event)
    {
        if ($event->isModel(Post::class) || $event->isModel(Discussion::class)) {
            $event->attributes['is_approved'] = true;
        }
    }

    /**
     * @param Saving $event
     */
    public function unapproveNewPosts(Saving $event)
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
     * @param GetModelIsPrivate $event
     * @return bool|null
     */
    public function markUnapprovedContentAsPrivate(GetModelIsPrivate $event)
    {
        if ($event->model instanceof Post || $event->model instanceof Discussion) {
            if (! $event->model->is_approved) {
                return true;
            }
        }
    }
}
