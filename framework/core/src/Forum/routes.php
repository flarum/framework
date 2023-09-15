<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Forum\Content;
use Flarum\Forum\Controller;
use Flarum\Http\RouteHandlerFactory;
use Flarum\Http\Router;

return function (Router $router, RouteHandlerFactory $factory) {
    $router
        ->get('/all', $factory->toForum(Content\Index::class))
        ->name('index');

    $router
        ->get('/d/{id}/{near?}', $factory->toForum(Content\Discussion::class))
        ->where('id', '\d+(?:-[^/]*)?')
        ->where('near', '[^/]*')
        ->name('discussion');

    $router
        ->get('/u/{username}/{filter?}', $factory->toForum(Content\User::class))
        ->where('filter', '[^/]*')
        ->name('user');

    $router
        ->get('/settings', $factory->toForum(Content\AssertRegistered::class))
        ->name('settings');

    $router
        ->get('/notifications', $factory->toForum(Content\AssertRegistered::class))
        ->name('notifications');

    $router
        ->get('/logout', $factory->toController(Controller\LogOutController::class))
        ->name('logout');

    $router
        ->post('/global-logout', $factory->toController(Controller\GlobalLogOutController::class))
        ->name('globalLogout');

    $router
        ->post('/login', $factory->toController(Controller\LogInController::class))
        ->name('login');

    $router
        ->post('/register', $factory->toController(Controller\RegisterController::class))
        ->name('register');

    $router
        ->get('/confirm/{token}', $factory->toController(Controller\ConfirmEmailViewController::class))
        ->name('confirmEmail');

    $router
        ->post('/confirm/{token}', $factory->toController(Controller\ConfirmEmailController::class))
        ->name('confirmEmail.submit');

    $router
        ->get('/reset/{token}', $factory->toController(Controller\ResetPasswordController::class))
        ->name('resetPassword');

    $router
        ->post('/reset', $factory->toController(Controller\SavePasswordController::class))
        ->name('savePassword');
};
