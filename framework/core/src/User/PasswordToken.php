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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property string $token
 * @property \Carbon\Carbon $created_at
 * @property int $user_id
 */
class PasswordToken extends AbstractModel
{
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $casts = ['created_at' => 'datetime'];

    /**
     * Use a custom primary key for this model.
     *
     * @var bool
     */
    public $incrementing = false;

    protected $primaryKey = 'token';

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
