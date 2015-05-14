<?php namespace Flarum\Mentions\Handlers;

use Flarum\Mentions\PostMentionsParser;
use Flarum\Mentions\PostMentionedNotification;
use Flarum\Core\Events\PostWasPosted;
use Flarum\Core\Models\User;
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

        // @todo listen for post edit/delete events and sync mentions as appropriate
    }

    public function whenPostWasPosted(PostWasPosted $event)
    {
        $reply = $event->post;

        $matches = $this->parser->match($reply->content);

        $mentioned = $reply->discussion->posts()->with('user')->whereIn('number', array_filter($matches['number']))->get();
        $reply->mentionsPosts()->sync($mentioned->lists('id'));

        // @todo convert this into a new event (PostWasMentioned) and send
        // notification as a handler?
        foreach ($mentioned as $post) {
            if ($post->user->id !== $reply->user->id) {
                $this->notifier->send(new PostMentionedNotification($post, $reply->user, $reply), [$post->user]);
            }
        }
    }
}
