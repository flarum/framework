<?php namespace Flarum\Api\Events;

use Flarum\Api\Serializers\Serializer;

class SerializeAttributes
{
    /**
     * The class doing the serializing.
     *
     * @var Serializer
     */
    public $serializer;

    /**
     * The model being serialized.
     *
     * @var object
     */
    public $model;

    /**
     * The serialized attributes of the resource.
     *
     * @var array
     */
    public $attributes;

    /**
     * @param Serializer $serializer The class doing the serializing.
     * @param object $model The model being serialized.
     * @param array $attributes The serialized attributes of the resource.
     */
    public function __construct(Serializer $serializer, $model, array &$attributes)
    {
        $this->serializer = $serializer;
        $this->model = $model;
        $this->attributes = &$attributes;
    }
}
