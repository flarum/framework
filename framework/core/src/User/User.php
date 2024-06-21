<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Carbon\Carbon;
use DomainException;
use Flarum\Database\AbstractModel;
use Flarum\Database\ScopeVisibilityTrait;
use Flarum\Discussion\Discussion;
use Flarum\Foundation\EventGeneratorTrait;
use Flarum\Group\Group;
use Flarum\Group\Permission;
use Flarum\Http\AccessToken;
use Flarum\Notification\Notification;
use Flarum\Post\Post;
use Flarum\User\DisplayName\DriverInterface;
use Flarum\User\Event\Activated;
use Flarum\User\Event\AvatarChanged;
use Flarum\User\Event\Deleted;
use Flarum\User\Event\EmailChanged;
use Flarum\User\Event\EmailChangeRequested;
use Flarum\User\Event\PasswordChanged;
use Flarum\User\Event\Registered;
use Flarum\User\Event\Renamed;
use Flarum\User\Exception\NotAuthenticatedException;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Staudenmeir\EloquentEagerLimit\HasEagerLimit;

/**
 * @property int $id
 * @property string $username
 * @property string $display_name
 * @property string $email
 * @property bool $is_email_confirmed
 * @property string $password
 * @property string|null $avatar_url
 * @property array $preferences
 * @property \Carbon\Carbon|null $joined_at
 * @property \Carbon\Carbon|null $last_seen_at
 * @property \Carbon\Carbon|null $marked_all_as_read_at
 * @property \Carbon\Carbon|null $read_notifications_at
 * @property int $discussion_count
 * @property int $comment_count
 * @property-read Collection<int, Group> $groups
 * @property-read Collection<int, Group> $visibleGroups
 * @property-read Collection<int, Notification> $notifications
 * @property-read Collection<int, AccessToken> $accessTokens
 * @property-read Collection<int, Post> $posts
 * @property-read Collection<int, Discussion> $discussions
 * @property-read Collection<int, Discussion> $read
 * @property-read Collection<int, Notification> $unreadNotifications
 * @property-read Collection<int, LoginProvider> $loginProviders
 * @property-read Collection<int, EmailToken> $emailTokens
 * @property-read Collection<int, PasswordToken> $passwordTokens
 */
class User extends AbstractModel
{
    use EventGeneratorTrait;
    use ScopeVisibilityTrait;
    use HasEagerLimit;
    use HasFactory;

    protected $casts = [
        'id' => 'integer',
        'is_email_confirmed' => 'boolean',
        'joined_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'marked_all_as_read_at' => 'datetime',
        'read_notifications_at' => 'datetime',
    ];

    /**
     * An array of permissions that this user has.
     *
     * @var string[]|null
     */
    protected $permissions = null;

    /**
     * An array of callables, through each of which the user's list of groups is passed
     * before being returned.
     *
     * @var callable[]
     */
    protected static array $groupProcessors = [];

    /**
     * An array of registered user preferences. Each preference is defined with
     * a key, and its value is an array containing the following keys:.
     *
     * - transformer: a callback that confines the value of the preference
     * - default: a default value if the preference isn't set
     *
     * @var array<string, array{transformer: callable(mixed): mixed, default: mixed}>
     */
    protected static array $preferences = [];

    /**
     * A driver for getting display names.
     */
    protected static DriverInterface $displayNameDriver;

    /**
     * The hasher with which to hash passwords.
     */
    protected static Hasher $hasher;

    /**
     * The access gate.
     */
    protected static Access\Gate $gate;

    /**
     * Callbacks to check passwords.
     *
     * @var callable[]
     */
    protected static array $passwordCheckers;

    /**
     * Difference from the current `last_seen` attribute value before `updateLastSeen()`
     * will update the attribute on the DB. Measured in seconds.
     */
    private const LAST_SEEN_UPDATE_DIFF = 180;

    public static function boot()
    {
        parent::boot();

        // Don't allow the root admin to be deleted.
        static::deleting(function (self $user) {
            if ($user->id == 1) {
                throw new DomainException('Cannot delete the root admin');
            }
        });

        static::deleted(function (self $user) {
            $user->raise(new Deleted($user));

            Notification::whereSubject($user)->delete();
        });

        static::creating(function (self $user) {
            $user->joined_at = Carbon::now();

            $user->raise(new Registered($user));
        });
    }

    public static function setGate(Access\Gate $gate): void
    {
        static::$gate = $gate;
    }

    public static function setDisplayNameDriver(DriverInterface $driver): void
    {
        static::$displayNameDriver = $driver;
    }

    public static function setPasswordCheckers(array $checkers): void
    {
        static::$passwordCheckers = $checkers;
    }

    public function rename(string $username): static
    {
        if ($username !== $this->username) {
            $oldUsername = $this->username;
            $this->username = $username;

            $this->raise(new Renamed($this, $oldUsername));
        }

        return $this;
    }

    public function changeEmail(string $email): static
    {
        if ($email !== $this->email) {
            $this->email = $email;

            $this->raise(new EmailChanged($this));
        }

        return $this;
    }

