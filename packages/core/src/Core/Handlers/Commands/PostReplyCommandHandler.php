<?php namespace Flarum\Core\Handlers\Commands;

use Flarum\Core\Events\PostWillBeSaved;
use Flarum\Core\Repositories\DiscussionRepositoryInterface as DiscussionRepository;
use Flarum\Core\Models\CommentPost;
use Flarum\Core\Support\DispatchesEvents;
use Flarum\Core\Notifications\Notifier;

class PostReplyCommandHandler
{
    use DispatchesEvents;

    protected $discussions;

    protected $notifier;

    public function __construct(DiscussionRepository $discussions, Notifier $notifier)
    {
        $this->discussions = $discussions;
        $this->notifier = $notifier;
    }

    public function handle($command)
    {
        $user = $command->user;

        // Make sure the user has permission to reply to this discussion. First,
        // make sure the discussion exists and that the user has permission to
        // view it; if not, fail with a ModelNotFound exception so we don't give
        // away the existence of the discussion. If the user is allowed to view
        // it, check if they have permission to reply.
        $discussion = $this->discussions->findOrFail($command->discussionId, $user);

        $discussion->assertCan($user, 'reply');

        // Create a new Post entity, persist it, and dispatch domain events.
        // Before persistance, though, fire an event to give plugins an
        // opportunity to alter the post entity based on data in the command.
        $post = CommentPost::reply(
            $command->discussionId,
            array_get($command->data, 'content'),
            $user->id
        );

        event(new PostWillBeSaved($post, $command));

        $post->save();

        $this->notifier->onePerUser(function () use ($post) {
            $this->dispatchEventsFor($post);
        });

        return $post;
    }
}
