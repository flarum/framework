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
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 */
class RouteHandlerFactory
{
    public function __construct(
        protected Container $container
    ) {
    }

    public function toController(callable|string $controller): callable|string|array
    {
        // If it's a class and it implements the RequestHandlerInterface, we'll
        // assume it's a PSR-7 request handler and we'll return [controller, 'handle']
        // as the callable.
        if (is_string($controller) && class_exists($controller) && in_array(RequestHandlerInterface::class, class_implements($controller))) {
            return [$controller, 'handle'];
        }

        return $controller;
    }

    public function toFrontend(string $frontend, callable|string|null $content = null): callable
    {
        $frontend = $this->container->make("flarum.frontend.$frontend");

        if ($content) {
            $frontend->content(is_callable($content) ? $content : $this->container->make($content));
        }

        return new FrontendController($frontend);
    }

    public function toForum(string $content = null): Closure
    {
        return $this->toFrontend('forum', $content);
    }

    public function toAdmin(string $content = null): Closure
    {
        return $this->toFrontend('admin', $content);
    }
}
