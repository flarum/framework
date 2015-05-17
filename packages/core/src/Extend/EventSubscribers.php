<?php namespace Flarum\Extend;

use Illuminate\Foundation\Application;

class EventSubscribers implements ExtenderInterface
{
    protected $subscribers;

    public function __construct($subscribers)
    {
        $this->subscribers = $subscribers;
    }

    public function extend(Application $app)
    {
        foreach ((array) $this->subscribers as $subscriber) {
            $app['events']->subscribe($subscriber);
        }
    }
}
