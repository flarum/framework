<?php namespace Flarum\Extend;

use Illuminate\Contracts\Container\Container;
use Flarum\Core\Activity\Activity;
use Flarum\Api\Serializers\ActivitySerializer;

class ActivityType implements ExtenderInterface
{
    protected $class;

    protected $serializer;

    public function __construct($class)
    {
        $this->class = $class;
    }

    public function subjectSerializer($serializer)
    {
        $this->serializer = $serializer;

        return $this;
    }

    public function extend(Container $container)
    {
        $class = $this->class;
        $type = $class::getType();

        Activity::setSubjectModel($type, $class::getSubjectModel());

        if ($this->serializer) {
            ActivitySerializer::setSubjectSerializer($type, $this->serializer);
        }
    }
}
