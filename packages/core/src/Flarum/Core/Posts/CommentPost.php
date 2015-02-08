<?php namespace Flarum\Core\Posts;

use App;
use Laracasts\Commander\Events\EventGenerator;
use Tobscure\Permissible\Permissible;

use Flarum\Core\Entity;
use Flarum\Core\Permission;
use Flarum\Core\Support\Exceptions\PermissionDeniedException;
use Flarum\Core\Users\User;

class CommentPost extends Post
{
    public static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            $post->number = ++$post->discussion->number_index;
            $post->discussion->save();
        });
    }

    public static function reply($discussionId, $content, $userId)
    {
        $post = new static;

        $post->content       = $content;
        $post->content_html  = static::formatContent($post->content);
        $post->time          = time();
        $post->discussion_id = $discussionId;
        $post->user_id       = $userId;
        $post->type          = 'comment';

        $post->raise(new Events\ReplyWasPosted($post));

        return $post;
    }

    public function revise($content, $user)
    {
        $this->content = $content;
        $this->content_html = static::formatContent($this->content);

        $this->edit_time = time();
        $this->edit_user_id = $user->id;

        $this->raise(new Events\PostWasRevised($this));
    }

    public function hide($user)
    {
        $this->delete_time = time();
        $this->delete_user_id = $user->id;

        $this->raise(new Events\PostWasHidden($this));
    }

    public function restore($user)
    {
        $this->delete_time = null;
        $this->delete_user_id = null;

        $this->raise(new Events\PostWasRestored($this));
    }

    public function getContentHtmlAttribute($value)
    {
        if (! $value) {
            $this->content_html = $value = static::formatContent($this->content);
            $this->save();
        }

        return $value;
    }

    protected static function formatContent($content)
    {
        $formatter = App::make('flarum.formatter');
        return $formatter->format($content);
    }
}
