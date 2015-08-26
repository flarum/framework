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

use Flarum\Core\Model;
use DateTime;

/**
 * @todo document database columns with @property
 */
class AccessToken extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'access_tokens';

    /**
     * Use a custom primary key for this model.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * {@inheritdoc}
     */
    protected $dates = ['created_at', 'expires_at'];

    /**
     * Generate an access token for the specified user.
     *
     * @param int $userId
     * @param int $minutes
     * @return static
     */
    public static function generate($userId, $minutes = 60)
    {
        $token = new static;

        $token->id = str_random(40);
        $token->user_id = $userId;
        $token->created_at = time();
        $token->expires_at = time() + $minutes * 60;

        return $token;
    }

    /**
     * Get the given token only if it is valid.
     *
     * @param string $token
     * @return static|null
     */
    public static function valid($token)
    {
        return static::where('id', $token)->where('expires_at', '>', new DateTime)->first();
    }

    /**
     * Define the relationship with the owner of this access token.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('Flarum\Core\Users\User');
    }
}
