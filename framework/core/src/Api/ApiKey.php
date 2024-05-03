<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $key
 * @property string|null $allowed_ips
 * @property string|null $scopes
 * @property int|null $user_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon|null $last_activity_at
 * @property-read \Flarum\User\User|null $user
 */
class ApiKey extends AbstractModel
{
    use HasFactory;

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'created_at' => 'datetime',
        'last_activity_at' => 'datetime'
    ];

    public static function generate(): static
    {
        $key = new static;

        $key->key = Str::random(40);

        return $key;
    }

    public function touch($attribute = null): bool
    {
        $this->last_activity_at = Carbon::now();

        return $this->save();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
