<?php namespace Flarum\Api;

use Flarum\Core\Users\User;
use Illuminate\Contracts\Container\Container;

class Client
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Execute the given API action class, pass the input and return its response.
     *
     * @param User $actor
     * @param string $actionClass
     * @param array $input
     * @return object
     */
    public function send(User $actor, $actionClass, array $input = [])
    {
        /** @var \Flarum\Api\Actions\JsonApiAction $action */
        $action = $this->container->make($actionClass);

        $response = $action->handle(new Request($input, $actor));

        return new Response($response);
    }
}
