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
use Flarum\Foundation\Application;
use Flarum\User\User;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\ServerRequestFactory;

class Client
{
    /**
     * @var ErrorHandler
     */
    protected $errorHandler;
    /**
     * @var Application
     */
    private $app;

    /**
     * @param Application $app
     * @param ErrorHandler $errorHandler
     */
    public function __construct(Application $app, ErrorHandler $errorHandler)
    {
        $this->app = $app;
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
            $controller = $this->app->make($controller);
        }

        if (! ($controller instanceof RequestHandlerInterface)) {
            throw new InvalidArgumentException(
                'Endpoint must be an instance of '.RequestHandlerInterface::class
            );
        }

        try {
            return $controller->handle($request);
        } catch (Exception $e) {
            if ($this->app->inDebugMode()) {
                throw $e;
            }

            return $this->errorHandler->handle($e);
        }
    }
}
