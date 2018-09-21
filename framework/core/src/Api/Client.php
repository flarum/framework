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

use Exception;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\ServerRequestFactory;

class Client
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var ErrorHandler
     */
    protected $errorHandler;

    /**
     * @param Container $container
     * @param ErrorHandler $errorHandler
     */
    public function __construct(Container $container, ErrorHandler $errorHandler = null)
    {
        $this->container = $container;
        $this->errorHandler = $errorHandler;
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
        } catch (Exception $e) {
            if (! $this->errorHandler) {
                throw $e;
            }

            return $this->errorHandler->handle($e);
        }
    }

    /**
     * @param ErrorHandler $errorHandler
     * @return Client
     */
    public function setErrorHandler(?ErrorHandler $errorHandler): self
    {
        $this->errorHandler = $errorHandler;

        return $this;
    }
}
