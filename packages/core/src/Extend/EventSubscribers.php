<?php namespace Flarum\Extend;

use Illuminate\Contracts\Container\Container;

class EventSubscribers implements ExtenderInterface
{
    protected $subscribers;

    public function __construct($subscribers)
    {
        $this->subscribers = $subscribers;
    }

    public function extend(Container $container)
    {
        foreach ((array) $this->subscribers as $subscriber) {
            $container->make('events')->subscribe($subscriber);
        }
    }
}
