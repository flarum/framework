<?php namespace Flarum\Api\Serializers;

use Tobscure\JsonApi\SerializerAbstract;
use Flarum\Api\Events\SerializeAttributes;
use Flarum\Api\Events\SerializeRelationship;
use Flarum\Support\Actor;
use Illuminate\Database\Eloquent\Relations\Relation;
use Closure;

/**
 * A base serializer to call Flarum events at common serialization points.
 */
abstract class BaseSerializer extends SerializerAbstract
{
    public $actor;

    /**
     * The custom relationships on this serializer.
     *
     * @var array
     */
    protected static $relationships = [];

    public function __construct(Actor $actor, $include = null, $link = null)
    {
        parent::__construct($include, $link);

        $this->actor = $actor;
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

    protected function relationship($serializer, $relation = null, $many = false)
    {
        // Get the relationship name from the stack trace.
        if (is_null($relation)) {
            list(, , $caller) = debug_backtrace(false, 3);
            $relation = $caller['function'];
        }

        return function ($model, $include, $included, $links) use ($serializer, $many, $relation) {
            if ($relation instanceof Closure) {
                $data = $relation($model, $include);
            } else {
                if ($include) {
                    $data = $model->$relation;
                } elseif ($many) {
                    $relationIds = $relation.'_ids';
                    $data = isset($model->$relationIds) ? $model->$relationIds : $model->$relation()->lists('id');
                } else {
                    $relationId = $relation.'_id';
                    $data = $model->$relationId;
                }
            }

            if ($serializer instanceof Closure) {
                $serializer = $serializer($model, $data);
            }
            $serializer = new $serializer($this->actor, $included, $links);
            return $many ? $serializer->collection($data) : $serializer->resource($data);
        };
    }

    public function hasOne($serializer, $relation = null)
    {
        return $this->relationship($serializer, $relation);
    }

    public function hasMany($serializer, $relation = null)
    {
        return $this->relationship($serializer, $relation, true);
    }

    /**
     * Add a custom relationship to the serializer.
     *
     * @param string $name The name of the relationship.
     * @param Closure $callback The callback to execute.
     * @return void
     */
    public static function addRelationship($name, $callback)
    {
        static::$relationships[$name] = $callback;
    }

    /**
     * Check for and execute custom relationships.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (isset(static::$relationships[$name])) {
            array_unshift($arguments, $this);
            return call_user_func_array(static::$relationships[$name], $arguments);
        }
    }
}
