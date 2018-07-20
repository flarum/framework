<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\User;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\User\Exception\InvalidConfirmationTokenException;

/**
 * @property string $token
 * @property \Carbon\Carbon $created_at
 * @property string $payload
 */
class AuthToken extends AbstractModel
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'registration_tokens';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at'];

    protected $casts = [
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
     * @param array $payload
     * @return static
     */
    public static function generate(array $payload)
    {
        $token = new static;

        $token->token = str_random(40);
        $token->payload = $payload;
        $token->created_at = Carbon::now();

        return $token;
    }

    /**
     * Find the token with the given ID, and assert that it has not expired.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $token
     *
     * @throws InvalidConfirmationTokenException
     *
     * @return AuthToken
     */
    public function scopeValidOrFail($query, string $token)
    {
        /** @var AuthToken $token */
        $token = $query->find($token);

        if (! $token || $token->created_at->lessThan(Carbon::now()->subDay())) {
            throw new InvalidConfirmationTokenException;
        }

        return $token;
    }
}
