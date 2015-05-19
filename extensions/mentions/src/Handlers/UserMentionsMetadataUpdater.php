<?php namespace Flarum\Mentions\Handlers;

use Flarum\Mentions\UserMentionsParser;
use Flarum\Mentions\UserMentionedNotification;
use Flarum\Core\Events\PostWasPosted;
use Flarum\Core\Events\PostWasRevised;
use Flarum\Core\Events\PostWasDeleted;
use Flarum\Core\Models\Post;
use Flarum\Core\Models\User;
use Flarum\Core\Notifications\Notifier;
use Illuminate\Contracts\Events\Dispatcher;

class UserMentionsMetadataUpdater
{
    protected $parser;

    protected $notifier;

    public function __construct(UserMentionsParser $parser, Notifier $notifier)
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

        // @todo convert this into a new event (UserWasMentioned) and send
        // notification as a handler?
        foreach ($mentioned as $user) {
            if ($user->id !== $post->user->id) {
                $this->notifier->send(new UserMentionedNotification($post->user, $post), [$user]);
            }
        }
    }

    public function whenPostWasRevised(PostWasRevised $event)
    {
        $this->syncMentions($event->post);
    }

    public function whenPostWasDeleted(PostWasDeleted $event)
    {
        $event->post->mentionsUsers()->sync([]);
    }

    public function syncMentions(Post $post)
    {
        $matches = $this->parser->match($post->content);

        $mentioned = User::whereIn('username', array_filter($matches['username']))->get();
        $post->mentionsUsers()->sync($mentioned);

        return $mentioned;
    }
}
