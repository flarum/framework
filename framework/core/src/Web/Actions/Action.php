<?php namespace Flarum\Web\Actions;

use Illuminate\Http\Request;
use Flarum\Web\Events\CommandWillBeDispatched;
use Flarum\Core\Support\Actor;

abstract class Action
{
    abstract public function handle(Request $request, $routeParams = []);

    public function __construct(Actor $actor)
    {
        $this->actor = $actor;
    }

    protected function callAction($class, $params = [])
    {
        $action = app($class);
        return $action->call($params);
    }

    protected function dispatch($command, $params)
    {
        $this->event(new CommandWillBeDispatched($command, $params));
        return $this->bus->dispatch($command);
    }
}
