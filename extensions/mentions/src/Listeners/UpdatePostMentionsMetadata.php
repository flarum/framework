<?php namespace Flarum\Mentions\Listeners;

use Flarum\Mentions\Notifications\PostMentionedBlueprint;
use Flarum\Core\Notifications\NotificationSyncer;
use Flarum\Events\RegisterNotificationTypes;
use Flarum\Events\PostWasPosted;
use Flarum\Events\PostWasRevised;
use Flarum\Events\PostWasHidden;
use Flarum\Events\PostWasRestored;
use Flarum\Events\PostWasDeleted;
use Flarum\Core\Posts\Post;
use Illuminate\Contracts\Events\Dispatcher;
use s9e\TextFormatter\Utils;

class UpdatePostMentionsMetadata
{
    protected $notifications;

    public function __construct(NotificationSyncer $notifications)
    {
        $this->notifications = $notifications;
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen(RegisterNotificationTypes::class, __CLASS__.'@registerNotificationType');

        $events->listen(PostWasPosted::class, __CLASS__.'@whenPostWasPosted');
        $events->listen(PostWasRevised::class, __CLASS__.'@whenPostWasRevised');
        $events->listen(PostWasHidden::class, __CLASS__.'@whenPostWasHidden');
        $events->listen(PostWasRestored::class, __CLASS__.'@whenPostWasRestored');
        $events->listen(PostWasDeleted::class, __CLASS__.'@whenPostWasDeleted');
    }

    public function registerNotificationType(RegisterNotificationTypes $event)
    {
        $event->register(
            PostMentionedBlueprint::class,
            'Flarum\Api\Serializers\PostBasicSerializer',
            ['alert']
        );
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
        $mentioned = Utils::getAttributeValues($reply->parsedContent, 'POSTMENTION', 'id');

        $this->sync($reply, $mentioned);
    }

    protected function replyBecameInvisible(Post $reply)
    {
        $this->sync($reply, []);
    }

    protected function sync(Post $reply, array $mentioned)
    {
        $reply->mentionsPosts()->sync($mentioned);

        $posts = Post::with('user')
            ->whereIn('id', $mentioned)
            ->get()
            ->filter(function ($post) use ($reply) {
                return $post->user->id !== $reply->user->id;
            })
            ->all();

        foreach ($posts as $post) {
            $this->notifications->sync(new PostMentionedBlueprint($post, $reply), [$post->user]);
        }
    }
}
