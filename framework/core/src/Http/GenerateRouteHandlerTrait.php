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

use Flarum\Http\Controller\ControllerInterface;
use Illuminate\Contracts\Container\Container;
use Psr\Http\Message\ServerRequestInterface;

trait GenerateRouteHandlerTrait
{
    /**
     * @return \Closure
     */
    protected function getHandlerGenerator(Container $container)
    {
        return function ($class) use ($container) {
            return function (ServerRequestInterface $request, $routeParams) use ($class, $container) {
                $controller = $container->make($class);

                if (! ($controller instanceof ControllerInterface)) {
                    throw new \InvalidArgumentException('Route handler must be an instance of '
                        . ControllerInterface::class);
                }

                $request = $request->withQueryParams(array_merge($request->getQueryParams(), $routeParams));

                return $controller->handle($request);
            };
        };
    }
}
