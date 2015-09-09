<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Users;

use Flarum\Core;
use Flarum\Core\Groups\Group;
use Flarum\Core\Model;
use Flarum\Core\Notifications\Notification;
use Flarum\Events\RegisterUserPreferences;
use Illuminate\Contracts\Hashing\Hasher;
use Flarum\Core\Formatter\FormatterManager;
use Flarum\Events\UserWasDeleted;
use Flarum\Events\PostWasDeleted;
use Flarum\Events\UserWasRegistered;
use Flarum\Events\UserWasRenamed;
use Flarum\Events\UserEmailWasChanged;
use Flarum\Events\UserPasswordWasChanged;
use Flarum\Events\UserBioWasChanged;
use Flarum\Events\UserAvatarWasChanged;
use Flarum\Events\UserWasActivated;
use Flarum\Events\UserEmailChangeWasRequested;
use Flarum\Events\GetUserGroups;
use Flarum\Core\Support\Locked;
use Flarum\Core\Support\VisibleScope;
use Flarum\Core\Support\EventGenerator;
use Flarum\Core\Support\ValidatesBeforeSave;
use Flarum\Core\Exceptions\ValidationException;

/**
 * @todo document database columns with @property
 */
class User extends Model
{
    use EventGenerator;
    use Locked;
    use ValidatesBeforeSave;
    use VisibleScope;

    /**
     * {@inheritdoc}
     */
    protected $table = 'users';

    /**
     * The validation rules for this model.
     *
     * @var array
     */
    protected $rules = [
        'username'          => 'required|alpha_dash|unique',
        'email'             => 'required|email|unique',
        'password'          => 'required',
        'join_time'         => 'date',
        'last_seen_time'    => 'date',
        'discussions_count' => 'integer',
        'posts_count'       => 'integer',
    ];

    /**
     * {@inheritdoc}
     */
    protected $dates = [
        'join_time',
        'last_seen_time',
        'read_time',
        'notification_read_time'
    ];

    /**
     * An array of permissions that this user has.
     *
     * @var string[]|null
     */
    protected $permissions = null;

    /**
     * The hasher with which to hash passwords.
     *
     * @var \Illuminate\Contracts\Hashing\Hasher
     */
    protected static $hasher;

    /**
     * An array of registered user preferences. Each preference is defined with
     * a key, and its value is an array containing the following keys:
     *
     * - transformer: a callback that confines the value of the preference
     * - default: a default value if the preference isn't set
     *
     * @var array
     */
    protected static $preferences = [];

    /**
     * Boot the model.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        // Don't allow the root admin to be deleted.
        static::deleting(function (User $user) {
            if ($user->id == 1) {
                throw new DomainException('Cannot delete the root admin');
            }
        });

        static::deleted(function ($user) {
            $user->raise(new UserWasDeleted($user));

            // Delete all of the posts by the user. Before we delete them
            // in a big batch query, we will loop through them and raise a
            // PostWasDeleted event for each post.
            $posts = $user->posts()->allTypes();

            foreach ($posts->get() as $post) {
                $user->raise(new PostWasDeleted($post));
            }

            $posts->delete();

            $user->read()->detach();
            $user->groups()->detach();
            $user->accessTokens()->delete();
            $user->notifications()->delete();
        });

        event(new RegisterUserPreferences);
    }

    /**
     * Register a new user.
     *
     * @param string $username
     * @param string $email
     * @param string $password
     * @return static
     */
    public static function register($username, $email, $password)
    {
        $user = new static;

        $user->assertValidPassword($password);

        $user->username  = $username;
        $user->email     = $email;
        $user->password  = $password;
        $user->join_time = time();

        $user->raise(new UserWasRegistered($user));

        return $user;
    }

    /**
     * Rename the user.
     *
     * @param string $username
     * @return $this
     */
    public function rename($username)
    {
        if ($username !== $this->username) {
            $this->username = $username;

            $this->raise(new UserWasRenamed($this));
        }

        return $this;
    }

    /**
     * Change the user's email.
     *
     * @param string $email
     * @return $this
     */
    public function changeEmail($email)
    {
        if ($email !== $this->email) {
            $this->email = $email;

            $this->raise(new UserEmailWasChanged($this));
        }

        return $this;
    }

