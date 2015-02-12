<?php namespace Flarum\Core\Posts;

use Laracasts\Commander\Events\EventGenerator;
use Tobscure\Permissible\Permissible;

use Flarum\Core\Entity;
use Flarum\Core\Permission;
use Flarum\Core\Support\Exceptions\PermissionDeniedException;
use Flarum\Core\Users\User;

class Post extends Entity
{
    use EventGenerator;
    use Permissible;

    protected $table = 'posts';

    protected static $rules = [
        'discussion_id'  => 'required|integer',
        'time'           => 'required|date',
        'content'        => 'required',
        'number'         => 'integer',
        'user_id'        => 'integer',
        'edit_time'      => 'date',
        'edit_user_id'   => 'integer',
        'hide_time'    => 'date',
        'hide_user_id' => 'integer',
    ];

    public static function boot()
    {
        parent::boot();

        static::grant(function ($grant, $user, $permission) {
            return app('flarum.permissions')->granted($user, $permission, 'post');
        });

        // Grant view access to a post only if the user can also view the
        // discussion which the post is in. Also, the if the post is hidden,
        // the user must have edit permissions too.
        static::grant('view', function ($grant, $user) {
            $grant->whereCan('view', 'discussion');
        });

        static::check('view', function ($check, $user) {
            $check->whereNull('hide_user_id')
                  ->orWhereCan('edit');
        });

        // Allow a user to edit their own post, unless it has been hidden by
        // someone else.
        static::grant('edit', function ($grant, $user) {
            $grant->whereCan('editOwn')
                  ->where('user_id', $user->id);
        });

        static::check('editOwn', function ($check, $user) {
            $check->whereNull('hide_user_id')
                  ->orWhere('hide_user_id', $user->id);
        });

        static::deleted(function ($post) {
            $post->raise(new Events\PostWasDeleted($post));
        });
    }

    public function discussion()
    {
        return $this->belongsTo('Flarum\Core\Discussions\Discussion', 'discussion_id');
    }

    public function user()
    {
        return $this->belongsTo('Flarum\Core\Users\User', 'user_id');
    }

    public function editUser()
    {
        return $this->belongsTo('Flarum\Core\Users\User', 'edit_user_id');
    }

    public function hideUser()
    {
        return $this->belongsTo('Flarum\Core\Users\User', 'hide_user_id');
    }

    public function getDates()
    {
        return ['time', 'edit_time', 'hide_time'];
    }

    // Terminates the query and returns an array of matching IDs.
    // Example usage: $discussion->posts()->ids();
    public function scopeIds($query)
    {
        return array_map('intval', $query->get(['id'])->fetch('id')->all());
    }

    public function scopeWhereCanView($query, $user = null)
    {
        if (is_null($user)) {
            $user = User::current();
        }
        return $this->scopeWhereCan($query, $user, 'view');
    }

    public function assertCan($user, $permission)
    {
        if (! $this->can($user, $permission)) {
            throw new PermissionDeniedException;
        }
    }

    public function newFromBuilder($attributes = [])
    {
        if (!empty($attributes->type)) {
            $class = 'Flarum\Core\Posts\\'.ucfirst($attributes->type).'Post';
            if (class_exists($class)) {
                $instance = new $class;
                $instance->exists = true;
                $instance->setRawAttributes((array) $attributes, true);
                return $instance;
            }
        }

        return parent::newFromBuilder($attributes);
    }
}
