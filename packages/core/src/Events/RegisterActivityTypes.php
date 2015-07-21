<?php namespace Flarum\Events;

use InvalidArgumentException;
use ReflectionClass;

class RegisterActivityTypes
{
    /**
     * @var array
     */
    protected $blueprints;

    /**
     * @var array
     */
    protected $serializers;

    public function __construct(array &$blueprints, array &$serializers = [])
    {
        $this->blueprints = &$blueprints;
        $this->serializers = &$serializers;
    }

    public function register($blueprint, $serializer)
    {
        if (! (new ReflectionClass($blueprint))->implementsInterface('Flarum\Core\Activity\Blueprint')) {
            throw new InvalidArgumentException('Activity blueprint ' . $blueprint
                . ' must implement Flarum\Core\Activity\Blueprint');
        }

        $this->blueprints[] = $blueprint;

        $this->serializers[$blueprint::getType()] = $serializer;
    }
}
