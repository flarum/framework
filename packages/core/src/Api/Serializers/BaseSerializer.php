<?php namespace Flarum\Api\Serializers;

use Tobscure\JsonApi\SerializerAbstract;
use Flarum\Api\Events\SerializeAttributes;
use Flarum\Api\Events\SerializeRelationship;
use Flarum\Core\Support\Actor;

/**
 * A base serializer to call Flarum events at common serialization points.
 */
abstract class BaseSerializer extends SerializerAbstract
{
    /**
     * The actor who is requesting the serialized objects.
     *
     * @var \Flarum\Core\Support\Actor
     */
    protected static $actor;

    /**
     * Set the actor who is requesting the serialized objects.
     *
     * @param  \Flarum\Core\Support\Actor  $actor
     * @return void
     */
    public static function setActor(Actor $actor)
    {
        static::$actor = $actor;
    }

    /**
     * Fire an event to allow custom serialization of attributes.
     *
     * @param  mixed $model The model to serialize.
     * @param  array $attributes Attributes that have already been serialized.
     * @return array
     */
    protected function attributesEvent($model, $attributes = [])
    {
        event(new SerializeAttributes($this, $model, $attributes));

        return $attributes;
    }

    /**
     * Fire an event to allow for custom links and includes.
     *
     * @param string $name
     * @param array $arguments
     * @return void
     */
    public function __call($name, $arguments)
    {
        if ($link = starts_with($name, 'link') || starts_with($name, 'include')) {
            $model = isset($arguments[0]) ? $arguments[0] : null;
            $relations = isset($arguments[1]) ? $arguments[1] : null;
            $type = $link ? 'link' : 'include';
            $name = substr($name, strlen($type));
            return event(new SerializeRelationship($this, $model, $type, $name, $relations), null, true);
        }
    }
}
