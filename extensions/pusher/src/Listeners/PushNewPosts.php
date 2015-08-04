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
        $guest = new Guest;
        $discussion = Discussion::whereVisibleTo($guest)->find($event->post->discussion_id);

        if ($discussion) {
            $post = $discussion->postsVisibleTo($guest)->find($event->post->id);

            if ($post) {
                $pusher = new Pusher(
                    $this->settings->get('pusher.app_key'),
                    $this->settings->get('pusher.app_secret'),
                    $this->settings->get('pusher.app_id')
                );

                $pusher->trigger('public', 'newPost', [
                    'postId' => $post->id,
                    'discussionId' => $discussion->id,
                    'tagIds' => $discussion->tags()->lists('id')
                ]);
            }
        }
    }
}
