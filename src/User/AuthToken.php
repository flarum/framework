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

use Flarum\Database\AbstractModel;
use Flarum\User\Exception\InvalidConfirmationTokenException;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property array $payload
 * @property array $suggestions
 * @property Carbon $created_at
 */
class AuthToken extends AbstractModel
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'auth_tokens';

    /**
     * {@inheritdoc}
     */
    protected $dates = ['created_at'];

    protected $casts = [
        'payload' => 'array',
        'suggestions' => 'array'
    ];

    /**
     * Use a custom primary key for this model.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Generate an email token for the specified user.
     *
     *
     * @param array      $payload
     * @param array|null $suggestions
     * @return static
     */
    public static function generate(array $payload, array $suggestions =  null)
    {
        $token = new static;

        $token->id = str_random(40);
        $token->payload = $payload;
        $token->suggestions = $suggestions;
        $token->created_at = time();

        return $token;
    }

    /**
     * Unserialize the payload attribute from the database's JSON value.
     *
     * @param string $value
     * @return string
     */
    public function getPayloadAttribute($value)
    {
        return json_decode($value, true);
    }

    /**
     * Serialize the payload attribute to be stored in the database as JSON.
     *
     * @param string $value
     */
    public function setPayloadAttribute($value)
    {
        $this->attributes['payload'] = json_encode($value);
    }

    /**
     * Find the token with the given ID, and assert that it has not expired.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $id
     *
     * @throws \Flarum\User\Exception\InvalidConfirmationTokenException
     *
     * @return static
     */
    public function scopeValidOrFail($query, $id)
    {
        /** @var AuthToken $token */
        $token = $query->find($id);

        if (! $token || $token->created_at->diffInDays() >= 1) {
            throw new InvalidConfirmationTokenException;
        }

        return $token;
    }
}
