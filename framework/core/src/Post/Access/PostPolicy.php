<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post\Access;

use Carbon\Carbon;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class PostPolicy extends AbstractPolicy
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param User $actor
     * @param string $ability
     * @param \Flarum\Post\Post $post
     * @return bool|null
     */
    public function can(User $actor, $ability, Post $post)
    {
        if ($actor->can($ability.'Posts', $post->discussion)) {
            return $this->allow();
        }
    }

    /**
     * @param User $actor
     * @param Post $post
     * @return bool|null
     */
    public function edit(User $actor, Post $post)
    {
        // A post is allowed to be edited if the user is the author, the post
        // hasn't been deleted by someone else, and the user is allowed to
        // create new replies in the discussion.
        if ($post->user_id == $actor->id && (! $post->hidden_at || $post->hidden_user_id == $actor->id) && $actor->can('reply', $post->discussion)) {
            $allowEditing = $this->settings->get('allow_post_editing');

            if ($allowEditing === '-1'
                || ($allowEditing === 'reply' && $post->number >= $post->discussion->last_post_number)
                || (is_numeric($allowEditing) && $post->created_at->diffInMinutes(new Carbon) < $allowEditing)) {
                return $this->allow();
            }
        }
    }

    /**
     * @param User $actor
     * @param Post $post
     * @return bool|null
     */
    public function hide(User $actor, Post $post)
    {
        if ($post->user_id == $actor->id && (! $post->hidden_at || $post->hidden_user_id == $actor->id) && $actor->can('reply', $post->discussion)) {
            $allowHiding = $this->settings->get('allow_hide_own_posts');

            if ($allowHiding === '-1'
                || ($allowHiding === 'reply' && $post->number >= $post->discussion->last_post_number)
                || (is_numeric($allowHiding) && $post->created_at->diffInMinutes(new Carbon) < $allowHiding)) {
                return $this->allow();
            }
        }
    }
}
