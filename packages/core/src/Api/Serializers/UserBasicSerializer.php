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

class UserBasicSerializer extends Serializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'users';

    /**
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($user)
    {
        return [
            'username'  => $user->username,
            'avatarUrl' => $user->avatar_url
        ];
    }

    /**
     * @return callable
     */
    protected function groups()
    {
        return $this->hasMany('Flarum\Api\Serializers\GroupSerializer');
    }
}
