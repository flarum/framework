<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Users;

use Flarum\Core\Model;
use Flarum\Core\Exceptions\InvalidConfirmationTokenException;
use DateTime;

/**
 * @todo document database columns with @property
 */
class EmailToken extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'email_tokens';

    /**
     * {@inheritdoc}
     */
    protected $dates = ['created_at'];

    /**
     * Use a custom primary key for this model.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Generate an email token for the specified user.
     *
     * @param string $email
     * @param int $userId
     *
     * @return static
     */
    public static function generate($email, $userId = null)
    {
        $token = new static;

        $token->id = str_random(40);
        $token->user_id = $userId;
        $token->email = $email;
        $token->created_at = time();

        return $token;
    }

    /**
     * Define the relationship with the owner of this email token.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('Flarum\Core\Users\User');
    }

    /**
     * Find the token with the given ID, and assert that it has not expired.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $id
     *
     * @throws InvalidConfirmationTokenException
     *
     * @return static
     */
    public function scopeValidOrFail($query, $id)
    {
        $token = $query->find($id);

        if (! $token || $token->created_at < new DateTime('-1 day')) {
            throw new InvalidConfirmationTokenException;
        }

        return $token;
    }
}
