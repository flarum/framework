<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Pusher\Listener;

use Flarum\Extension\ExtensionManager;
use Flarum\Post\Event\Posted;
use Flarum\User\Guest;
use Flarum\User\User;
use Illuminate\Support\Str;
use Pusher\Pusher;

class PushNewPost
{
    public function __construct(
        protected Pusher $pusher,
        protected ExtensionManager $extensions
    ) {
    }

    public function handle(Posted $event): void
    {
        $channels = [];

        if ($event->post->isVisibleTo(new Guest)) {
            $channels[] = 'public';
        } else {
            // Retrieve private channels, used for each user.
            $response = $this->pusher->getChannels([
                'filter_by_prefix' => 'private-user'
            ]);

            // @phpstan-ignore-next-line
            if (! $response) {
                return;
            }

            foreach ($response->channels as $name => $channel) {
                $userId = Str::after($name, 'private-user');

                if (($user = User::find($userId)) && $event->post->isVisibleTo($user)) {
                    $channels[] = $name;
                }
            }
        }

        if (count($channels)) {
            $tags = $this->extensions->isEnabled('flarum-tags') ? $event->post->discussion->tags : null;

            $this->pusher->trigger($channels, 'newPost', [
                'postId' => $event->post->id,
                'discussionId' => $event->post->discussion->id,
                'tagIds' => $tags ? $tags->pluck('id') : null
            ]);
        }
    }
}
