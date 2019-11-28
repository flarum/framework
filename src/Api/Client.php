<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api;

use Exception;
use Flarum\Foundation\ErrorHandling\JsonApiFormatter;
use Flarum\Foundation\ErrorHandling\Registry;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;
use Zend\Diactoros\ServerRequestFactory;

class Client
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param Container $container
     * @param Registry $registry
     */
    public function __construct(Container $container, Registry $registry)
    {
        $this->container = $container;
        $this->registry = $registry;
    }

    /**
     * Execute the given API action class, pass the input and return its response.
     *
     * @param string|RequestHandlerInterface $controller
     * @param User|null $actor
     * @param array $queryParams
     * @param array $body
     * @return ResponseInterface
     * @throws Exception
     */
    public function send($controller, User $actor = null, array $queryParams = [], array $body = []): ResponseInterface
    {
        $request = ServerRequestFactory::fromGlobals(null, $queryParams, $body);

        $request = $request->withAttribute('actor', $actor);

        if (is_string($controller)) {
            $controller = $this->container->make($controller);
        }

        if (! ($controller instanceof RequestHandlerInterface)) {
            throw new InvalidArgumentException(
                'Endpoint must be an instance of '.RequestHandlerInterface::class
            );
        }

        try {
            return $controller->handle($request);
        } catch (Throwable $e) {
            $error = $this->registry->handle($e);

            if ($error->shouldBeReported()) {
                throw $e;
            }

            return (new JsonApiFormatter)->format($error, $request);
        }
    }
}
