<?php namespace Flarum\Support;

use Illuminate\Http\Request;
use Illuminate\Contracts\Bus\Dispatcher;

abstract class Action
{
    abstract public function handle(Request $request, $routeParams = []);

    public function __construct(Actor $actor, Dispatcher $bus)
    {
        $this->actor = $actor;
        $this->bus = $bus;
    }

    protected function callAction($class, $params = [])
    {
        $action = app($class);
        return $action->call($params);
    }
}
