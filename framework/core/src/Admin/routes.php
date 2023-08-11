<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Admin\Content\Index;
use Flarum\Admin\Controller\UpdateExtensionController;
use Flarum\Http\Router;
use Flarum\Http\RouteHandlerFactory;

return function (Router $router, RouteHandlerFactory $factory) {

    $router
        ->get('/', $factory->toAdmin(Index::class))
        ->name('index');

    $router
        ->post('/extensions/{name}', $factory->toController(UpdateExtensionController::class))
        ->name('extensions.update');

};
