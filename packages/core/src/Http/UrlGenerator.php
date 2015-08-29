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

use Flarum\Core;

class UrlGenerator implements UrlGeneratorInterface
{
    protected $routes;


    public function __construct(RouteCollection $routes)
    {
        $this->routes = $routes;
    }

    public function toRoute($name, $parameters = [])
    {
        $path = $this->routes->getPath($name, $parameters);
        $path = ltrim($path, '/');

        return Core::url() . "/$path";
    }

    public function toAsset($path)
    {
        return Core::url() . "/assets/$path";
    }
}
