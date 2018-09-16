<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

class CurrentUserSerializer extends UserSerializer
{
    /**
     * @param \Flarum\User\User $user
     * @return array
     */
    protected function getDefaultAttributes($user)
    {
        $attributes = parent::getDefaultAttributes($user);

        $attributes += [
            'isEmailConfirmed'         => (bool) $user->is_email_confirmed,
            'email'                    => $user->email,
            'markedAllAsReadAt'        => $this->formatDate($user->marked_all_as_read_at),
            'unreadNotificationCount'  => (int) $user->getUnreadNotificationCount(),
            'newNotificationCount'     => (int) $user->getNewNotificationCount(),
            'preferences'              => (array) $user->preferences
        ];

        return $attributes;
    }
}
