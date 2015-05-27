<?php

use Psr\Http\Message\ServerRequestInterface;

$action = function ($class) {
    return function (ServerRequestInterface $httpRequest, $routeParams) use ($class) {
        $action = $this->app->make($class);

        return $action->handle($httpRequest, $routeParams);
    };
};

/** @var Flarum\Http\Router $router */
$router = $this->app->make('Flarum\Http\Router');

$router->get('/admin', 'flarum.admin.index', $action('Flarum\Admin\Actions\IndexAction'));
