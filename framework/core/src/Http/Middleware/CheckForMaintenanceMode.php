<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Flarum\Foundation\MaintenanceMode;
use Flarum\Http\Exception\MaintenanceModeException;
use Flarum\Http\RequestUtil;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CheckForMaintenanceMode implements MiddlewareInterface
{
    public function __construct(
        private readonly MaintenanceMode $maintenance,
        private readonly array $exemptRoutes,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $isRouteExcluded = in_array($request->getAttribute('routeName'), $this->exemptRoutes, true);

        if ($this->maintenance->inMaintenanceMode() && ! $actor->isAdmin() && ! $isRouteExcluded) {
            throw new MaintenanceModeException('The forum is currently in maintenance mode.');
        }

        return $handler->handle($request);
    }
}
