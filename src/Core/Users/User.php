<?php namespace Flarum\Core\Users;

use Flarum\Core\Groups\Group;
use Flarum\Core\Model;
use Flarum\Core\Notifications\Notification;
use Illuminate\Contracts\Hashing\Hasher;
use Flarum\Core\Formatter\FormatterManager;
use Flarum\Core\Users\Events\UserWasDeleted;
use Flarum\Core\Users\Events\UserWasRegistered;
use Flarum\Core\Users\Events\UserWasRenamed;
use Flarum\Core\Users\Events\UserEmailWasChanged;
use Flarum\Core\Users\Events\UserPasswordWasChanged;
use Flarum\Core\Users\Events\UserBioWasChanged;
use Flarum\Core\Users\Events\UserAvatarWasChanged;
use Flarum\Core\Users\Events\UserWasActivated;
use Flarum\Core\Users\Events\UserEmailChangeWasRequested;
use Flarum\Core\Support\Locked;
use Flarum\Core\Support\VisibleScope;
use Flarum\Core\Support\EventGenerator;
use Flarum\Core\Support\ValidatesBeforeSave;

/**
 * @todo document database columns with @property
 */
class User extends Model
{
    use EventGenerator;
    use Locked;
    use VisibleScope;
    use ValidatesBeforeSave;

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
    protected static $dateAttributes = [
        'join_time',
        'last_seen_time',
        'read_time',
        'notification_read_time'
    ];

    /**
     * The text formatter instance.
     *
     * @var FormatterManager
     */
    protected static $formatter;

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

        static::deleted(function ($user) {
            $user->raise(new UserWasDeleted($user));
        });
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
        $this->password = $password;

        $this->raise(new UserPasswordWasChanged($this));

        return $this;
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
        $this->bio_html = null;

        $this->raise(new UserBioWasChanged($this));

        return $this;
    }

    /**
     * Get the user's bio formatted as HTML.
     *
     * @param string $value
     * @return string
     */
    public function getBioHtmlAttribute($value)
    {
        if ($value === null) {
            $this->bio_html = $value = static::formatBio($this);
            $this->save();
        }

        return $value;
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
        return $this->avatar_path ? app('Flarum\Http\UrlGeneratorInterface')->toAsset('assets/avatars/'.$this->avatar_path) : null;
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

        if (! array_key_exists('permissions', $this->relations)) {
            $this->setRelation('permissions', $this->permissions()->get());
        }

        return (bool) $this->permissions->contains('permission', $permission);
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
     * Define the relationship with the user's activity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activity()
    {
        return $this->hasMany('Flarum\Core\Activity\Activity');
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
     * Set the text formatter instance.
     *
     * @param FormatterManager $formatter
     */
    public static function setFormatter(FormatterManager $formatter)
    {
        static::$formatter = $formatter;
    }

    /**
     * Get the formatted content of a user's bio.
     *
     * @param User $user
     * @return string
     */
    protected static function formatBio(User $user)
    {
        return static::$formatter->format($user->bio, $user);
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
