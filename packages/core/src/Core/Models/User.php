<?php namespace Flarum\Core\Models;

use Illuminate\Contracts\Hashing\Hasher;
use Tobscure\Permissible\Permissible;
use Flarum\Core\Formatter\FormatterManager;
use Flarum\Core\Exceptions\InvalidConfirmationTokenException;
use Flarum\Core\Events\UserWasDeleted;
use Flarum\Core\Events\UserWasRegistered;
use Flarum\Core\Events\UserWasRenamed;
use Flarum\Core\Events\UserEmailWasChanged;
use Flarum\Core\Events\UserPasswordWasChanged;
use Flarum\Core\Events\UserBioWasChanged;
use Flarum\Core\Events\UserWasActivated;
use Flarum\Core\Events\UserEmailWasConfirmed;

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
        'username'          => 'required|unique',
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
    protected $dates = ['join_time', 'last_seen_time', 'read_time'];

    /**
     * The hasher with which to hash passwords.
     *
     * @var \Illuminate\Contracts\Hashing\Hasher
     */
    protected static $hasher;

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

        $user->refreshConfirmationToken();

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
        $this->attributes['password'] = $value ? static::$hasher->make($value) : null;
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
        $this->groups()->sync([3]);

        $this->raise(new UserWasActivated($this));

        return $this;
    }

    /**
     * Check if a given confirmation token is valid for this user.
     *
     * @param  string  $token
     * @return boolean
     */
    public function assertConfirmationTokenValid($token)
    {
        if ($this->is_confirmed ||
            ! $token ||
            $this->confirmation_token !== $token) {
            throw new InvalidConfirmationTokenException;
        }
    }

    /**
     * Generate a new confirmation token for the user.
     *
     * @return $this
     */
    public function refreshConfirmationToken()
    {
        $this->is_confirmed = false;
        $this->confirmation_token = str_random(30);

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
     * Get a list of the user's grantees according to their ID and groups.
     *
     * @return array
     */
    public function getGrantees()
    {
        $grantees = ['group.'.GROUP::GUEST_ID]; // guests
        if ($this->id) {
            $grantees[] = 'user.'.$this->id;
        }
        foreach ($this->groups as $group) {
            $grantees[] = 'group.'.$group->id;
        }

        return $grantees;
    }

    /**
     * Check whether the user has a certain permission based on their groups.
     *
     * @param  string  $permission
     * @param  string  $entity
     * @return boolean
     */
    public function hasPermission($permission, $entity)
    {
        if ($this->isAdmin()) {
            return true;
        }

        $count = $this->permissions()->where('entity', $entity)->where('permission', $permission)->count();

        return (bool) $count;
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
     * Define the relationship with the user's permissions.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function permissions()
    {
        return Permission::whereIn('grantee', $this->getGrantees());
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
