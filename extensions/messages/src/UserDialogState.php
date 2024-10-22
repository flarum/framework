<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\Foundation\EventGeneratorTrait;
use Flarum\Messages\Dialog\Event\UserRead;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $dialog_id
 * @property int $user_id
 * @property \Carbon\Carbon $joined_at
 * @property int|null $last_read_message_id
 * @property \Carbon\Carbon $last_read_at
 * @property-read Dialog $dialog
 * @property-read User $user
 */
class UserDialogState extends AbstractModel
{
    use EventGeneratorTrait;

    protected $table = 'dialog_user';

    protected $casts = [
        'user_id' => 'integer',
        'dialog_id' => 'integer',
        'joined_at' => 'datetime',
        'last_read_at' => 'datetime',
        'last_read_message_id' => 'integer'
    ];

    public function dialog(): BelongsTo
    {
        return $this->belongsTo(Dialog::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function read(int $messageId): static
    {
        if ($messageId > $this->last_read_message_id) {
            $this->last_read_message_id = $messageId;
            $this->last_read_at = Carbon::now();

            $this->raise(new UserRead($this));
        }

        return $this;
    }
}
