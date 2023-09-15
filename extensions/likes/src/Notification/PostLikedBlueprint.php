<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes\Notification;

use Flarum\Database\AbstractModel;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Post\Post;
use Flarum\User\User;

class PostLikedBlueprint implements BlueprintInterface
{
    public function __construct(
        public Post $post,
        public User $user
    ) {
    }

    public function getSubject(): ?AbstractModel
    {
        return $this->post;
    }

    public function getFromUser(): ?User
    {
        return $this->user;
    }

    public function getData(): mixed
    {
        return null;
    }

    public static function getType(): string
    {
        return 'postLiked';
    }

    public static function getSubjectModel(): string
    {
        return Post::class;
    }
}
