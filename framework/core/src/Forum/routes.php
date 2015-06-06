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

/**
 * Route::group(['middleware' => 'Flarum\Forum\Middleware\LoginWithCookie'], function () use ($action) {
 * For the two below
 */

$router->get('/', 'flarum.forum.index', $action('Flarum\Forum\Actions\IndexAction'));

$router->get('/logout', 'flarum.forum.logout', $action('Flarum\Forum\Actions\LogoutAction'));

$router->post('/login', 'flarum.forum.login', $action('Flarum\Forum\Actions\LoginAction'));

$router->get('/confirm/{token}', 'flarum.forum.confirmEmail', $action('Flarum\Forum\Actions\ConfirmEmailAction'));

$router->get('/reset/{token}', 'flarum.forum.resetPassword', $action('Flarum\Forum\Actions\ResetPasswordAction'));

$router->post('/reset', 'flarum.forum.savePassword', $action('Flarum\Forum\Actions\SavePasswordAction'));
