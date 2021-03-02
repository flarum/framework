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
use Illuminate\Database\Schema\Builder as SchemaBuilder;
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
     * Generate an access token for the specified user.
     *
     * @param int $userId
     * @param int $lifetime Does nothing. Deprecated in beta 16, removed in beta 17
     * @return static
     */
    public static function generate($userId, $lifetime = null)
    {
        if (!is_null($lifetime)) {
            trigger_error('Parameter $lifetime is deprecated in beta 16, will be removed in beta 17', E_USER_DEPRECATED);
        }

        $token = new static;

        $token->type = 'session';
        $token->token = Str::random(40);
        $token->user_id = $userId;
        $token->created_at = Carbon::now();
        $token->last_activity_at = Carbon::now();

        return $token;
    }

    public function touch()
    {
        $this->last_activity_at = Carbon::now();

        return $this->save();
    }

    public function updateLastActivity(ServerRequestInterface $request)
    {
        $ipAddress = Arr::get($request->getServerParams(), 'REMOTE_ADDR');
        // We truncate user agent so it fits in the database column
        $userAgent = substr(Arr::get($request->getServerParams(), 'HTTP_USER_AGENT'), 0, SchemaBuilder::$defaultStringLength);

        $this->last_activity_at = Carbon::now();
        $this->last_ip_address = $ipAddress;
        $this->last_user_agent = $userAgent;

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
     * Uses the $lifetime value by default, can be overridden by children classes.
     * @param Builder $query
     * @param Carbon $date
     */
    public static function scopeValid(Builder $query, Carbon $date)
    {
        if (static::$lifetime > 0) {
            $query->where('last_activity_at', '>', $date->clone()->subSeconds(static::$lifetime));
        }
    }

    /**
     * Filters which tokens are expired at the given date and ready for garbage collection.
     * Uses the $lifetime value by default, can be overridden by children classes.
     * @param Builder $query
     * @param Carbon $date
     */
    public static function scopeExpired(Builder $query, Carbon $date)
    {
        if (static::$lifetime > 0) {
            $query->where('last_activity_at', '<', $date->clone()->subSeconds(static::$lifetime));
        } else {
            $query->whereRaw('FALSE');
        }
    }

    /**
     * Shortcut to find a valid token
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
        $attributes = (array)$attributes;

        if (!empty($attributes['type'])
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
