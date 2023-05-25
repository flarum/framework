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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $key
 * @property string|null $allowed_ips
 * @property string|null $scopes
 * @property int|null $user_id
 * @property \Flarum\User\User|null $user
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon|null $last_activity_at
 */
class ApiKey extends AbstractModel
{
    protected $dates = ['last_activity_at'];

    public static function generate(): static
    {
        $key = new static;

        $key->key = Str::random(40);

        return $key;
    }

    public function touch(): bool
    {
        $this->last_activity_at = Carbon::now();

        return $this->save();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
