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
     * A map of activity types (key) to the serializer that should be used to
     * output the activity's subject (value).
     *
     * @var array
     */
    public static $subjects = [
        'posted' => 'Flarum\Api\Serializers\PostBasicSerializer',
        'joined' => 'Flarum\Api\Serializers\UserBasicSerializer'
    ];

    /**
     * Serialize attributes of an Activity model for JSON output.
     *
     * @param Activity $activity The Activity model to serialize.
     * @return array
     */
    protected function attributes($activity)
    {
        $attributes = [
            'contentType' => $activity->type,
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

    public function subject()
    {
        return $this->hasOne(function ($activity) {
            return static::$subjects[$activity->type];
        });
    }
}
