<?php namespace Flarum\Core\Models;

use Illuminate\Contracts\Hashing\Hasher;
use Tobscure\Permissible\Permissible;
use Flarum\Core\Formatter\FormatterManager;
use Flarum\Core\Events\UserWasDeleted;
use Flarum\Core\Events\UserWasRegistered;
use Flarum\Core\Events\UserWasRenamed;
use Flarum\Core\Events\UserEmailWasChanged;
use Flarum\Core\Events\UserPasswordWasChanged;
use Flarum\Core\Events\UserBioWasChanged;
use Flarum\Core\Events\UserAvatarWasChanged;
use Flarum\Core\Events\UserWasActivated;
use Flarum\Core\Events\UserEmailWasConfirmed;
use Flarum\Core\Events\UserEmailChangeWasRequested;

class User extends Model
{
    use Permissible;

    /**
     * The text formatter instance.
     *
     * @var \Flarum\Core\Formatter\Formatter
     */
    protected static $formatter;

    /**
     * The validation rules for this model.
     *
     * @var array
     */
    public static $rules = [
        'username'          => 'required|alpha_dash|unique',
        'email'             => 'required|email|unique',
        'password'          => 'required',
        'join_time'         => 'date',
        'last_seen_time'    => 'date',
        'discussions_count' => 'integer',
        'posts_count'       => 'integer',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['join_time', 'last_seen_time', 'read_time', 'notification_read_time'];

    /**
     * The hasher with which to hash passwords.
     *
     * @var \Illuminate\Contracts\Hashing\Hasher
     */
    protected static $hasher;

    protected static $preferences = [];

    /**
     * Raise an event when a post is deleted.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::deleted(function ($user) {
            $user->raise(new UserWasDeleted($user));
        });
    }

    /**
     * Register a new user.
     *
     * @param  string  $username
     * @param  string  $email
     * @param  string  $password
     * @return static
     */
    public static function register($username, $email, $password)
    {
        $user = new static;

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
     * @param  string  $username
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
     * @param  string  $email
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

    public function requestEmailChange($email)
    {
        if ($email !== $this->email) {
            $validator = static::$validator->make(
                compact('email'),
                $this->expandUniqueRules(array_only(static::$rules, 'email'))
            );

            if ($validator->fails()) {
                $this->throwValidationFailureException($validator);
            }

            $this->raise(new UserEmailChangeWasRequested($this, $email));
        }

        return $this;
    }

    /**
     * Change the user's password.
     *
     * @param  string  $password
     * @return $this
     */
    public function changePassword($password)
    {
        $this->password = $password;

        $this->raise(new UserPasswordWasChanged($this));

        return $this;
    }

    /**
     * Store the password as a hash.
     *
     * @param  string  $value
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = $value ? static::$hasher->make($value) : '';
    }

    /**
     * Change the user's bio.
     *
     * @param  string  $bio
     * @return $this
     */
    public function changeBio($bio)
    {
        $this->bio = $bio;

        $this->raise(new UserBioWasChanged($this));

        return $this;
    }

    /**
     * Get the content formatter as HTML.
     *
     * @param  string  $value
     * @return string
     */
    public function getBioHtmlAttribute($value)
    {
        if (! $value) {
            $this->bio_html = $value = static::formatBio($this->bio);
            $this->save();
        }

        return $value;
    }

    /**
     * Mark all discussions as read by setting the user's read_time.
     *
     * @return $this
     */
    public function markAllAsRead()
    {
        $this->read_time = time();

        return $this;
    }

    /**
     * Mark all notifications as read by setting the user's notification_read_time.
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
     * @return string
     */
    public function getAvatarUrlAttribute()
    {
        return $this->avatar_path ? asset('assets/avatars/'.$this->avatar_path) : null;
    }

    /**
     * Check if a given password matches the user's password.
     *
     * @param  string  $password
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
     * Confirm the user's email.
     *
     * @return $this
     */
    public function confirmEmail()
    {
        $this->is_confirmed = true;
        $this->confirmation_token = null;

        $this->raise(new UserEmailWasConfirmed($this));

        return $this;
    }

    /**
     * Check whether the user has a certain permission based on their groups.
     *
     * @param  string  $permission
     * @param  string  $entity
     * @return boolean
     */
    public function hasPermission($permission)
    {
        if ($this->isAdmin()) {
            return true;
        }

        $count = $this->permissions()->where('permission', $permission)->count();

        return (bool) $count;
    }

    public function getUnreadNotificationsCount()
    {
        $types = array_keys(Notification::getTypes());

        return $this->notifications()
            ->whereIn('type', array_filter($types, [$this, 'shouldAlert']))
            ->where('time', '>', $this->notification_read_time ?: 0)
            ->where('is_read', 0)
            ->count($this->getConnection()->raw('DISTINCT type, subject_id'));
    }

    public function getPreferencesAttribute($value)
    {
        $defaults = [];

        foreach (static::$preferences as $k => $v) {
            $defaults[$k] = $v['default'];
        }

        return array_merge($defaults, array_only((array) json_decode($value, true), array_keys(static::$preferences)));
    }

    public function setPreferencesAttribute($value)
    {
        $this->attributes['preferences'] = json_encode($value);
    }

    public static function registerPreference($key, $transformer = null, $default = null)
    {
        static::$preferences[$key] = [
            'transformer' => $transformer,
            'default' => $default
        ];
    }

    public static function notificationPreferenceKey($type, $method)
    {
        return 'notify_'.$type.'_'.$method;
    }

    public function shouldAlert($type)
    {
        return $this->preference(static::notificationPreferenceKey($type, 'alert'));
    }

    public function shouldEmail($type)
    {
        return $this->preference(static::notificationPreferenceKey($type, 'email'));
    }

    public function preference($key, $default = null)
    {
        return array_get($this->preferences, $key, $default);
    }

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

    public function updateLastSeen()
    {
        $this->last_seen_time = time();

        return $this;
    }

    /**
     * Check whether or not the user is an administrator.
     *
     * @return boolean
     */
    public function isAdmin()
    {
        return $this->groups->contains(Group::ADMINISTRATOR_ID);
    }

    /**
     * Check whether or not the user is a guest.
     *
     * @return boolean
     */
    public function isGuest()
    {
        return false;
    }

    /**
     * Define the relationship with the user's activity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activity()
    {
        return $this->hasMany('Flarum\Core\Models\Activity');
    }

    /**
     * Define the relationship with the user's groups.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function groups()
    {
        return $this->belongsToMany('Flarum\Core\Models\Group', 'users_groups');
    }

    /**
     * Define the relationship with the user's notifications.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notifications()
    {
        return $this->hasMany('Flarum\Core\Models\Notification');
    }

    /**
     * Define the relationship with the user's permissions.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function permissions()
    {
        $groupIds = [Group::GUEST_ID];

        if ($this->is_activated) {
            $groupIds = array_merge($groupIds, [Group::MEMBER_ID], $this->groups->lists('id'));
        }

        return Permission::whereIn('group_id', $groupIds);
    }

    /**
     * Define the relationship with the user's access tokens.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function accessTokens()
    {
        return $this->hasMany('Flarum\Core\Models\AccessToken');
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
    protected static function formatBio($content)
    {
        return static::$formatter->format($content);
    }
}
