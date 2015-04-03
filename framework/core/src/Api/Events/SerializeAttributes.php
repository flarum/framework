<?php namespace Flarum\Api\Events;

class SerializeAttributes
{
    public $serializer;

    public $model;

    public $attributes;

    public function __construct($serializer, $model, &$attributes)
    {
        $this->serializer = $serializer;
        $this->model = $model;
        $this->attributes = &$attributes;
    }
}
