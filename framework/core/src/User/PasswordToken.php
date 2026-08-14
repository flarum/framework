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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property string $token
 * @property \Carbon\Carbon $created_at
 * @property int $user_id
 */
class PasswordToken extends AbstractModel
{
    protected $casts = [
        'created_at' => 'datetime',
        'user_id' => 'integer',
    ];

    public $incrementing = false;

    protected $primaryKey = 'token';

    /**
     * The token is a 40-character random string, not a number. Without this,
     * Eloquent defaults the key type to `int` and binds a lookup as an integer;
     * on MySQL/MariaDB a lookup for `0` then matches any token beginning with a
     * letter — about 84% of them — which was an unauthenticated account
     * takeover (GHSA-55f2-h36g-96c3).
     */
    protected $keyType = 'string';

    /**
     * Generate a password token for the specified user.
     */
    public static function generate(int $userId): static
    {
        $token = new static;

        $token->token = Str::random(40);
        $token->user_id = $userId;
        $token->created_at = Carbon::now();

        return $token;
    }

    /**
     * Find the token with the given ID, and assert that it has not expired.
     *
     * @throws InvalidConfirmationTokenException
     */
    public static function validOrFail(string $id): static
    {
        /** @var static|null $token */
        $token = static::find($id);

        if (! $token || $token->created_at->diffInDays(null, true) >= 1) {
            throw new InvalidConfirmationTokenException;
        }

        return $token;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
