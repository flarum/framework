<?php namespace Flarum\Extend;

use Illuminate\Foundation\Application;

class DiscussionGambit implements ExtenderInterface
{
    protected $class;

    public function __construct($class)
    {
        $this->class = $class;
    }

    public function extend(Application $app)
    {
        $app['events']->listen('Flarum\Core\Events\RegisterDiscussionGambits', function ($event) {
            $event->gambits->add($this->class);
        });
    }
}
