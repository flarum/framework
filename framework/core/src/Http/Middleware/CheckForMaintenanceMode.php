<?php

namespace Flarum\Http\Middleware;

use Flarum\Foundation\Config;
use Flarum\Http\Exception\MaintenanceModeException;
use Flarum\Http\RequestUtil;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CheckForMaintenanceMode implements MiddlewareInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly array $exemptRoutes,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $isRouteExcluded = in_array($request->getAttribute('routeName'), $this->exemptRoutes, true);

        if ($this->config->inMaintenanceMode() && ! $actor->isAdmin() && ! $isRouteExcluded) {
            throw new MaintenanceModeException('The forum is currently in maintenance mode.');
        }

        return $handler->handle($request);
    }
}
