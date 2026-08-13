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
 * @property int $user_id
 * @property \Carbon\Carbon $created_at
 * @property string $email
 */
class EmailToken extends AbstractModel
{
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at'];

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
     * The primary key is a random string, not an auto-incrementing integer.
     * Without this, Eloquent casts the key to an integer before lookup, so a
     * request supplying `0` matches any token MySQL coerces to `0` (i.e. any
     * token that does not begin with a digit).
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Generate an email token for the specified user.
     *
     * @param string $email
     * @param int $userId
     *
     * @return static
     */
    public static function generate($email, $userId)
    {
        $token = new static;

        $token->token = Str::random(40);
        $token->user_id = $userId;
        $token->email = $email;
        $token->created_at = Carbon::now();

        return $token;
    }

    /**
     * Define the relationship with the owner of this email token.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Find the token with the given ID, and assert that it has not expired.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $id
     * @return static
     * @throws InvalidConfirmationTokenException
     */
    public function scopeValidOrFail($query, $id)
    {
        /** @var static|null $token */
        $token = $query->find($id);

        if (! $token || $token->created_at->diffInDays() >= 1) {
            throw new InvalidConfirmationTokenException;
        }

        return $token;
    }
}
