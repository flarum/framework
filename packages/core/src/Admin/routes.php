<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Admin\Content\Index;
use Flarum\Admin\Controller\UpdateExtensionController;
use Flarum\Http\RouteCollection;
use Flarum\Http\RouteHandlerFactory;

return function (RouteCollection $map, RouteHandlerFactory $route) {
    $map->get(
        '/',
        'index',
        $route->toAdmin(Index::class)
    );

    $map->post(
        '/extensions/{name}',
        'extensions.update',
        $route->toController(UpdateExtensionController::class)
    );
};
