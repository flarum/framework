<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Pusher\Listeners;

use Flarum\Events\PostWasPosted;
use Flarum\Events\NotificationWillBeSent;
use Illuminate\Contracts\Events\Dispatcher;
use Flarum\Core\Settings\SettingsRepository;
use Flarum\Core\Users\Guest;
use Flarum\Core\Discussions\Discussion;
use Pusher;

class PushNewPosts
{
    protected $settings;

    public function __construct(SettingsRepository $settings)
    {
        $this->settings = $settings;
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen(PostWasPosted::class, [$this, 'pushNewPost']);
        $events->listen(NotificationWillBeSent::class, [$this, 'pushNotification']);
    }

    public function pushNewPost(PostWasPosted $event)
    {
        if ($event->post->isVisibleTo(new Guest)) {
            $pusher = $this->getPusher();

            $pusher->trigger('public', 'newPost', [
                'postId' => $event->post->id,
                'discussionId' => $event->post->discussion->id,
                'tagIds' => $event->post->discussion->tags()->lists('id')
            ]);
        }
    }

    public function pushNotification(NotificationWillBeSent $event)
    {
        $pusher = $this->getPusher();
        $blueprint = $event->blueprint;

        foreach ($event->users as $user) {
            if ($user->shouldAlert($blueprint::getType())) {
                $pusher->trigger('private-user' . $user->id, 'notification', null);
            }
        }
    }

    protected function getPusher()
    {
        return new Pusher(
            $this->settings->get('pusher.app_key'),
            $this->settings->get('pusher.app_secret'),
            $this->settings->get('pusher.app_id')
        );
    }
}
