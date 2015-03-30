<?php namespace Flarum\Forum\Events;

class CommandWillBeDispatched
{
    public $command;

    public $params;

    public function __construct($command, $params)
    {
        $this->command = $command;
        $this->params = $params;
    }
}
