<?php namespace Flarum\Forum\Actions;

use Flarum\Support\Action;
use Flarum\Forum\Events\CommandWillBeDispatched;

abstract class BaseAction extends Action
{
    protected function dispatch($command, $params = [])
    {
        event(new CommandWillBeDispatched($command, $params));
        return $this->bus->dispatch($command);
    }
}
