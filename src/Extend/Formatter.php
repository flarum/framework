<?php namespace Flarum\Extend;

use Illuminate\Contracts\Container\Container;

class Formatter implements ExtenderInterface
{
    protected $name;

    protected $class;

    protected $priority;

    public function __construct($name, $class, $priority = 0)
    {
        $this->name = $name;
        $this->class = $class;
        $this->priority = $priority;
    }

    public function extend(Container $container)
    {
        $container->make('flarum.formatter')->add($this->name, $this->class, $this->priority);
    }
}
