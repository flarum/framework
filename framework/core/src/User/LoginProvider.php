<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\Database\AbstractModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $provider
 * @property string $identifier
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $last_login_at
 * @property-read User $user
 */
class LoginProvider extends AbstractModel
{
    protected $casts = [
        'created_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    public $timestamps = true;

    const UPDATED_AT = 'last_login_at';

    protected $fillable = ['provider', 'identifier'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user associated with the provider so that they can be logged in.
     */
    public static function logIn(string $provider, string $identifier): ?User
    {
        if ($provider = static::where(compact('provider', 'identifier'))->first()) {
            $provider->touch();

            return $provider->user;
        }

        return null;
    }
}
