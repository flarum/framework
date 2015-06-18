<?php namespace Flarum\Extend;

use Illuminate\Contracts\Container\Container;

class EventSubscriber implements ExtenderInterface
{
    protected $subscriber;

    public function __construct($subscriber)
    {
        $this->subscriber = $subscriber;
    }

    public function extend(Container $container)
    {
        foreach ((array) $this->subscriber as $subscriber) {
            $container->make('events')->subscribe($subscriber);
        }
    }
}
