<?php namespace Flarum\Mentions\Handlers;

use Flarum\Mentions\PostMentionsParser;
use Flarum\Mentions\PostMentionedNotification;
use Flarum\Core\Events\PostWasPosted;
use Flarum\Core\Events\PostWasRevised;
use Flarum\Core\Events\PostWasDeleted;
use Flarum\Core\Models\Post;
use Flarum\Core\Notifications\Notifier;
use Illuminate\Contracts\Events\Dispatcher;

class PostMentionsMetadataUpdater
{
    protected $parser;

    protected $notifier;

    public function __construct(PostMentionsParser $parser, Notifier $notifier)
    {
        $this->parser = $parser;
        $this->notifier = $notifier;
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen('Flarum\Core\Events\PostWasPosted', __CLASS__.'@whenPostWasPosted');
        $events->listen('Flarum\Core\Events\PostWasRevised', __CLASS__.'@whenPostWasRevised');
        $events->listen('Flarum\Core\Events\PostWasDeleted', __CLASS__.'@whenPostWasDeleted');
    }

    public function whenPostWasPosted(PostWasPosted $event)
    {
        $mentioned = $this->syncMentions($event->post);

        // @todo convert this into a new event (PostWasMentioned) and send
        // notification as a handler?
        foreach ($mentioned as $post) {
            if ($post->user->id !== $reply->user->id) {
                $this->notifier->send(new PostMentionedNotification($post, $reply->user, $reply), [$post->user]);
            }
        }
    }

    public function whenPostWasRevised(PostWasRevised $event)
    {
        $this->syncMentions($event->post);
    }

    public function whenPostWasDeleted(PostWasDeleted $event)
    {
        $event->post->mentionsPosts()->sync([]);
    }

    protected function syncMentions(Post $reply)
    {
        $matches = $this->parser->match($reply->content);

        $mentioned = $reply->discussion->posts()->with('user')->whereIn('number', array_filter($matches['number']))->get();
        $reply->mentionsPosts()->sync($mentioned->lists('id'));

        return $mentioned;
    }
}
