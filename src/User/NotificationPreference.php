<?php

namespace Flarum\User;

use Flarum\Database\AbstractModel;

/**
 * @property int $user_id
 * @property string $type
 * @property string $channel
 * @property bool $enabled
 */
class NotificationPreference extends AbstractModel
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
