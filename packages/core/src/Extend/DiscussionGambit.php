<?php namespace Flarum\Extend;

use Illuminate\Contracts\Container\Container;

class DiscussionGambit implements ExtenderInterface
{
    protected $class;

    public function __construct($class)
    {
        $this->class = $class;
    }

    public function extend(Container $container)
    {
        $container->make('events')->listen('Flarum\Core\Events\RegisterDiscussionGambits', function ($event) {
            $event->gambits->add($this->class);
        });
    }
}
