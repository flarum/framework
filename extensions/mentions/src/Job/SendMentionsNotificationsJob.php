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
    /**
     * @var CommentPost
     */
    protected $post;

    /**
     * @var array
     */
    protected $userMentions;

    /**
     * @var array
     */
    protected $postMentions;

    /**
     * @var array
     */
    protected $groupMentions;

    /**
     * @var NotificationSyncer
     */
    private $notifications;

    public function __construct(CommentPost $post, array $userMentions, array $postMentions, array $groupMentions)
    {
        $this->post = $post;
        $this->userMentions = $userMentions;
        $this->postMentions = $postMentions;
        $this->groupMentions = $groupMentions;
    }

    public function handle(NotificationSyncer $notifications): void
    {
        $this->notifications = $notifications;

        $this->notifyAboutUserMentions($this->post, $this->userMentions);
        $this->notifyAboutPostMentions($this->post, $this->postMentions);
        $this->notifyAboutGroupMentions($this->post, $this->groupMentions);
    }

    protected function notifyAboutUserMentions(Post $post, array $mentioned)
    {
        $users = User::whereIn('id', $mentioned)
            ->with('groups')
            ->get()
            ->filter(function ($user) use ($post) {
                return $post->isVisibleTo($user) && $user->id !== $post->user_id;
            })
            ->all();

        $this->notifications->sync(new UserMentionedBlueprint($post), $users);
    }

    protected function notifyAboutPostMentions(Post $reply, array $mentioned)
    {
        $posts = Post::with('user')
            ->whereIn('id', $mentioned)
            ->with('user.groups')
            ->get()
            ->filter(function (Post $post) use ($reply) {
                return $post->user && $post->user_id !== $reply->user_id && $reply->isVisibleTo($post->user);
            })
            ->all();

        foreach ($posts as $post) {
            $this->notifications->sync(new PostMentionedBlueprint($post, $reply), [$post->user]);
        }
    }

    protected function notifyAboutGroupMentions(Post $post, array $mentioned)
    {
        $users = User::whereHas('groups', function ($query) use ($mentioned) {
            $query->whereIn('groups.id', $mentioned);
        })
            ->with('groups')
            ->get()
            ->filter(function (User $user) use ($post) {
                return $post->isVisibleTo($user) && $user->id !== $post->user_id;
            })
            ->all();

        $this->notifications->sync(new GroupMentionedBlueprint($post), $users);
    }
}
