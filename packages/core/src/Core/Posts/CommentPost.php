<?php namespace Flarum\Core\Posts;

use DomainException;
use Flarum\Core\Formatter\FormatterManager;
use Flarum\Core\Posts\Events\PostWasPosted;
use Flarum\Core\Posts\Events\PostWasRevised;
use Flarum\Core\Posts\Events\PostWasHidden;
use Flarum\Core\Posts\Events\PostWasRestored;
use Flarum\Core\Users\User;

/**
 * A standard comment in a discussion.
 */
class CommentPost extends Post
{
    /**
     * {@inheritdoc}
     */
    public static $type = 'comment';

    /**
     * The text formatter instance.
     *
     * @var FormatterManager
     */
    protected static $formatter;

    /**
     * Create a new instance in reply to a discussion.
     *
     * @param int $discussionId
     * @param string $content
     * @param int $userId
     * @return static
     */
    public static function reply($discussionId, $content, $userId)
    {
        $post = new static;

        $post->content       = $content;
        $post->time          = time();
        $post->discussion_id = $discussionId;
        $post->user_id       = $userId;
        $post->type          = static::$type;

        $post->raise(new PostWasPosted($post));

        return $post;
    }

    /**
     * Revise the post's content.
     *
     * @param string $content
     * @param User $actor
     * @return $this
     */
    public function revise($content, User $actor)
    {
        if ($this->content !== $content) {
            $this->content = $content;
            $this->content_html = static::formatContent($this);

            $this->edit_time = time();
            $this->edit_user_id = $actor->id;

            $this->raise(new PostWasRevised($this));
        }

        return $this;
    }

    /**
     * Hide the post.
     *
     * @param User $actor
     * @return $this
     */
    public function hide(User $actor)
    {
        if ($this->number == 1) {
            throw new DomainException('Cannot hide the first post of a discussion');
        }

        if (! $this->hide_time) {
            $this->hide_time = time();
            $this->hide_user_id = $actor->id;

            $this->raise(new PostWasHidden($this));
        }

        return $this;
    }

    /**
     * Restore the post.
     *
     * @return $this
     */
    public function restore()
    {
        if ($this->number == 1) {
            throw new DomainException('Cannot restore the first post of a discussion');
        }

        if ($this->hide_time !== null) {
            $this->hide_time = null;
            $this->hide_user_id = null;

            $this->raise(new PostWasRestored($this));
        }

        return $this;
    }

    /**
     * Get the content formatted as HTML.
     *
     * @param string $value
     * @return string
     */
    public function getContentHtmlAttribute($value)
    {
        if (! $value) {
            $this->content_html = $value = static::formatContent($this);
            $this->save();
        }

        return $value;
    }

    /**
     * Get text formatter instance.
     *
     * @return FormatterManager
     */
    public static function getFormatter()
    {
        return static::$formatter;
    }

    /**
     * Set text formatter instance.
     *
     * @param FormatterManager $formatter
     */
    public static function setFormatter(FormatterManager $formatter)
    {
        static::$formatter = $formatter;
    }

    /**
     * Format a string of post content using the set formatter.
     *
     * @param CommentPost $post
     * @return string
     */
    protected static function formatContent(CommentPost $post)
    {
        return static::$formatter->format($post->content, $post);
    }
}
