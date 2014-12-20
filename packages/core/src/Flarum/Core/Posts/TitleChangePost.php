<?php namespace Flarum\Core\Posts;

use Laracasts\Commander\Events\EventGenerator;
use Tobscure\Permissible\Permissible;

use Flarum\Core\Entity;
use Flarum\Core\Permission;
use Flarum\Core\Support\Exceptions\PermissionDeniedException;
use Flarum\Core\Users\User;

class TitleChangePost extends Post
{
    public static function reply($discussionId, $content, $userId)
    {
        $post = new static;

        $post->content       = $content;
        $post->time          = time();
        $post->discussion_id = $discussionId;
        $post->user_id       = $userId;
        $post->type          = 'titleChange';

        return $post;
    }
}