    /**
     * Request that the user's email be changed.
     *
     * @param string $email
     * @return $this
     */
    public function requestEmailChange($email)
    {
        if ($email !== $this->email) {
            $validator = $this->makeValidator();

            $validator->setRules(array_only($validator->getRules(), 'email'));
            $validator->setData(compact('email'));

            if ($validator->fails()) {
                $this->throwValidationException($validator);
            }

            $this->raise(new UserEmailChangeWasRequested($this, $email));
        }

        return $this;
    }

    /**
     * Change the user's password.
     *
     * @param string $password
     * @return $this
     */
    public function changePassword($password)
    {
        $this->assertValidPassword($password);

        $this->password = $password;

        $this->raise(new UserPasswordWasChanged($this));

        return $this;
    }

    /**
     * Validate password input.
     *
     * @param string $password
     * @return void
     * @throws \Flarum\Core\Exceptions\ValidationException
     */
    protected function assertValidPassword($password)
    {
        if (strlen($password) < 8) {
            throw new ValidationException(['password' => 'Password must be at least 8 characters']);
        }
    }

    /**
     * Set the password attribute, storing it as a hash.
     *
     * @param string $value
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = $value ? static::$hasher->make($value) : '';
    }

    /**
     * Change the user's bio.
     *
     * @param string $bio
     * @return $this
     */
    public function changeBio($bio)
    {
        $this->bio = $bio;

        $this->raise(new UserBioWasChanged($this));

        return $this;
    }

    /**
     * Mark all discussions as read.
     *
     * @return $this
     */
    public function markAllAsRead()
    {
        $this->read_time = time();

        return $this;
    }

    /**
     * Mark all notifications as read.
     *
     * @return $this
     */
    public function markNotificationsAsRead()
    {
        $this->notification_read_time = time();

        return $this;
    }

    /**
     * Change the path of the user avatar.
     *
     * @param string $path
     * @return $this
     */
    public function changeAvatarPath($path)
    {
        $this->avatar_path = $path;

        $this->raise(new UserAvatarWasChanged($this));

        return $this;
    }

    /**
     * Get the URL of the user's avatar.
     *
     * @todo Allow different storage locations to be used
     * @return string
     */
    public function getAvatarUrlAttribute()
    {
        $urlGenerator = app('Flarum\Http\UrlGeneratorInterface');

        return $this->avatar_path ? $urlGenerator->toAsset('avatars/'.$this->avatar_path) : null;
    }

    /**
     * Get the user's locale, falling back to the forum's default if they
     * haven't set one.
     *
     * @param string $value
     * @return string
     */
    public function getLocaleAttribute($value)
    {
        return $value ?: Core::config('locale', 'en');
    }

    /**
     * Check if a given password matches the user's password.
     *
     * @param string $password
     * @return boolean
     */
    public function checkPassword($password)
    {
        return static::$hasher->check($password, $this->password);
    }

    /**
     * Activate the user's account.
     *
     * @return $this
     */
    public function activate()
    {
        $this->is_activated = true;

        $this->raise(new UserWasActivated($this));

        return $this;
    }

    /**
     * Check whether the user has a certain permission based on their groups.
     *
     * @param string $permission
     * @return boolean
     */
    public function hasPermission($permission)
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (is_null($this->permissions)) {
            $this->permissions = $this->getPermissions();
        }

