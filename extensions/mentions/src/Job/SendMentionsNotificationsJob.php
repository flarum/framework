<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Job;

use Flarum\Mentions\Notification\GroupMentionedBlueprint;
use Flarum\Mentions\Notification\PostMentionedBlueprint;
use Flarum\Mentions\Notification\UserMentionedBlueprint;
use Flarum\Notification\NotificationSyncer;
use Flarum\Post\CommentPost;
use Flarum\Post\Post;
use Flarum\Queue\AbstractJob;
use Flarum\User\User;

class SendMentionsNotificationsJob extends AbstractJob
{
    private NotificationSyncer $notifications;

    public function __construct(
        protected CommentPost $post,
        protected array $userMentions,
        protected array $postMentions,
        protected array $groupMentions
    ) {
    }

    public function handle(NotificationSyncer $notifications): void
    {
        $this->notifications = $notifications;

        $this->notifyAboutUserMentions($this->post, $this->userMentions);
        $this->notifyAboutPostMentions($this->post, $this->postMentions);
        $this->notifyAboutGroupMentions($this->post, $this->groupMentions);
    }

    protected function notifyAboutUserMentions(Post $post, array $mentioned): void
    {
        $users = User::whereIn('id', $mentioned)
            ->with('groups')
            ->get()
            ->filter(fn ($user) => $post->isVisibleTo($user) && $user->id !== $post->user_id)
            ->all();

        $this->notifications->sync(new UserMentionedBlueprint($post), $users);
    }

    protected function notifyAboutPostMentions(Post $reply, array $mentioned): void
    {
        $posts = Post::with('user')
            ->whereIn('id', $mentioned)
            ->with('user.groups')
            ->get()
            ->filter(fn (Post $post) => $post->user && $post->user_id !== $reply->user_id && $reply->isVisibleTo($post->user))
            ->all();

        foreach ($posts as $post) {
            $this->notifications->sync(new PostMentionedBlueprint($post, $reply), [$post->user]);
        }
    }

    protected function notifyAboutGroupMentions(Post $post, array $mentioned): void
    {
        $users = User::whereHas('groups', function ($query) use ($mentioned) {
            $query->whereIn('groups.id', $mentioned);
        })
            ->with('groups')
            ->get()
            ->filter(fn (User $user) => $post->isVisibleTo($user) && $user->id !== $post->user_id)
            ->all();

        $this->notifications->sync(new GroupMentionedBlueprint($post), $users);
    }
}
