<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\Database\ScopeVisibilityTrait;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @property int $id
 * @property string $token
 * @property int $user_id
 * @property Carbon $created_at
 * @property Carbon|null $last_activity_at
 * @property string $type
 * @property string $title
 * @property string|null $last_ip_address
 * @property string|null $last_user_agent
 * @property-read \Flarum\User\User|null $user
 */
class AccessToken extends AbstractModel
{
    use ScopeVisibilityTrait;
    use HasFactory;

    protected $table = 'access_tokens';

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'created_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    /**
     * A map of access token types, as specified in the `type` column, to their classes.
     */
    protected static array $models = [];

    /**
     * The type of token this is, to be stored in the access tokens table.
     *
     * Should be overwritten by subclasses with the value that is
     * to be stored in the database, which will then be used for
     * mapping the hydrated model instance to the proper subtype.
     */
    public static string $type = '';

    /**
     * How long this access token should be valid from the time of last activity.
     * This value will be used in the validity and expiration checks.
     * @var int Lifetime in seconds. Zero means it will never expire.
     */
    protected static int $lifetime = 0;

    /**
     * Difference from the current `last_activity_at` attribute value before `updateLastSeen()`
     * will update the attribute on the DB. Measured in seconds.
     */
    private const LAST_ACTIVITY_UPDATE_DIFF = 90;

    public ?array $uniqueKeys = ['token'];

    /**
     * Generate an access token for the specified user.
     */
    public static function generate(int $userId): static
    {
        $token = static::make($userId);
        $token->save();

        return $token;
    }

    public static function make(int $userId): static
    {
        if (static::class === self::class) {
            throw new \Exception('Use of AccessToken::generate() is not allowed: use the `generate` method on one of the subclasses.');
        } else {
            $token = new static;
            $token->type = static::$type;
        }

        $token->token = Str::random(40);
        $token->user_id = $userId;
        $token->created_at = Carbon::now();
        $token->last_activity_at = Carbon::now();

        return $token;
    }

    /**
     * Update the time of last usage of a token.
     * If a request object is provided, the IP address and User Agent will also be logged.
     */
    public function touch($attribute = null, ServerRequestInterface $request = null): bool
    {
        $now = Carbon::now();

        if ($this->last_activity_at === null || $this->last_activity_at->diffInSeconds($now, true) > AccessToken::LAST_ACTIVITY_UPDATE_DIFF) {
            $this->last_activity_at = $now;
        }

        if ($request) {
            $this->last_ip_address = $request->getAttribute('ipAddress');
            // We truncate user agent so it fits in the database column
            // The length is hard-coded as the column length
            // It seems like MySQL or Laravel already truncates values, but we'll play safe and do it ourselves
            $agent = Arr::get($request->getServerParams(), 'HTTP_USER_AGENT');
            $this->last_user_agent = substr($agent ?? '', 0, 255);
        } else {
            // If no request is provided, we set the values back to null
            // That way the values always match with the date logged in last_activity
            $this->last_ip_address = null;
            $this->last_user_agent = null;
        }

        return $this->save();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Filters which tokens are valid at the given date for this particular token type.
     * Uses the static::$lifetime value by default, can be overridden by children classes.
     */
    protected static function scopeValid(Builder $query, Carbon $date): void
    {
        if (static::$lifetime > 0) {
            $query->where('last_activity_at', '>', $date->clone()->subSeconds(static::$lifetime));
        }
    }

    /**
     * Filters which tokens are expired at the given date and ready for garbage collection.
     * Uses the static::$lifetime value by default, can be overridden by children classes.
     */
    protected static function scopeExpired(Builder $query, Carbon $date): void
    {
        if (static::$lifetime > 0) {
            $query->where('last_activity_at', '<', $date->clone()->subSeconds(static::$lifetime));
        } else {
            $query->whereRaw('FALSE');
        }
    }

    /**
     * Shortcut to find a valid token.
     * @param string $token Token as sent by the user. We allow non-string values like null so we can directly feed any value from a request.
     */
    public static function findValid(string $token): ?AccessToken
    {
        return static::query()->whereValid()->where('token', $token)->first();
    }

    /**
     * This query scope is intended to be used on the base AccessToken object to query for valid tokens of any type.
     */
    public function scopeWhereValid(Builder $query, ?Carbon $date = null): void
    {
        if (is_null($date)) {
            $date = Carbon::now();
        }

        $query->where(function (Builder $query) use ($date) {
            foreach ($this->getModels() as $model) {
                $query->orWhere(function (Builder $query) use ($model, $date) {
                    $query->where('type', $model::$type);
                    $model::scopeValid($query, $date);
                });
            }
        });
    }

    /**
     * This query scope is intended to be used on the base AccessToken object to query for expired tokens of any type.
     */
    public function scopeWhereExpired(Builder $query, Carbon $date = null): void
    {
        if (is_null($date)) {
            $date = Carbon::now();
        }

        $query->where(function (Builder $query) use ($date) {
            foreach ($this->getModels() as $model) {
                $query->orWhere(function (Builder $query) use ($model, $date) {
                    $query->where('type', $model::$type);
                    $model::scopeExpired($query, $date);
                });
            }
        });
    }

    /**
     * Create a new model instance according to the access token type.
     *
     * @param array $attributes
     * @param string|null $connection
     * @return static|object
     */
    public function newFromBuilder($attributes = [], $connection = null)
    {
        $attributes = (array) $attributes;

        if (! empty($attributes['type'])
            && isset(static::$models[$attributes['type']])
            && class_exists($class = static::$models[$attributes['type']])
        ) {
            /** @var AccessToken $instance */
            $instance = new $class;
            $instance->exists = true;
            $instance->setRawAttributes($attributes, true);
            $instance->setConnection($connection ?: $this->connection);

            return $instance;
        }

        return parent::newFromBuilder($attributes, $connection);
    }

    /**
     * Get the type-to-model map.
     */
    public static function getModels(): array
    {
        return static::$models;
    }

    /**
     * Set the model for the given access token type.
     *
     * @param class-string<self> $model The class name of the model for that type.
     */
    public static function setModel(string $type, string $model): void
    {
        static::$models[$type] = $model;
    }
}
