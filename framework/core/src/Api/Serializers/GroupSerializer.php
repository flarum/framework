<?php namespace Flarum\Api\Serializers;

class GroupSerializer extends BaseSerializer
{
    /**
     * The resource type.
     *
     * @var string
     */
    protected $type = 'groups';

    /**
     * Serialize attributes of a Group model for JSON output.
     *
     * @param  Group $group The Group model to serialize.
     * @return array
     */
    protected function attributes($group)
    {
        $attributes = [
            'id'           => (int) $group->id,
            'nameSingular' => $group->name_singular,
            'namePlural'   => $group->name_plural,
            'color'        => $group->color,
            'icon'         => $group->icon,
        ];

        return $this->extendAttributes($group, $attributes);
    }
}
