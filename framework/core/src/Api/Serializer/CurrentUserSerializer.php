<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

use Flarum\User\User;
use InvalidArgumentException;

class CurrentUserSerializer extends UserSerializer
{
    protected function getDefaultAttributes(object|array $model): array
    {
        if (! ($model instanceof User)) {
            throw new InvalidArgumentException(
                $this::class.' can only serialize instances of '.User::class
            );
        }

        $attributes = parent::getDefaultAttributes($model);

        $attributes += [
            'isEmailConfirmed' => (bool) $model->is_email_confirmed,
            'email' => $model->email,
            'markedAllAsReadAt' => $this->formatDate($model->marked_all_as_read_at),
            'unreadNotificationCount' => (int) $model->getUnreadNotificationCount(),
            'newNotificationCount' => (int) $model->getNewNotificationCount(),
            'preferences' => (array) $model->preferences,
            'isAdmin' => $model->isAdmin(),
        ];

        return $attributes;
    }
}
