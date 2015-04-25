<?php namespace Flarum\Forum\Events;

class BootForum
{
    public $app;

    public function __construct($app)
    {
        $this->app = $app;
    }
}
