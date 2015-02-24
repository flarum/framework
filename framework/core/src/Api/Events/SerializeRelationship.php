<?php namespace Flarum\Api\Events;

class SerializeRelationship
{
    public $serializer;

    public $model;

    public $type;

    public $name;

    public $relations;

    public function __construct($serializer, $model, $type, $name, $relations)
    {
        $this->serializer = $serializer;
        $this->model = $model;
        $this->type = $type;
        $this->name = $name;
        $this->relations = $relations;
    }
}
