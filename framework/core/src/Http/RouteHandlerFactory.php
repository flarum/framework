<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Closure;
use Flarum\Frontend\Controller as FrontendController;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class RouteHandlerFactory
{
    /**
     * @var Container
     */
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function toController($controller): Closure
    {
        return function (Request $request, array $routeParams) use ($controller) {
            $controller = $this->resolveController($controller);

            $request = $request->withQueryParams(array_merge($request->getQueryParams(), $routeParams));

            return $controller->handle($request);
        };
    }

    /**
     * @param string $frontend
     * @param string|callable|null $content
     */
    public function toFrontend(string $frontend, $content = null): Closure
    {
        return $this->toController(function (Container $container) use ($frontend, $content) {
            $frontend = $container->make("flarum.frontend.$frontend");

            if ($content) {
                $frontend->content(is_callable($content) ? $content : $container->make($content));
            }

            return new FrontendController($frontend);
        });
    }

    public function toForum(string $content = null): Closure
    {
        return $this->toFrontend('forum', $content);
    }

    public function toAdmin(string $content = null): Closure
    {
        return $this->toFrontend('admin', $content);
    }

    private function resolveController($controller): Handler
    {
        if (is_callable($controller)) {
            $controller = $this->container->call($controller);
        } else {
            $controller = $this->container->make($controller);
        }

        if (! $controller instanceof Handler) {
            throw new InvalidArgumentException('Controller must be an instance of '.Handler::class);
        }

        return $controller;
    }
}
