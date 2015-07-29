<?php namespace Flarum\Api\Serializers;

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
