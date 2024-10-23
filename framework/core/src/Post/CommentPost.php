<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post;

use Carbon\Carbon;
use Flarum\Formatter\Formattable;
use Flarum\Formatter\HasFormattedContent;
use Flarum\Post\Event\Hidden;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Restored;
use Flarum\Post\Event\Revised;
use Flarum\User\User;

/**
 * A standard comment in a discussion.
 */
class CommentPost extends Post implements Formattable
{
    use HasFormattedContent;

    public static string $type = 'comment';

    protected $observables = ['hidden'];

    public static function boot()
    {
        parent::boot();

        static::creating(function (self $post) {
            $post->raise(new Posted($post));
        });
    }

    public function revise(string $content, User $actor): static
    {
        if ($this->content !== $content) {
            $oldContent = $this->content;

            $this->setContentAttribute($content, $actor);

            $this->edited_at = Carbon::now();
            $this->edited_user_id = $actor->id;

            $this->raise(new Revised($this, $actor, $oldContent));
        }

        return $this;
    }

    public function hide(?User $actor = null): static
    {
        if (! $this->hidden_at) {
            $this->hidden_at = Carbon::now();
            $this->hidden_user_id = $actor?->id;

            $this->raise(new Hidden($this));

            $this->saved(function (self $model) {
                if ($model === $this) {
                    $model->fireModelEvent('hidden', false);
                }
            });
        }

        return $this;
    }

    public function restore(): static
    {
        if ($this->hidden_at !== null) {
            $this->hidden_at = null;
            $this->hidden_user_id = null;

            $this->raise(new Restored($this));

            $this->saved(function (self $model) {
                if ($model === $this) {
                    $model->fireModelEvent('restored', false);
                }
            });
        }

        return $this;
    }
}