    public function requestEmailChange(string $email): static
    {
        if ($email !== $this->email) {
            $this->raise(new EmailChangeRequested($this, $email));
        }

        return $this;
    }

    public function changePassword(string $password): static
    {
        $this->password = $password;

        $this->raise(new PasswordChanged($this));

        return $this;
    }

    /**
     * Set the password attribute, storing it as a hash.
     */
    public function setPasswordAttribute(?string $value): void
    {
        $this->attributes['password'] = $value ? static::$hasher->make($value) : '';
    }

    /**
     * Mark all discussions as read.
     */
    public function markAllAsRead(): static
    {
        $this->marked_all_as_read_at = Carbon::now();

        return $this;
    }

    /**
     * Mark all notifications as read.
     */
    public function markNotificationsAsRead(): static
    {
        $this->read_notifications_at = Carbon::now();

        return $this;
    }

    public function changeAvatarPath(?string $path): static
    {
        $this->avatar_url = $path;

        $this->raise(new AvatarChanged($this));

        return $this;
    }

    public function getAvatarUrlAttribute(?string $value = null): ?string
    {
        if ($value && ! str_contains($value, '://')) {
            return resolve(Factory::class)->disk('flarum-avatars')->url($value);
        }

        return $value;
    }

    public function getDisplayNameAttribute(): string
    {
        return static::$displayNameDriver->displayName($this);
    }

    public function checkPassword(string $password): bool
    {
        $valid = false;

        foreach (static::$passwordCheckers as $checker) {
            $result = $checker($this, $password);

            if ($result === false) {
                return false;
            } elseif ($result === true) {
                $valid = true;
            }
        }

        return $valid;
    }

    public function activate(): static
    {
        if (! $this->is_email_confirmed) {
            $this->is_email_confirmed = true;

            $this->raise(new Activated($this));
        }

        return $this;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return in_array($permission, $this->getPermissions());
    }

    /**
     * Check whether the user has a permission that is like the given string,
     * based on their groups.
     */
    public function hasPermissionLike(string $match): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        foreach ($this->getPermissions() as $permission) {
            if (substr($permission, -strlen($match)) === $match) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the notification types that should be alerted to this user, according
     * to their preferences.
     */
    public function getAlertableNotificationTypes(): array
    {
        $types = array_keys(Notification::getSubjectModels());

        return array_filter($types, [$this, 'shouldAlert']);
    }

    public function getUnreadNotificationCount(): int
    {
        return $this->unreadNotifications()->count();
    }

    /**
     * @return HasMany<Notification>
     */
    protected function unreadNotifications(): HasMany
    {
        return $this->notifications()
            ->whereIn('type', $this->getAlertableNotificationTypes())
            ->whereNull('read_at')
            ->where('is_deleted', false)
            ->whereSubjectVisibleTo($this);
    }

    /**
     * @return Collection
     */
    protected function getUnreadNotifications(): Collection
    {
        return $this->unreadNotifications()->get();
    }

    /**
     * Get the number of new, unseen notifications for the user.
     */
    public function getNewNotificationCount(): int
    {
        return $this->unreadNotifications()
            ->where('created_at', '>', $this->read_notifications_at ?? 0)
            ->count();
    }

    /**
     * Get the values of all registered preferences for this user, by
     * transforming their stored preferences and merging them with the defaults.
     */
    public function getPreferencesAttribute(?string $value): array
    {
        $defaults = array_map(function ($value) {
            return $value['default'];
        }, static::$preferences);

        $user = $value !== null ? Arr::only((array) json_decode($value, true), array_keys(static::$preferences)) : [];

        return array_merge($defaults, $user);
    }

    /**
     * Encode an array of preferences for storage in the database.
     */
    public function setPreferencesAttribute(array $value): void
    {
        $this->attributes['preferences'] = json_encode($value);
    }

    /**
     * Check whether the user should receive an alert for a notification
     * type.
     */
    public function shouldAlert(string $type): bool
    {
        return (bool) $this->getPreference(static::getNotificationPreferenceKey($type, 'alert'));
    }

    /**
     * Check whether the user should receive an email for a notification
     * type.
     */
    public function shouldEmail(string $type): bool
    {
        return (bool) $this->getPreference(static::getNotificationPreferenceKey($type, 'email'));
    }

    public function getPreference(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->preferences, $key, $default);
    }

    public function setPreference(string $key, mixed $value): static
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

    public function updateLastSeen(): static
    {
        $now = Carbon::now();

        if ($this->last_seen_at === null || $this->last_seen_at->diffInSeconds($now) > User::LAST_SEEN_UPDATE_DIFF) {
            $this->last_seen_at = $now;
        }

        return $this;
    }

    public function isAdmin(): bool
    {
        return $this->groups->contains(Group::ADMINISTRATOR_ID);
    }

    /**
     * Check whether the user is a guest.
     */
    public function isGuest(): bool
    {
        return false;
    }

