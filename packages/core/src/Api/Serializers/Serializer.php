<?php namespace Flarum\Api\Serializers;

use BadMethodCallException;
use Closure;
use Flarum\Core\Users\User;
use Tobscure\JsonApi\SerializerAbstract;
use Flarum\Api\Events\SerializeAttributes;

abstract class Serializer extends SerializerAbstract
{
    /**
     * @var User
     */
    public $actor;

    /**
     * An array of custom relation methods, grouped by subclass.
     *
     * @var array
     */
    protected static $relationMethods = [];

    /**
     * @param User $actor
     * @param array|null $include
     * @param array|null $link
     */
    public function __construct(User $actor, $include = null, $link = null)
    {
        parent::__construct($include, $link);

        $this->actor = $actor;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAttributes($model)
    {
        $attributes = $this->getDefaultAttributes($model);

        event(new SerializeAttributes($this, $model, $attributes));

        return $attributes;
    }

    /**
     * Get the default set of serialized attributes for a model.
     *
     * @param $model
     * @return array
     */
    abstract protected function getDefaultAttributes($model);

    /**
     * {@inheritdoc}
     */
    protected function getRelationshipFromMethod($name)
    {
        if (isset(static::$relationMethods[$name])) {
            return call_user_func(static::$relationMethods[$name], $this);
        }

        return parent::getRelationshipFromMethod($name);
    }

    /**
     * Get a closure that returns a Collection/Resource representing a relation.
     *
     * @param string|Closure $serializer The name of the serializer, or a
     *     Closure returning the name of the serializer, to use for the related
     *     items.
     * @param string|Closure|null $relation If a string is provided, it will be
     *     used to retrieve the relation data from the model:
     *     - If the relation is being included, the relation will be accessed
     *       as a property on the model.
     *     - If the relation is not being included and is a to-many relation, a
     *       list of IDs will be accessed as a property on the model with the
     *       suffix '_ids', otherwise by querying the relation method.
     *     - If the relation is not being included and is a to-one relation,
     *       the ID will be accessed as a property on the model with the suffix
     *       '_id'.
     *     If a closure is provided, it will be passed the model and
     *     whether or not the relation is being included. It is expected to
     *     return the relation data.
     * @param bool $many Whether or not this is a to-many relation.
     * @return callable
     */
    protected function getRelationship($serializer, $relation = null, $many = false)
    {
        // If no relationship name was provided, we can guess it from the
        // stack trace. The assumes that one of the hasOne or hasMany methods
        // was called from directly inside a serializer method.
        if (is_null($relation)) {
            list(, , $caller) = debug_backtrace(false, 3);
            $relation = $caller['function'];
        }

        return function ($model, $include, $included, $links) use ($serializer, $many, $relation) {
            // If the passed relation was a closure, we can let that take care
            // of retrieving the relation data from the model. Otherwise, we
            // need to get the data from the model itself, using the relation
            // name provided.
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

            // If the passed serializer was a closure, we'll need to run
            // that in order to find out which serializer class to instantiate.
            // This is useful for polymorphic relations.
            if ($serializer instanceof Closure) {
                $serializer = $serializer($model, $data);
            }

            /** @var \Tobscure\JsonApi\SerializerInterface $serializer */
            $serializer = new $serializer($this->actor, $included, $links);

            return $many ? $serializer->collection($data) : $serializer->resource($data);
        };
    }

    /**
     * Get a closure that returns a Resource representing a relation.
     *
     * @param string $serializer
     * @param string|Closure|null $relation
     * @see Serializer::getRelationship()
     * @return callable
     */
    public function hasOne($serializer, $relation = null)
    {
        return $this->getRelationship($serializer, $relation);
    }

    /**
     * Get a closure that returns a Collection representing a relation.
     *
     * @param string $serializer
     * @param string|Closure|null $relation
     * @see Serializer::getRelationship()
     * @return callable
     */
    public function hasMany($serializer, $relation = null)
    {
        return $this->getRelationship($serializer, $relation, true);
    }

    /**
     * Add a custom relation to the model.
     *
     * @param string $name The name of the relation.
     * @param callable $callback The callback to execute. This should return a
     *     relation closure {@see Serializer::getRelationship()}
     */
    public static function setRelationMethod($name, callable $callback)
    {
        static::$relationMethods[get_called_class()][$name] = $callback;
    }
}
