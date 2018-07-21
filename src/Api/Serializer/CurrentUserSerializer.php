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
            'isActivated'              => (bool) $user->is_email_confirmed,
            'email'                    => $user->email,
            'readTime'                 => $this->formatDate($user->marked_all_as_read_at),
            'unreadNotificationsCount' => (int) $user->getUnreadNotificationsCount(),
            'newNotificationsCount'    => (int) $user->getNewNotificationsCount(),
            'preferences'              => (array) $user->preferences
        ];

        return $attributes;
    }
}
