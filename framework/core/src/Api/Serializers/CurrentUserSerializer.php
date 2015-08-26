<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Serializers;

class CurrentUserSerializer extends UserSerializer
{
    /**
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($user)
    {
        $attributes = parent::getDefaultAttributes($user);

        if ($user->id == $this->actor->id) {
            $attributes += [
                'readTime'                 => $user->read_time ? $user->read_time->toRFC3339String() : null,
                'unreadNotificationsCount' => $user->getUnreadNotificationsCount(),
                'preferences'              => $user->preferences
            ];
        }

        return $attributes;
    }
}
