<?php namespace Flarum\Api\Serializers;

use Flarum\Core\Models\Group;

class GroupSerializer extends BaseSerializer
{
    /**
     * The name to use for Flarum events.
     * @var string
     */
    protected static $eventName = 'Group';

    /**
     * The resource type.
     * @var string
     */
    protected $type = 'groups';

    /**
     * Serialize attributes of a Group model for JSON output.
     *
     * @param  Group $group The Group model to serialize.
     * @return array
     */
    protected function attributes(Group $group)
    {
        $attributes = [
            'id'   => (int) $group->id,
            'name' => $group->name
        ];

        return $this->attributesEvent($group, $attributes);
    }

    /**
     * Get the URL templates where this resource and its related resources can
     * be accessed.
     *
     * @return array
     */
    public function href()
    {
        return [
            'groups' => $this->action('GroupsController@show', ['id' => '{groups.id}']),
            'users'  => $this->action('UsersController@indexForGroup', ['id' => '{groups.id}'])
        ];
    }
}
