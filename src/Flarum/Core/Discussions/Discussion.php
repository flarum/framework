<?php namespace Flarum\Core\Discussions;

use Laracasts\Commander\Events\EventGenerator;
use Tobscure\Permissible\Permissible;

use Flarum\Core\Entity;
use Flarum\Core\Forum;
use Flarum\Core\Permission;
use Flarum\Core\Support\Exceptions\PermissionDeniedException;
use Flarum\Core\Users\User;

class Discussion extends Entity
{
    use Permissible;
    use EventGenerator;

    protected $table = 'discussions';

    protected static $rules = [
        'title'            => 'required',
        'start_time'       => 'required|date',
        'comments_count'   => 'integer',
        'start_user_id'    => 'integer',
        'start_post_id'    => 'integer',
        'last_time'        => 'date',
        'last_user_id'     => 'integer',
        'last_post_id'     => 'integer',
        'last_post_number' => 'integer'
    ];

    public static function boot()
    {
        parent::boot();

        static::grant(function ($grant, $user, $permission) {
            return app('flarum.permissions')->granted($user, $permission, 'discussion');
        });

        // Grant view access to a discussion if the user can view the forum.
        static::grant('view', function ($grant, $user) {
            return app('flarum.forum')->can($user, 'view');
        });

        // Allow a user to edit their own discussion.
        static::grant('edit', function ($grant, $user) {
            if (app('flarum.permissions')->granted($user, 'editOwn', 'discussion')) {
                $grant->where('user_id', $user->id);
            }
        });

        static::deleted(function ($discussion) {
            $discussion->raise(new Events\DiscussionWasDeleted($discussion));

            $discussion->posts()->delete();
            $discussion->readers()->detach();
        });
    }

    public static function start($title, $user)
    {
        $discussion = new static;

        $discussion->title         = $title;
        $discussion->start_time    = time();
        $discussion->start_user_id = $user->id;

        $discussion->raise(new Events\DiscussionWasStarted($discussion));

        return $discussion;
    }

    public function setLastPost($post)
    {
        $this->last_time        = $post->time;
        $this->last_user_id     = $post->user_id;
        $this->last_post_id     = $post->id;
        $this->last_post_number = $post->number;
    }

    public function refreshLastPost()
    {
        $lastPost = $this->comments()->orderBy('time', 'desc')->first();
        $this->setLastPost($lastPost);
    }

    public function refreshCommentsCount()
    {
        $this->comments_count = $this->comments()->count();
    }

    public function rename($title, $user)
    {
        if ($this->title === $title) {
            return;
        }

        $this->title = $title;

        $this->raise(new Events\DiscussionWasRenamed($this, $user));
    }

    public function getDates()
    {
        return ['start_time', 'last_time'];
    }

    public function posts()
    {
        return $this->hasMany('Flarum\Core\Posts\Post')->orderBy('time', 'asc');
    }

    public function comments()
    {
        return $this->posts()->where('type', 'comment')->whereNull('delete_time');
    }

    public function startPost()
    {
        return $this->belongsTo('Flarum\Core\Posts\Post', 'start_post_id');
    }

    public function startUser()
    {
        return $this->belongsTo('Flarum\Core\Users\User', 'start_user_id');
    }

    public function lastPost()
    {
        return $this->belongsTo('Flarum\Core\Posts\Post', 'last_post_id');
    }

    public function lastUser()
    {
        return $this->belongsTo('Flarum\Core\Users\User', 'last_user_id');
    }

    public function readers()
    {
        return $this->belongsToMany('Flarum\Core\Users\User', 'users_discussions');
    }

    public function state($userId = null)
    {
        if (is_null($userId)) {
            $userId = User::current()->id;
        }
        return $this->hasOne('Flarum\Core\Discussions\DiscussionState')->where('user_id', $userId);
    }

    public function stateFor($user)
    {
        $state = $this->state($user->id)->first();

        if (! $state) {
            $state = new DiscussionState;
            $state->discussion_id = $this->id;
            $state->user_id = $user->id;
        }

        return $state;
    }

    public function scopePermission($query, $permission, $user = null)
    {
        if (is_null($user)) {
            $user = User::current();
        }
        return $this->scopeWhereCan($query, $user, $permission);
    }

    public function scopeWhereCanView($query, $user = null)
    {
        return $this->scopePermission($query, 'view', $user);
    }

    public function permission($permission, $user = null)
    {
        if (is_null($user)) {
            $user = User::current();
        }
        return $this->can($user, $permission);
    }

    public function assertCan($user, $permission)
    {
        if (! $this->can($user, $permission)) {
            throw new PermissionDeniedException;
        }
    }
}
