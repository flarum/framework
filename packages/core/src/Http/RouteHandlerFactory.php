<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http;

use Flarum\Frontend\Controller as FrontendController;
use Illuminate\Contracts\Container\Container;

class RouteHandlerFactory
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
     * @param string|callable $controller
     * @return ControllerRouteHandler
     */
    public function toController($controller)
    {
        return new ControllerRouteHandler($this->container, $controller);
    }

    /**
     * @param string $frontend
     * @param string|null $content
     * @return ControllerRouteHandler
     */
    public function toFrontend(string $frontend, string $content = null)
    {
        return $this->toController(function (Container $container) use ($frontend, $content) {
            $frontend = $container->make("flarum.frontend.$frontend");

            if ($content) {
                $frontend->add($container->make($content));
            }

            return new FrontendController($frontend);
        });
    }

    /**
     * @param string|null $content
     * @return ControllerRouteHandler
     */
    public function toForum(string $content = null)
    {
        return $this->toFrontend('forum', $content);
    }

    /**
     * @param string|null $content
     * @return ControllerRouteHandler
     */
    public function toAdmin(string $content = null)
    {
        return $this->toFrontend('admin', $content);
    }
}
