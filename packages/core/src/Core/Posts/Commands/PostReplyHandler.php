<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Posts\Commands;

use Flarum\Events\PostWillBeSaved;
use Flarum\Core\Discussions\DiscussionRepository;
use Flarum\Core\Posts\CommentPost;
use Flarum\Core\Support\DispatchesEvents;
use Flarum\Core\Notifications\NotificationSyncer;

class PostReplyHandler
{
    use DispatchesEvents;

    /**
     * @var DiscussionRepository
     */
    protected $discussions;

    /**
     * @var NotificationSyncer
     */
    protected $notifications;

    /**
     * @param DiscussionRepository $discussions
     * @param NotificationSyncer $notifications
     */
    public function __construct(DiscussionRepository $discussions, NotificationSyncer $notifications)
    {
        $this->discussions = $discussions;
        $this->notifications = $notifications;
    }

    /**
     * @param PostReply $command
     * @return CommentPost
     * @throws \Flarum\Core\Exceptions\PermissionDeniedException
     */
    public function handle(PostReply $command)
    {
        $actor = $command->actor;

        // Make sure the user has permission to reply to this discussion. First,
        // make sure the discussion exists and that the user has permission to
        // view it; if not, fail with a ModelNotFound exception so we don't give
        // away the existence of the discussion. If the user is allowed to view
        // it, check if they have permission to reply.
        $discussion = $this->discussions->findOrFail($command->discussionId, $actor);

        $discussion->assertCan($actor, 'reply');

        // Create a new Post entity, persist it, and dispatch domain events.
        // Before persistence, though, fire an event to give plugins an
        // opportunity to alter the post entity based on data in the command.
        $post = CommentPost::reply(
            $command->discussionId,
            array_get($command->data, 'attributes.content'),
            $actor->id
        );

        event(new PostWillBeSaved($post, $actor, $command->data));

        $post->save();

        $this->notifications->onePerUser(function () use ($post) {
            $this->dispatchEventsFor($post);
        });

        return $post;
    }
}
