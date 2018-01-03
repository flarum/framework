<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Http\RouteCollection;
use Flarum\Http\RouteHandlerFactory;
use Illuminate\Contracts\Container\Container;

class Route implements Extender
{
    protected $appName;
    protected $name;
    protected $httpMethod;
    protected $path;
    protected $handler;

    public function __construct($appName, $name, $httpMethod, $path, $handler)
    {
        $this->appName = $appName;
        $this->name = $name;
        $this->httpMethod = $httpMethod;
        $this->path = $path;
        $this->handler = $handler;
    }

    public function apply(Container $container)
    {
        /** @var RouteCollection $routes */
        $collection = $container->make("flarum.{$this->appName}.routes");

        /** @var RouteHandlerFactory $factory */
        $factory = $container->make(RouteHandlerFactory::class);

        $collection->{$this->httpMethod}(
            $this->path,
            $this->name,
            $factory->toController($this->handler)
        );
    }
}
