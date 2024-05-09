<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Api\Controller;
use Flarum\Http\RouteCollection;
use Flarum\Http\RouteHandlerFactory;

return function (RouteCollection $map, RouteHandlerFactory $route) {
    // Get forum information
    $map->get(
        '/',
        'forum.show',
        $route->toController(Controller\ShowForumController::class)
    );

    // Create authentication token
    $map->post(
        '/token',
        'token',
        $route->toController(Controller\CreateTokenController::class)
    );

    // Terminate all other sessions
    $map->delete(
        '/sessions',
        'sessions.delete',
        $route->toController(Controller\TerminateAllOtherSessionsController::class)
    );

    // Send forgot password email
    $map->post(
        '/forgot',
        'forgot',
        $route->toController(Controller\ForgotPasswordController::class)
    );

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    */

    // send confirmation email
    $map->post(
        '/users/{id}/send-confirmation',
        'users.confirmation.send',
        $route->toController(Controller\SendConfirmationEmailController::class)
    );

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    // Mark all notifications as read
    $map->post(
        '/notifications/read',
        'notifications.readAll',
        $route->toController(Controller\ReadAllNotificationsController::class)
    );

    // Delete all notifications for the current user.
    $map->delete(
        '/notifications',
        'notifications.deleteAll',
        $route->toController(Controller\DeleteAllNotificationsController::class)
    );

    /*
    |--------------------------------------------------------------------------
    | Administration
    |--------------------------------------------------------------------------
    */

    // Toggle an extension
    $map->patch(
        '/extensions/{name}',
        'extensions.update',
        $route->toController(Controller\UpdateExtensionController::class)
    );

    // Uninstall an extension
    $map->delete(
        '/extensions/{name}',
        'extensions.delete',
        $route->toController(Controller\UninstallExtensionController::class)
    );

    // Extension bisect
    $map->post(
        '/extension-bisect',
        'extension-bisect',
        $route->toController(Controller\ExtensionBisectController::class)
    );

    // Update settings
    $map->post(
        '/settings',
        'settings',
        $route->toController(Controller\SetSettingsController::class)
    );

    // Update a permission
    $map->post(
        '/permission',
        'permission',
        $route->toController(Controller\SetPermissionController::class)
    );

    // Upload a logo
    $map->post(
        '/logo',
        'logo',
        $route->toController(Controller\UploadLogoController::class)
    );

    // Remove the logo
    $map->delete(
        '/logo',
        'logo.delete',
        $route->toController(Controller\DeleteLogoController::class)
    );

    // Upload a favicon
    $map->post(
        '/favicon',
        'favicon',
        $route->toController(Controller\UploadFaviconController::class)
    );

    // Remove the favicon
    $map->delete(
        '/favicon',
        'favicon.delete',
        $route->toController(Controller\DeleteFaviconController::class)
    );

    // Clear the cache
    $map->delete(
        '/cache',
        'cache.clear',
        $route->toController(Controller\ClearCacheController::class)
    );

    // List available mail drivers, available fields and validation status
    $map->get(
        '/mail/settings',
        'mailSettings.index',
        $route->toController(Controller\ShowMailSettingsController::class)
    );

    // Send test mail post
    $map->post(
        '/mail/test',
        'mailTest',
        $route->toController(Controller\SendTestMailController::class)
    );
};
