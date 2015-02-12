<?php namespace Flarum\Core\Posts;

use Laracasts\Commander\Events\EventGenerator;
use Tobscure\Permissible\Permissible;

use Flarum\Core\Entity;
use Flarum\Core\Permission;
use Flarum\Core\Support\Exceptions\PermissionDeniedException;
use Flarum\Core\Users\User;

class RenamedPost extends Post
{
    public static function reply($discussionId, $userId, $oldTitle, $newTitle)
    {
        $post = new static;

        $post->content       = [$oldTitle, $newTitle];
        $post->time          = time();
        $post->discussion_id = $discussionId;
        $post->user_id       = $userId;
        $post->type          = 'renamed';

        return $post;
    }

    public function getContentAttribute($value)
    {
        return json_decode($value);
    }

    public function setContentAttribute($value)
    {
        $this->attributes['content'] = json_encode($value);
    }
}
