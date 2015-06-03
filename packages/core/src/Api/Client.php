<?php

namespace Flarum\Api;

use Flarum\Support\Actor;
use Illuminate\Contracts\Container\Container;

class Client
{
    protected $container;

    protected $actor;

    public function __construct(Container $container, Actor $actor)
    {
        $this->container = $container;
        $this->actor = $actor;
    }

    /**
     * Execute the given API action class, pass the input and return its response.
     *
     * @param string $actionClass
     * @param array $input
     * @return object
     */
    public function send($actionClass, array $input = [])
    {
        /** @var \Flarum\Api\Actions\JsonApiAction $action */
        $action = $this->container->make($actionClass);

        $response = $action->handle(new Request($input, $this->actor));

        return json_decode($response->getBody());
    }
}