    /**
     * Ensure the current user is allowed to do something.
     *
     * If the condition is not met, an exception will be thrown that signals the
     * lack of permissions. This is about *authorization*, i.e. retrying such a
     * request / operation without a change in permissions (or using another
     * user account) is pointless.
     *
     * @throws PermissionDeniedException
     */
    public function assertPermission(bool $condition): void
    {
        if (! $condition) {
            throw new PermissionDeniedException;
        }
    }

    /**
     * Ensure the given actor is authenticated.
     *
     * This will throw an exception for guest users, signaling that
     * *authorization* failed. Thus, they could retry the operation after
     * logging in (or using other means of authentication).
     *
     * @throws NotAuthenticatedException
     */
    public function assertRegistered(): void
    {
        if ($this->isGuest()) {
            throw new NotAuthenticatedException;
        }
    }

    /**
     * @throws PermissionDeniedException
     */
    public function assertCan(string $ability, mixed $arguments = null): void
    {
        $this->assertPermission(
            $this->can($ability, $arguments)
        );
    }

    /**
     * @throws PermissionDeniedException
     */
    public function assertAdmin(): void
    {
        $this->assertCan('administrate');
    }

    /**
     * @return HasMany<Post>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * @return HasMany<Discussion>
     */
    public function discussions(): HasMany
    {
        return $this->hasMany(Discussion::class);
    }

    /**
     * @return BelongsToMany<Discussion>
     */
    public function read(): BelongsToMany
    {
        return $this->belongsToMany(Discussion::class);
    }

    /**
     * @return BelongsToMany<Group>
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class);
    }

    public function visibleGroups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class)->where('is_hidden', false);
    }

    /**
     * @return HasMany<Notification>
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * @return HasMany<EmailToken>
     */
    public function emailTokens(): HasMany
    {
        return $this->hasMany(EmailToken::class);
    }

    /**
     * @return HasMany<PasswordToken>
     */
    public function passwordTokens(): HasMany
    {
        return $this->hasMany(PasswordToken::class);
    }

    /**
     * Define the relationship with the permissions of all the groups that
     * the user is in.
     *
     * @return Builder
     */
    public function permissions(): Builder
    {
        $groupIds = [Group::GUEST_ID];

        // If a user's account hasn't been activated, they are essentially no
        // more than a guest. If they are activated, we can give them the
        // standard 'member' group, as well as any other groups they've been
        // assigned to.
        if ($this->is_email_confirmed) {
            $groupIds = array_merge($groupIds, [Group::MEMBER_ID], $this->groups->pluck('id')->all());
        }

        foreach (static::$groupProcessors as $processor) {
            $groupIds = $processor($this, $groupIds);
        }

        return Permission::query()->whereIn('group_id', $groupIds);
    }

    /**
     * Get a list of permissions that the user has.
     *
     * @return string[]
     */
    public function getPermissions(): array
    {
        if (is_null($this->permissions)) {
            $this->permissions = $this->permissions()->pluck('permission')->all();
        }

        return $this->permissions;
    }

    /**
     * @return HasMany<AccessToken>
     */
    public function accessTokens(): HasMany
    {
        return $this->hasMany(AccessToken::class);
    }

    /**
     * @return HasMany<LoginProvider>
     */
    public function loginProviders(): HasMany
    {
        return $this->hasMany(LoginProvider::class);
    }

    public function can(string $ability, mixed $arguments = null): bool
    {
        return static::$gate->allows($this, $ability, $arguments);
    }

    public function cannot(string $ability, mixed $arguments = null): bool
    {
        return ! $this->can($ability, $arguments);
    }

    /**
     * Set the hasher with which to hash passwords.
     *
     * @internal
     */
    public static function setHasher(Hasher $hasher): void
    {
        static::$hasher = $hasher;
    }

    /**
     * Register a preference with a transformer and a default value.
     *
     * @internal
     */
    public static function registerPreference(string $key, callable $transformer = null, mixed $default = null): void
    {
        static::$preferences[$key] = compact('transformer', 'default');
    }

    /**
     * Register a callback that processes a user's list of groups.
     *
     * @internal
     */
    public static function addGroupProcessor(callable $callback): void
    {
        static::$groupProcessors[] = $callback;
    }

    /**
     * Get the key for a preference which flags whether the user will
     * receive a notification for $type via $method.
     */
    public static function getNotificationPreferenceKey(string $type, string $method): string
    {
        return 'notify_'.$type.'_'.$method;
    }

    public function refreshCommentCount(): static
    {
        $this->comment_count = $this->posts()
            ->where('type', 'comment')
            ->where('is_private', false)
            ->count();

        return $this;
    }

    public function refreshDiscussionCount(): static
    {
        $this->discussion_count = $this->discussions()
            ->where('is_private', false)
            ->count();

        return $this;
    }

    /**
     * Set the value of a notification preference.
     */
    public function setNotificationPreference(string $type, string $method, bool $value): static
    {
        $this->setPreference(static::getNotificationPreferenceKey($type, $method), $value);

        return $this;
    }
}
