<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\User\User;

/**
 * @property string $token
 * @property int $user_id
 * @property Carbon $created_at
 * @property Carbon|null $last_activity_at
 * @property int $lifetime_seconds
 * @property \Flarum\User\User|null $user
 */
class AccessToken extends AbstractModel
{
    /**
     * Use a custom primary key for this model.
     *
     * @var bool
     */
    public $incrementing = false;

    protected $primaryKey = 'token';

    protected $dates = ['last_activity_at'];

    /**
     * Generate an access token for the specified user.
     *
     * @param int $userId
     * @param int $lifetime
     * @return static
     */
    public static function generate($userId, $lifetime = 3600)
    {
        $token = new static;

        $token->token = str_random(40);
        $token->user_id = $userId;
        $token->created_at = Carbon::now();
        $token->last_activity_at = Carbon::now();
        $token->lifetime_seconds = $lifetime;

        return $token;
    }

    public function touch()
    {
        $this->last_activity_at = Carbon::now();

        return $this->save();
    }

    /**
     * Define the relationship with the owner of this access token.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
