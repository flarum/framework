<?php

namespace Flarum\Approval;

use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\User\User;

trait RefreshesDiscussionTrait
{
    /**
     * Refreshes and saves the discussion data.
     *
     * @param  Post  $post
     * @param callable(Post $post, Discussion $discussion, User $user): void|null $beforeDiscussionSaveCallback
     */
    protected function refreshAndSaveDiscussion(Post $post, callable $beforeDiscussionSaveCallback = null)
    {
        $discussion = $post->discussion;
        $user = $discussion->user;

        $discussion->refreshCommentCount();
        $discussion->refreshLastPost();

        if ($beforeDiscussionSaveCallback) {
            $beforeDiscussionSaveCallback($post, $discussion, $user);
        }

        $discussion->save();

        if ($discussion->user) {
            $user->refreshCommentCount();
            $user->save();
        }

        if ($post->user) {
            $post->user->refreshCommentCount();
            $post->user->save();
        }
    }
}
