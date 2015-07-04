<?php namespace Flarum\Api\Serializers;

class ActivitySerializer extends Serializer
{
    /**
     * @inheritdoc
     */
    protected $type = 'activity';

    /**
     * A map of activity types (key) to the serializer that should be used to
     * output the activity's subject (value).
     *
     * @var array
     */
    protected static $subjectSerializers = [];

    /**
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($activity)
    {
        return [
            'contentType' => $activity->type,
            'time' => $activity->time->toRFC3339String()
        ];
    }

    /**
     * @return callable
     */
    public function user()
    {
        return $this->hasOne('Flarum\Api\Serializers\UserBasicSerializer');
    }

    /**
     * @return callable
     */
    public function sender()
    {
        return $this->hasOne('Flarum\Api\Serializers\UserBasicSerializer');
    }

    /**
     * @return callable
     */
    public function subject()
    {
        return $this->hasOne(function ($activity) {
            return static::$subjectSerializers[$activity->type];
        });
    }

    /**
     * @param $type
     * @param $serializer
     */
    public static function setSubjectSerializer($type, $serializer)
    {
        static::$subjectSerializers[$type] = $serializer;
    }
}
