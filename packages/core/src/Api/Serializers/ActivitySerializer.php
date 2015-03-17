<?php namespace Flarum\Api\Serializers;

use Flarum\Core\Models\Activity;

class ActivitySerializer extends BaseSerializer
{
    /**
     * The resource type.
     * @var string
     */
    protected $type = 'activity';

    /**
     * Serialize attributes of an Activity model for JSON output.
     *
     * @param Activity $activity The Activity model to serialize.
     * @return array
     */
    protected function attributes(Activity $activity)
    {
        $attributes = [
            'id'   => ((int) $activity->id) ?: str_random(5),
            'type' => $activity->type,
            'content' => json_encode($activity->data),
            'time' => $activity->time->toRFC3339String()
        ];

        return $this->attributesEvent($activity, $attributes);
    }

    /**
     * Get a resource containing an activity's sender.
     *
     * @param Activity $activity
     * @return Tobscure\JsonApi\Resource
     */
    public function linkUser(Activity $activity)
    {
        return (new UserBasicSerializer)->resource($activity->user_id);
    }

    /**
     * Get a resource containing an activity's sender.
     *
     * @param Activity $activity
     * @param array $relations
     * @return Tobscure\JsonApi\Resource
     */
    public function includeSender(Activity $activity, $relations)
    {
        return (new UserBasicSerializer($relations))->resource($activity->sender);
    }

    /**
     * Get a resource containing an activity's sender.
     *
     * @param Activity $activity
     * @param array $relations
     * @return Tobscure\JsonApi\Resource
     */
    public function includePost(Activity $activity, $relations)
    {
        return (new PostSerializer($relations))->resource($activity->post);
    }
}
