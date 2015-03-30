<?php namespace Flarum\Api\Serializers;

use Tobscure\JsonApi\SerializerAbstract;
use Flarum\Api\Events\SerializeAttributes;
use Flarum\Api\Events\SerializeRelationship;
use Flarum\Support\Actor;
use Closure;

/**
 * A base serializer to call Flarum events at common serialization points.
 */
abstract class BaseSerializer extends SerializerAbstract
{
    /**
     * The actor who is requesting the serialized objects.
     *
     * @var \Flarum\Support\Actor
     */
    protected static $actor;

    /**
     * Set the actor who is requesting the serialized objects.
     *
     * @param  \Flarum\Support\Actor  $actor
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
    protected function extendAttributes($model, &$attributes = [])
    {
        event(new SerializeAttributes($this, $model, $attributes));

        return $attributes;
    }

    protected function relationship($serializer, Closure $callback = null, $many = false)
    {
        // Get the relationship name from the stack trace.
        if (is_null($callback)) {
            list(, , $caller) = debug_backtrace(false, 3);
            $relation = $caller['function'];
        }

        return function ($model, $include, $links) use ($serializer, $callback, $many, $relation) {
            if ($callback) {
                $data = $callback($model, $include);
            } else {
                if ($include) {
                    $data = $model->$relation;
                } elseif ($many) {
                    $relationIds = $relation.'_ids';
                    $data = $model->$relationIds ?: $model->relation()->get(['id'])->fetch('id')->all();
                } else {
                    $relationId = $relation.'_id';
                    $data = $model->$relationId;
                }
            }

            if (is_array($serializer)) {
                $class = get_class(is_object($data) ? $data : $model->$relation()->getRelated());
                $serializer = $serializer[$class];
            }
            $serializer = new $serializer($links);
            return $many ? $serializer->collection($data) : $serializer->resource($data);
        };
    }

    public function hasOne($serializer, Closure $callback = null)
    {
        return $this->relationship($serializer, $callback);
    }

    public function hasMany($serializer, Closure $callback = null)
    {
        return $this->relationship($serializer, $callback, true);
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
            return event(new SerializeRelationship($this, $name), null, true);
    }
}
