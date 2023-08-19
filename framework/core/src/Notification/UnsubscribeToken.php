<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification;

use Flarum\Database\AbstractModel;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnsubscribeToken extends AbstractModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'unsubscribe_tokens';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'user_id'        => 'int',
        'unsubscribed_at' => 'datetime'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'email_type', 'token'];

    /**
     * Define a relation to the User model.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
