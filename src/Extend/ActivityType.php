<?php namespace Flarum\Extend;

use Illuminate\Contracts\Container\Container;
use Flarum\Core\Models\Activity;
use Flarum\Api\Serializers\ActivitySerializer;

class ActivityType implements ExtenderInterface
{
    protected $class;

    protected $serializer;

    public function __construct($class, $serializer)
    {
        $this->class = $class;
        $this->serializer = $serializer;
    }

    public function extend(Container $container)
    {
        $class = $this->class;

        Activity::setSubjectModel($class::getType(), $class::getSubjectModel());

        ActivitySerializer::$subjects[$class::getType()] = $this->serializer;
    }
}
