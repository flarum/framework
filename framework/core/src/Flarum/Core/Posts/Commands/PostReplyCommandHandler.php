<?php namespace Flarum\Core\Posts\Commands;

use Flarum\Core\Discussions\DiscussionRepository;
use Flarum\Core\Posts\CommentPost;
use Flarum\Core\Posts\PostRepository;
use Laracasts\Commander\CommandHandler;
use Laracasts\Commander\Events\DispatchableTrait;
use Event;

class PostReplyCommandHandler implements CommandHandler
{
    use DispatchableTrait;

    protected $discussions;

    protected $posts;

    public function __construct(DiscussionRepository $discussions, PostRepository $posts)
    {
        $this->discussions = $discussions;
        $this->posts = $posts;
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
            $command->content,
            $user->id
        );

        Event::fire('Flarum.Core.Posts.Commands.PostReply.PostWillBeSaved', [$post, $command]);

        $this->posts->save($post);
        $this->dispatchEventsFor($post);

        return $post;
    }
}
