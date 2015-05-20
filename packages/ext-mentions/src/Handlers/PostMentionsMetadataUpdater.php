<?php namespace Flarum\Mentions\Handlers;

use Flarum\Mentions\PostMentionsParser;
use Flarum\Mentions\PostMentionedActivity;
use Flarum\Mentions\PostMentionedNotification;
use Flarum\Core\Activity\ActivitySyncer;
use Flarum\Core\Notifications\NotificationSyncer;
use Flarum\Core\Events\PostWasPosted;
use Flarum\Core\Events\PostWasRevised;
use Flarum\Core\Events\PostWasDeleted;
use Flarum\Core\Models\Post;
use Flarum\Core\Notifications\Notifier;
use Illuminate\Contracts\Events\Dispatcher;

class PostMentionsMetadataUpdater
{
    protected $parser;

    protected $activity;

    protected $notifications;

    public function __construct(PostMentionsParser $parser, ActivitySyncer $activity, NotificationSyncer $notifications)
    {
        $this->parser = $parser;
        $this->activity = $activity;
        $this->notifications = $notifications;
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen('Flarum\Core\Events\PostWasPosted', __CLASS__.'@whenPostWasPosted');
        $events->listen('Flarum\Core\Events\PostWasRevised', __CLASS__.'@whenPostWasRevised');
        $events->listen('Flarum\Core\Events\PostWasHidden', __CLASS__.'@whenPostWasHidden');
        $events->listen('Flarum\Core\Events\PostWasRestored', __CLASS__.'@whenPostWasRestored');
        $events->listen('Flarum\Core\Events\PostWasDeleted', __CLASS__.'@whenPostWasDeleted');
    }

    public function whenPostWasPosted(PostWasPosted $event)
    {
        $this->replyBecameVisible($event->post);
    }

    public function whenPostWasRevised(PostWasRevised $event)
    {
        $this->replyBecameVisible($event->post);
    }

    public function whenPostWasHidden(PostWasHidden $event)
    {
        $this->replyBecameInvisible($event->post);
    }

    public function whenPostWasRestored(PostWasRestored $event)
    {
        $this->replyBecameVisible($event->post);
    }

    public function whenPostWasDeleted(PostWasDeleted $event)
    {
        $this->replyBecameInvisible($event->post);
    }

    protected function replyBecameVisible(Post $reply)
    {
        $matches = $this->parser->match($reply->content);

        $mentioned = $reply->discussion->posts()->with('user')->whereIn('number', array_filter($matches['number']))->get()->all();

        $this->sync($reply, $mentioned);
    }

    protected function replyBecameInvisible(Post $reply)
    {
        $this->sync($reply, []);
    }

    protected function sync(Post $reply, array $mentioned)
    {
        $reply->mentionsPosts()->sync(array_pluck($mentioned, 'id'));

        $mentioned = array_filter($mentioned, function ($post) use ($reply) {
            return $post->user->id !== $reply->user->id;
        });

        foreach ($mentioned as $post) {
            $this->activity->sync(new PostMentionedActivity($post, $reply), [$post->user]);

            $this->notifications->sync(new PostMentionedNotification($post, $reply), [$post->user]);
        }
    }
}
