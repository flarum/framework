<?php namespace Flarum\Core\Posts;

use Flarum\Core\Discussions\Discussion;
use Flarum\Core\Users\User;
use Flarum\Support\ServiceProvider;
use Flarum\Extend;

class PostsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->extend([
            new Extend\PostType('Flarum\Core\Posts\CommentPost'),
            new Extend\PostType('Flarum\Core\Posts\DiscussionRenamedPost')
        ]);

        CommentPost::setFormatter($this->app->make('flarum.formatter'));

        Post::allow('*', function ($post, $user, $action) {
            return $post->discussion->can($user, $action.'Posts') ?: null;
        });

        // When fetching a discussion's posts: if the user doesn't have permission
        // to moderate the discussion, then they can't see posts that have been
        // hidden by someone other than themself.
        Discussion::addPostVisibilityScope(function ($query, User $user, Discussion $discussion) {
            if (! $discussion->can($user, 'editPosts')) {
                $query->where(function ($query) use ($user) {
                    $query->whereNull('hide_user_id')
                          ->orWhere('hide_user_id', $user->id);
                });
            }
        });

        Post::allow('view', function ($post, $user) {
            return ! $post->hide_user_id || $post->can($user, 'edit') ?: null;
        });

        // A post is allowed to be edited if the user has permission to moderate
        // the discussion which it's in, or if they are the author and the post
        // hasn't been deleted by someone else.
        Post::allow('edit', function ($post, $user) {
            if ($post->discussion->can($user, 'editPosts') ||
                ($post->user_id == $user->id && (! $post->hide_user_id || $post->hide_user_id == $user->id))
            ) {
                return true;
            }
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
    }
}
