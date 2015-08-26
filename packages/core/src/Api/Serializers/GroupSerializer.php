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

class GroupSerializer extends Serializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'groups';

    /**
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($group)
    {
        return [
            'nameSingular' => $group->name_singular,
            'namePlural'   => $group->name_plural,
            'color'        => $group->color,
            'icon'         => $group->icon,
        ];
    }

    /**
     * @return callable
     */
    protected function permissions()
    {
        return $this->hasMany('Flarum\Api\Serializers\PermissionSerializer');
    }
}
