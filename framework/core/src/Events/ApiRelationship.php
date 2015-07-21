<?php namespace Flarum\Events;

use Flarum\Api\Serializers\Serializer;

class ApiRelationship
{
    /**
     * @var Serializer
     */
    public $serializer;

    /**
     * @var string
     */
    public $relationship;

    /**
     * @param Serializer $serializer
     * @param string $relationship
     */
    public function __construct(Serializer $serializer, $relationship)
    {
        $this->serializer = $serializer;
        $this->relationship = $relationship;
    }
}
