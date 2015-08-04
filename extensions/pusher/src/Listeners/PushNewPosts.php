<?php namespace Flarum\Pusher\Listeners;

use Flarum\Events\PostWasPosted;
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
    }

    public function pushNewPost(PostWasPosted $event)
    {
        if ($event->post->isVisibleTo(new Guest)) {
            $pusher = new Pusher(
                $this->settings->get('pusher.app_key'),
                $this->settings->get('pusher.app_secret'),
                $this->settings->get('pusher.app_id')
            );

            $pusher->trigger('public', 'newPost', [
                'postId' => $event->post->id,
                'discussionId' => $event->post->discussion->id,
                'tagIds' => $event->post->discussion->tags()->lists('id')
            ]);
        }
    }
}
