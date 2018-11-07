<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\User\User;

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

    /**
     * Generate an API key.
     *
     * @return static
     */
    public static function generate()
    {
        $key = new static;

        $key->key = str_random(40);

        return $key;
    }

    public function touch()
    {
        $this->last_activity_at = Carbon::now();

        return $this->save();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
