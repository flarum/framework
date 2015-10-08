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

use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Core\Group;
use InvalidArgumentException;

class GroupSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'groups';

    /**
     * {@inheritdoc}
     *
     * @param Group $group
     * @throws InvalidArgumentException
     */
    protected function getDefaultAttributes($group)
    {
        if (! ($group instanceof Group)) {
            throw new InvalidArgumentException(get_class($this)
                . ' can only serialize instances of ' . Group::class);
        }

        return [
            'nameSingular' => $group->name_singular,
            'namePlural'   => $group->name_plural,
            'color'        => $group->color,
            'icon'         => $group->icon,
        ];
    }

    /**
     * @return \Flarum\Api\Relationship\HasManyBuilder
     */
    protected function permissions()
    {
        return $this->hasMany('Flarum\Api\Serializers\PermissionSerializer');
    }
}
