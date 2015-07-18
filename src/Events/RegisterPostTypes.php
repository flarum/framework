<?php namespace Flarum\Events;

class RegisterPostTypes
{
    protected $models;

    public function __construct(array &$models)
    {
        $this->models = &$models;
    }

    public function register($class)
    {
        $this->models[] = $class;
    }
}
