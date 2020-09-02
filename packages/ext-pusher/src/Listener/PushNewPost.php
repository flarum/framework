<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Pusher\Listener;

use Flarum\Post\Event\Posted;
use Flarum\User\Guest;
use Pusher;

class PushNewPost
{
    /**
     * @var Pusher
     */
    protected $pusher;

    public function __construct(Pusher $pusher)
    {
        $this->pusher = $pusher;
    }

    public function handle(Posted $event)
    {
        if ($event->post->isVisibleTo(new Guest)) {
            $tags = $event->post->discussion->tags;

            $this->pusher->trigger('public', 'newPost', [
                'postId' => $event->post->id,
                'discussionId' => $event->post->discussion->id,
                'tagIds' => $tags ? $tags->pluck('id') : null
            ]);
        }
    }
}
