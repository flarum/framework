<?php namespace Flarum\Core\Models;

use Flarum\Core\Formatter\FormatterManager;
use Flarum\Core\Events\PostWasPosted;
use Flarum\Core\Events\PostWasRevised;
use Flarum\Core\Events\PostWasHidden;
use Flarum\Core\Events\PostWasRestored;

class CommentPost extends Post
{
    /**
     * The text formatter instance.
     *
     * @var \Flarum\Core\Formatter\Formatter
     */
    protected static $formatter;

    /**
     * Add an event listener to set the post's number, and update the
     * discussion's number index, when inserting a post.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            $post->number = ++$post->discussion->number_index;
            $post->discussion->save();
        });
    }

    /**
     * Create a new instance in reply to a discussion.
     *
     * @param  int  $discussionId
     * @param  string  $content
     * @param  int  $userId
     * @return static
     */
    public static function reply($discussionId, $content, $userId)
    {
        $post = new static;

        $post->content       = $content;
        $post->content_html  = static::formatContent($post->content);
        $post->time          = time();
        $post->discussion_id = $discussionId;
        $post->user_id       = $userId;
        $post->type          = 'comment';

        $post->raise(new PostWasPosted($post));

        return $post;
    }

    /**
     * Revise the post's content.
     *
     * @param  string  $content
     * @param  \Flarum\Core\Models\User  $user
     * @return $this
     */
    public function revise($content, $user)
    {
        if ($this->content !== $content) {
            $this->content = $content;
            $this->content_html = static::formatContent($this->content);

            $this->edit_time = time();
            $this->edit_user_id = $user->id;

            $this->raise(new PostWasRevised($this));
        }

        return $this;
    }

    /**
     * Hide the post.
     *
     * @param  \Flarum\Core\Models\User  $user
     * @return $this
     */
    public function hide($user)
    {
        if (! $this->hide_time) {
            $this->hide_time = time();
            $this->hide_user_id = $user->id;

            $this->raise(new PostWasHidden($this));
        }

        return $this;
    }

    /**
     * Restore the post.
     *
     * @param  \Flarum\Core\Models\User  $user
     * @return $this
     */
    public function restore($user)
    {
        if ($this->hide_time !== null) {
            $this->hide_time = null;
            $this->hide_user_id = null;

            $this->raise(new PostWasRestored($this));
        }

        return $this;
    }

    /**
     * Get the content formatter as HTML.
     *
     * @param  string  $value
     * @return string
     */
    public function getContentHtmlAttribute($value)
    {
        if (! $value) {
            $this->content_html = $value = static::formatContent($this->content);
            $this->save();
        }

        return $value;
    }

    /**
     * Get text formatter instance.
     *
     * @return \Flarum\Core\Formatter\FormatterManager
     */
    public static function getFormatter()
    {
        return static::$formatter;
    }

    /**
     * Set text formatter instance.
     *
     * @param  \Flarum\Core\Formatter\FormatterManager  $formatter
     */
    public static function setFormatter(FormatterManager $formatter)
    {
        static::$formatter = $formatter;
    }

    /**
     * Format a string of post content using the set formatter.
     *
     * @param  string  $content
     * @return string
     */
    protected static function formatContent($content)
    {
        return static::$formatter->format($content);
    }
}
