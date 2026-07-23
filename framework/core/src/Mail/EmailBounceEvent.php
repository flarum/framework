<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail;

use Flarum\Database\AbstractModel;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A historical record of a single bounce or complaint event reported by the
 * mail provider. Unlike the flag on the user, these rows persist even after
 * the user fixes their address, so they can be counted as true event volume.
 *
 * @property int $id
 * @property string $email
 * @property int|null $user_id
 * @property string $type
 * @property string|null $reason
 * @property \Carbon\Carbon $created_at
 * @property-read \Flarum\User\User|null $user
 */
class EmailBounceEvent extends AbstractModel
{
    public const TYPE_BOUNCE = 'bounce';
    public const TYPE_COMPLAINT = 'complaint';

    protected $table = 'email_bounce_events';

    public $timestamps = false;

    protected $casts = [
        'user_id' => 'int',
        'created_at' => 'datetime',
    ];

    protected $fillable = ['email', 'user_id', 'type', 'reason', 'created_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