        return in_array($permission, $this->permissions);
    }

    /**
     * Check whether the user has a permission that is like the given string,
     * based on their groups.
     *
     * @param string $match
     * @return boolean
     */
    public function hasPermissionLike($match)
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (is_null($this->permissions)) {
            $this->permissions = $this->getPermissions();
        }

        foreach ($this->permissions as $permission) {
            if (substr($permission, -strlen($match)) === $match) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the notification types that should be alerted to this user, according
     * to their preferences.
     *
     * @return array
     */
    public function getAlertableNotificationTypes()
    {
        $types = array_keys(Notification::getSubjectModels());

        return array_filter($types, [$this, 'shouldAlert']);
    }

    /**
     * Get the number of unread notifications for the user.
     *
     * @return mixed
     */
    public function getUnreadNotificationsCount()
    {
        return $this->notifications()
            ->whereIn('type', $this->getAlertableNotificationTypes())
            ->where('time', '>', $this->notification_read_time ?: 0)
            ->where('is_read', 0)
            ->where('is_deleted', 0)
            ->count($this->getConnection()->raw('DISTINCT type, subject_id'));
    }

    /**
     * Get the values of all registered preferences for this user, by
     * transforming their stored preferences and merging them with the defaults.
     *
     * @param string $value
     * @return array
     */
    public function getPreferencesAttribute($value)
    {
        $defaults = array_build(static::$preferences, function ($key, $value) {
            return [$key, $value['default']];
        });

        $user = array_only((array) json_decode($value, true), array_keys(static::$preferences));

        return array_merge($defaults, $user);
    }

    /**
     * Encode an array of preferences for storage in the database.
     *
     * @param mixed $value
     */
    public function setPreferencesAttribute($value)
    {
        $this->attributes['preferences'] = json_encode($value);
    }

    /**
     * Check whether or not the user should receive an alert for a notification
     * type.
     *
     * @param string $type
     * @return bool
     */
    public function shouldAlert($type)
    {
        return (bool) $this->getPreference(static::getNotificationPreferenceKey($type, 'alert'));
    }

    /**
     * Check whether or not the user should receive an email for a notification
     * type.
     *
     * @param string $type
     * @return bool
     */
    public function shouldEmail($type)
    {
        return (bool) $this->getPreference(static::getNotificationPreferenceKey($type, 'email'));
    }

    /**
     * Get the value of a preference for this user.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getPreference($key, $default = null)
    {
        return array_get($this->preferences, $key, $default);
    }

    /**
     * Set the value of a preference for this user.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setPreference($key, $value)
    {
        if (isset(static::$preferences[$key])) {
            $preferences = $this->preferences;

            if (! is_null($transformer = static::$preferences[$key]['transformer'])) {
                $preferences[$key] = call_user_func($transformer, $value);
            } else {
                $preferences[$key] = $value;
            }

            $this->preferences = $preferences;
        }

        return $this;
    }

    /**
     * Set the user as being last seen just now.
     *
     * @return $this
     */
    public function updateLastSeen()
    {
        $this->last_seen_time = time();

        return $this;
    }

    /**
     * Check whether or not the user is an administrator.
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->groups->contains(Group::ADMINISTRATOR_ID);
    }

    /**
     * Check whether or not the user is a guest.
     *
     * @return bool
     */
    public function isGuest()
    {
        return false;
    }

    /**
     * Define the relationship with the user's posts.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts()
    {
        return $this->hasMany('Flarum\Core\Posts\Post');
    }

    /**
     * Define the relationship with the user's read discussions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function read()
    {
        return $this->belongsToMany('Flarum\Core\Discussions\Discussion', 'users_discussions');
    }

    /**
     * Define the relationship with the user's groups.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function groups()
    {
        return $this->belongsToMany('Flarum\Core\Groups\Group', 'users_groups');
    }

    /**
     * Define the relationship with the user's notifications.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notifications()
    {
        return $this->hasMany('Flarum\Core\Notifications\Notification');
    }

    /**
     * Define the relationship with the permissions of all of the groups that
     * the user is in.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function permissions()
    {
        $groupIds = [Group::GUEST_ID];

        // If a user's account hasn't been activated, they are essentially no
        // more than a guest. If they are activated, we can give them the
        // standard 'member' group, as well as any other groups they've been
        // assigned to.
        if ($this->is_activated) {
            $groupIds = array_merge($groupIds, [Group::MEMBER_ID], $this->groups->lists('id')->all());
        }

        event(new GetUserGroups($this, $groupIds));

        return Permission::whereIn('group_id', $groupIds);
    }

    /**
     * Get a list of permissions that the user has.
     *
     * @return string[]
     */
    public function getPermissions()
    {
        return $this->permissions()->lists('permission')->all();
    }

    /**
     * Define the relationship with the user's access tokens.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function accessTokens()
    {
        return $this->hasMany('Flarum\Api\AccessToken');
    }

    /**
     * Set the hasher with which to hash passwords.
     *
     * @param \Illuminate\Contracts\Hashing\Hasher  $hasher
     */
    public static function setHasher(Hasher $hasher)
    {
        static::$hasher = $hasher;
    }

    /**
     * Register a preference with a transformer and a default value.
     *
     * @param string $key
     * @param callable $transformer
     * @param mixed $default
     */
    public static function addPreference($key, callable $transformer = null, $default = null)
    {
        static::$preferences[$key] = compact('transformer', 'default');
    }

    /**
     * Get the key for a preference which flags whether or not the user will
     * receive a notification for $type via $method.
     *
     * @param string $type
     * @param string $method
     * @return string
     */
    public static function getNotificationPreferenceKey($type, $method)
    {
        return 'notify_'.$type.'_'.$method;
    }
}
