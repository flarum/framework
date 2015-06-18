<?php namespace Flarum\Extend;

use Illuminate\Contracts\Container\Container;

class ApiSerializer implements ExtenderInterface
{
    protected $serializer;

    protected $attributes = [];

    protected $relations = [];

    public function __construct($serializer)
    {
        $this->serializer = $serializer;
    }

    public function attributes($callback)
    {
        $this->attributes[] = $callback;

        return $this;
    }

    public function hasOne($relation, $related)
    {
        $this->relations[$relation] = function ($serializer) use ($relation, $related) {
            return $serializer->hasOne($related, $relation);
        };

        return $this;
    }

    public function hasMany($relation, $related)
    {
        $this->relations[$relation] = function ($serializer) use ($relation, $related) {
            return $serializer->hasMany($related, $relation);
        };

        return $this;
    }

    public function extend(Container $container)
    {
        $serializer = $this->serializer;

        if (count($this->attributes)) {
            $container->make('events')->listen('Flarum\Api\Events\SerializeAttributes', function ($event) use ($serializer) {
                if ($event->serializer instanceof $serializer) {
                    foreach ($this->attributes as $callback) {
                        $callback($event->attributes, $event->model, $event->serializer->actor->getUser());
                    }
                }
            });
        }

        foreach ($this->relations as $relation => $callback) {
            $serializer::addRelationship($relation, $callback);
        }
    }
}
