<?php namespace Flarum\Extend;

use Illuminate\Contracts\Container\Container;

class PostFormatter implements ExtenderInterface
{
    protected $class;

    protected $priority;

    public function __construct($class)
    {
        $this->class = $class;
    }

    public function extend(Container $container)
    {
        $container->make('flarum.formatter')->add($this->class);
    }
}
