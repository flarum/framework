<?php

use Blomstra\Realtime\Websocket\Connection;
use Blomstra\Realtime\Websocket\Logger\WebsocketLogger;
use Ratchet\WebSocket\MessageComponentInterface;
use Ratchet\WebSocket\WsServer;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

function addRoute(RouteCollection $routes, string $method, string $uri, string $handler) {
    $action = resolve($handler);

    if (is_subclass_of($handler, MessageComponentInterface::class)) {
        if (WebsocketLogger::isEnabled()) {
            $action = WebsocketLogger::decorate($action);
        }

        $action = new WsServer($action);
    }

    $routes->add($uri, new Route($uri, ['_controller' => $action], [], [], null, [], [$method]));
}

return function (RouteCollection $routes) {
    addRoute($routes, 'get', '/app/{appKey}', Connection\Websocket::class);
    addRoute($routes, 'get', '/apps/{appId}/channels', Connection\FetchChannels::class);
    addRoute($routes, 'post', '/apps/{appId}/events', Connection\TriggerEvent::class);
};
