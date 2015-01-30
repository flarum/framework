<?php namespace Flarum\Core\Users;

use Illuminate\Auth\UserInterface;
use Illuminate\Auth\UserTrait;
use Illuminate\Auth\Reminders\RemindableInterface;
use Illuminate\Auth\Reminders\RemindableTrait;
use Auth;
use DB;
use Event;
use Hash;
use Tobscure\Permissible\Permissible;
use Laracasts\Commander\Events\EventGenerator;

use Flarum\Core\Entity;
use Flarum\Core\Groups\Group;
use Flarum\Core\Support\Exceptions\PermissionDeniedException;

class User extends Entity implements UserInterface, RemindableInterface
{
    use EventGenerator;
    use Permissible;
    
    use UserTrait, RemindableTrait;

    protected static $rules = [
        'username'          => 'required|username|unique',
        'email'             => 'required|email|unique',
        'password'          => 'required',
        'join_time'         => 'date',
        'last_seen_time'    => 'date',
        'discussions_count' => 'integer',
        'posts_count'       => 'integer',
    ];

    protected $table = 'users';

    protected $hidden = ['password'];
    
    public static function boot()
    {
        parent::boot();

        static::grant(function ($grant, $user, $permission) {
            return app('flarum.permissions')->granted($user, $permission, 'forum');
        });

        // Grant view access to a user if the user can view the forum.
        static::grant('view', function ($grant, $user) {
            return app('forum')->can($user, 'view');
        });

        // Allow a user to edit their own account.
        static::grant('edit', function ($grant, $user) {
            $grant->where('id', $user->id);
        });

        static::deleted(function ($user) {
            $user->raise(new Events\UserWasDeleted($user));
        });
    }

    public function setUsernameAttribute($username)
    {
        $this->attributes['username'] = $username;
        $this->raise(new Events\UserWasRenamed($this));
    }

    public function setEmailAttribute($email)
    {
        $this->attributes['email'] = $email;
        $this->raise(new Events\EmailWasChanged($this));
    }

    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = Hash::make($password);
        $this->raise(new Events\PasswordWasChanged($this));
    }

    public static function register($username, $email, $password)
    {
        $user = new static;

        $user->username  = $username;
        $user->email     = $email;
        $user->password  = $password;
        $user->join_time = time();

        $user->raise(new Events\UserWasRegistered($user));

        return $user;
    }

    public function getDates()
    {
        return ['join_time', 'last_seen_time'];
    }

    public function getAvatarUrlAttribute()
    {
        return '';
    }

    public static function current()
    {
        static $current = null;

        if (Auth::guest()) {
            if (! isset($current)) {
                $current = new Guest;
            }
            return $current;
        }

        return Auth::user();
    }

    public function getGrantees()
    {
        $grantees = ['group.2']; // guests
        if ($this->id) {
            $grantees[] = 'user.'.$this->id;
        }
        foreach ($this->groups as $group) {
            $grantees[] = 'group.'.$group->id;
        }

        /*
			TODO: maybe we should rethink how groups and permissions work a bit.

			Permissions table could be like:
				GRANTEE 		ENTITY 			PERMISSION
				all 			forum 			view
				all 			discussion		view
				all 			post 			view
				all 			user 			view
				user 			discussion 		create
				user 			discussion 		reply
				group.1			forum			administrate
				group.1			post 			delete
				etc

			sit on it. what about for suspended users? we could hook in and remove the 'user' grantee?
		*/

        return $grantees;
    }

    public function permission($permission, $user = null)
    {
        if (is_null($user)) {
            $user = User::current();
        }
        return $this->can($user, $permission);
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

    public function assertCan($user, $permission)
    {
        if (! $this->can($user, $permission)) {
            throw new PermissionDeniedException;
        }
    }

    // public function granted($permission, $scope)
    // {
    //     return isset($this->permissions[$scope]) && in_array($permission, $this->permissions[$scope]);
    // }

    // public function mustBeAbleTo($permission, $scope = 'forum', $entity = null)
    // {
    //     if (! $this->can($permission, $scope, $entity)) {
    //         throw new PermissionDeniedException;
    //     }
    // }

    public function admin()
    {
        return $this->can('administrate');
    }

    public function isAdmin()
    {
        return $this->groups->contains(1);
    }

    public function guest()
    {
        return false;
    }

    public function groups()
    {
        return $this->belongsToMany('Flarum\Core\Groups\Group', 'users_groups');
    }

    public function activity()
    {
        return $this->hasMany('Flarum\Core\Activity\Activity');
    }

    public function setRememberToken($value)
    {
        return;
    }
}
