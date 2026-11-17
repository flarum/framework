<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes\Data;

use Flarum\Likes\LikeCount;
use Flarum\Post\Post;

class Posts extends \Flarum\Gdpr\Data\Posts
{
    public function delete(): void
    {
        Post::query()->getConnection()->transaction(function () {
            $counts = resolve(LikeCount::class);
            $affected = $counts->affectedUserIds(Post::query()->where('user_id', $this->user->id));
            parent::delete();
            $counts->refresh($affected);
        });
    }
}
