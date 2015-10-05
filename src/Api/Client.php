<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api;

use Flarum\Core\Users\User;
use Illuminate\Contracts\Container\Container;
use Exception;
use Zend\Stratigility\ErrorMiddlewareInterface;

class Client
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var ErrorMiddlewareInterface
     */
    private $middleware;

    /**
     * @param Container $container
     * @param ErrorMiddlewareInterface $middleware
     */
    public function __construct(Container $container, ErrorMiddlewareInterface $middleware)
    {
        $this->container = $container;
        $this->middleware = $middleware;
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
        /** @var \Flarum\Api\Actions\Action $action */
        $action = $this->container->make($actionClass);

        try {
            $response = $action->handle(new Request($input, $actor));
        } catch (Exception $e) {
            $response = $this->middleware->handle($e);
        }
        
        return new Response($response);
    }
}
