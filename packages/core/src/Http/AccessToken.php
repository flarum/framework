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
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
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
 * @property string $last_ip_address
 * @property string $last_user_agent
 * @property \Flarum\User\User|null $user
 */
class AccessToken extends AbstractModel
{
    protected $table = 'access_tokens';

    protected $dates = [
        'created_at',
        'last_activity_at',
    ];

    /**
     * A map of access token types, as specified in the `type` column, to their classes.
     *
     * @var array
     */
    protected static $models = [];

    /**
     * The type of token this is, to be stored in the access tokens table.
     *
     * Should be overwritten by subclasses with the value that is
     * to be stored in the database, which will then be used for
     * mapping the hydrated model instance to the proper subtype.
     *
     * @var string
     */
    public static $type = '';

    /**
     * How long this access token should be valid from the time of last activity.
     * This value will be used in the validity and expiration checks.
     * @var int Lifetime in seconds. Zero means it will never expire.
     */
    protected static $lifetime = 0;

    /**
     * Difference from the current `last_activity_at` attribute value before `updateLastSeen()`
     * will update the attribute on the DB. Measured in seconds.
     */
    private const LAST_ACTIVITY_UPDATE_DIFF = 90;

    /**
     * Generate an access token for the specified user.
     *
     * @param int $userId
     * @return static
     */
    public static function generate($userId)
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
        $token->save();

        return $token;
    }

    /**
     * Update the time of last usage of a token.
     * If a request object is provided, the IP address and User Agent will also be logged.
     * @param ServerRequestInterface|null $request
     * @return bool
     */
    public function touch(ServerRequestInterface $request = null)
    {
        $now = Carbon::now();

        if ($this->last_activity_at === null || $this->last_activity_at->diffInSeconds($now) > AccessToken::LAST_ACTIVITY_UPDATE_DIFF) {
            $this->last_activity_at = $now;
        }

        if ($request) {
            $this->last_ip_address = $request->getAttribute('ipAddress');
            // We truncate user agent so it fits in the database column
            // The length is hard-coded as the column length
            // It seems like MySQL or Laravel already truncates values, but we'll play safe and do it ourselves
            $this->last_user_agent = substr(Arr::get($request->getServerParams(), 'HTTP_USER_AGENT'), 0, 255);
        } else {
            // If no request is provided, we set the values back to null
            // That way the values always match with the date logged in last_activity
            $this->last_ip_address = null;
            $this->last_user_agent = null;
        }

        return $this->save();
    }

    /**
     * Define the relationship with the owner of this access token.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Filters which tokens are valid at the given date for this particular token type.
     * Uses the static::$lifetime value by default, can be overridden by children classes.
     * @param Builder $query
     * @param Carbon $date
     */
    protected static function scopeValid(Builder $query, Carbon $date)
    {
        if (static::$lifetime > 0) {
            $query->where('last_activity_at', '>', $date->clone()->subSeconds(static::$lifetime));
        }
    }

    /**
     * Filters which tokens are expired at the given date and ready for garbage collection.
     * Uses the static::$lifetime value by default, can be overridden by children classes.
     * @param Builder $query
     * @param Carbon $date
     */
    protected static function scopeExpired(Builder $query, Carbon $date)
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
     * @return AccessToken|null
     */
    public static function findValid($token): ?AccessToken
    {
        return static::query()->whereValid()->where('token', $token)->first();
    }

    /**
     * This query scope is intended to be used on the base AccessToken object to query for valid tokens of any type.
     * @param Builder $query
     * @param Carbon|null $date
     */
    public function scopeWhereValid(Builder $query, Carbon $date = null)
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
     * @param Builder $query
     * @param Carbon|null $date
     */
    public function scopeWhereExpired(Builder $query, Carbon $date = null)
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
     *
     * @return array
     */
    public static function getModels()
    {
        return static::$models;
    }

    /**
     * Set the model for the given access token type.
     *
     * @param string $type The access token type.
     * @param string $model The class name of the model for that type.
     * @return void
     */
    public static function setModel(string $type, string $model)
    {
        static::$models[$type] = $model;
    }
}
