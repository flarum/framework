<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post\Command;

use Carbon\Carbon;
use Flarum\Discussion\DiscussionRepository;
use Flarum\Foundation\DispatchEventsTrait;
use Flarum\Notification\NotificationSyncer;
use Flarum\Post\CommentPost;
use Flarum\Post\Event\Saving;
use Flarum\Post\PostValidator;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;

class PostReplyHandler
{
    use DispatchEventsTrait;

    /**
     * @var DiscussionRepository
     */
    protected $discussions;

    /**
     * @var \Flarum\Notification\NotificationSyncer
     */
    protected $notifications;

    /**
     * @var \Flarum\Post\PostValidator
     */
    protected $validator;

    /**
     * @param Dispatcher $events
     * @param DiscussionRepository $discussions
     * @param \Flarum\Notification\NotificationSyncer $notifications
     * @param PostValidator $validator
     */
    public function __construct(
        Dispatcher $events,
        DiscussionRepository $discussions,
        NotificationSyncer $notifications,
        PostValidator $validator
    ) {
        $this->events = $events;
        $this->discussions = $discussions;
        $this->notifications = $notifications;
        $this->validator = $validator;
    }

    /**
     * @param PostReply $command
     * @return CommentPost
     * @throws \Flarum\User\Exception\PermissionDeniedException
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

        // If this is the first post in the discussion, it's technically not a
        // "reply", so we won't check for that permission.
        if ($discussion->post_number_index > 0) {
            $actor->assertCan('reply', $discussion);
        }

        // Create a new Post entity, persist it, and dispatch domain events.
        // Before persistence, though, fire an event to give plugins an
        // opportunity to alter the post entity based on data in the command.
        $post = CommentPost::reply(
            $discussion->id,
            Arr::get($command->data, 'attributes.content'),
            $actor->id,
            $command->ipAddress
        );

        if ($actor->isAdmin() && ($time = Arr::get($command->data, 'attributes.createdAt'))) {
            $post->created_at = new Carbon($time);
        }

        $this->events->dispatch(
            new Saving($post, $actor, $command->data)
        );

        $this->validator->assertValid($post->getAttributes());

        $post->save();

        $this->notifications->onePerUser(function () use ($post, $actor) {
            $this->dispatchEventsFor($post, $actor);
        });

        return $post;
    }
}
