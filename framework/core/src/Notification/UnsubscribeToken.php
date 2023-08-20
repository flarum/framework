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
    protected $table = 'unsubscribe_tokens';

    protected $casts = [
        'user_id'        => 'int',
        'unsubscribed_at' => 'datetime'
    ];

    protected $fillable = ['user_id', 'email_type', 'token'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
