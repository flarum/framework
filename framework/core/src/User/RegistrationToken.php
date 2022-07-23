<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\User\Exception\InvalidConfirmationTokenException;
use Illuminate\Support\Str;

/**
 * @property string $token
 * @property string $provider
 * @property string $identifier
 * @property array $user_attributes
 * @property array $payload
 * @property \Carbon\Carbon $created_at
 *
 * @method static self validOrFail(string $token)
 */
class RegistrationToken extends AbstractModel
{
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at'];

    protected $casts = [
        'user_attributes' => 'array',
        'payload' => 'array'
    ];

    /**
     * Use a custom primary key for this model.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * {@inheritdoc}
     */
    protected $primaryKey = 'token';

    /**
     * Generate an auth token for the specified user.
     *
     * @param string $provider
     * @param string $identifier
     * @param array $attributes
     * @param array $payload
     * @return static
     */
    public static function generate(string $provider, string $identifier, array $attributes, array $payload)
    {
        $token = new static;

        $token->token = Str::random(40);
        $token->provider = $provider;
        $token->identifier = $identifier;
        $token->user_attributes = $attributes;
        $token->payload = $payload;
        $token->created_at = Carbon::now();

        return $token;
    }

    /**
     * Find the token with the given ID, and assert that it has not expired.
     *
     * @param \Illuminate\Database\Eloquent\Builder<self> $query
     * @param string $token
     *
     * @throws InvalidConfirmationTokenException
     *
     * @return RegistrationToken
     */
    public function scopeValidOrFail($query, string $token)
    {
        /** @var RegistrationToken|null $token */
        $token = $query->find($token);

        if (! $token || $token->created_at->lessThan(Carbon::now()->subDay())) {
            throw new InvalidConfirmationTokenException;
        }

        return $token;
    }
}
