<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes\Access;

use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class LikePostPolicy extends AbstractPolicy
{
    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function like(User $actor, Post $post): ?string
    {
        if ($actor->id === $post->user_id && ! (bool) $this->settings->get('flarum-likes.like_own_post')) {
            return $this->deny();
        }

        return null;
    }
}
