<?php namespace Flarum\Api\Serializers;

class ActivitySerializer extends BaseSerializer
{
    /**
     * The resource type.
     *
     * @var string
     */
    protected $type = 'activity';

    /**
     * Serialize attributes of an Activity model for JSON output.
     *
     * @param Activity $activity The Activity model to serialize.
     * @return array
     */
    protected function attributes($activity)
    {
        $attributes = [
            'id'   => ((int) $activity->id) ?: str_random(5),
            'contentType' => $activity->type,
            'content' => json_encode($activity->data),
            'time' => $activity->time->toRFC3339String()
        ];

        return $this->extendAttributes($activity, $attributes);
    }

    public function user()
    {
        return $this->hasOne('Flarum\Api\Serializers\UserBasicSerializer');
    }

    public function sender()
    {
        return $this->hasOne('Flarum\Api\Serializers\UserBasicSerializer');
    }

    public function post()
    {
        return $this->hasOne('Flarum\Api\Serializers\PostSerializer');
    }
}
