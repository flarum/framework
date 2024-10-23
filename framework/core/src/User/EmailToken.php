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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property string $token
 * @property int $user_id
 * @property \Carbon\Carbon $created_at
 * @property string $email
 */
class EmailToken extends AbstractModel
{
    protected $casts = [
        'user_id' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Use a custom primary key for this model.
     *
     * @var bool
     */
    public $incrementing = false;

    protected $primaryKey = 'token';

    public static function generate(string $email, int $userId): static
    {
        $token = new static;

        $token->token = Str::random(40);
        $token->user_id = $userId;
        $token->email = $email;
        $token->created_at = Carbon::now();

        return $token;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Find the token with the given ID, and assert that it has not expired.
     *
     * @throws InvalidConfirmationTokenException
     */
    public function scopeValidOrFail(Builder $query, string $id): static
    {
        /** @var static|null $token */
        $token = $query->find($id);

        if (! $token || $token->created_at->diffInDays(null, true) >= 1) {
            throw new InvalidConfirmationTokenException;
        }

        return $token;
    }
}
