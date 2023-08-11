<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Api\Controller;
use Flarum\Http\RouteHandlerFactory;
use Flarum\Http\Router;

return function (Router $router, RouteHandlerFactory $factory) {

    // Get forum information
    $router
        ->get('/', $factory->toController(Controller\ShowForumController::class))
        ->name('forum.show');

    // List access tokens
    $router
        ->get('/access-tokens', $factory->toController(Controller\ListAccessTokensController::class))
        ->name('access-tokens.index');

    // Create access token
    $router
        ->post('/access-tokens', $factory->toController(Controller\CreateAccessTokenController::class))
        ->name('access-tokens.create');

    // Delete access token
    $router
        ->delete('/access-tokens/{id}', $factory->toController(Controller\DeleteAccessTokenController::class))
        ->name('access-tokens.delete');

    // Create authentication token
    $router
        ->post('/token', $factory->toController(Controller\CreateTokenController::class))
        ->name('token');

    // Terminate all other sessions
    $router
        ->delete('/sessions', $factory->toController(Controller\TerminateAllOtherSessionsController::class))
        ->name('sessions.delete');

    // Send forgot password email
    $router
        ->post('/forgot', $factory->toController(Controller\ForgotPasswordController::class))
        ->name('forgot');

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    */

    // List users
    $router
        ->get('/users', $factory->toController(Controller\ListUsersController::class))
        ->name('users.index');

    // Register a user
    $router
        ->post('/users', $factory->toController(Controller\CreateUserController::class))
        ->name('users.create');

    // Get a single user
    $router
        ->get('/users/{id}', $factory->toController(Controller\ShowUserController::class))
        ->name('users.show')
        ->whereNumber('id');

    // Edit a user
    $router
        ->patch('/users/{id}', $factory->toController(Controller\UpdateUserController::class))
        ->name('users.update')
        ->whereNumber('id');

    // Delete a user
    $router
        ->delete('/users/{id}', $factory->toController(Controller\DeleteUserController::class))
        ->name('users.delete')
        ->whereNumber('id');

    // Upload avatar
    $router
        ->post('/users/{id}/avatar', $factory->toController(Controller\UploadAvatarController::class))
        ->name('users.avatar.upload')
        ->whereNumber('id');

    // Remove avatar
    $router
        ->delete('/users/{id}/avatar', $factory->toController(Controller\DeleteAvatarController::class))
        ->name('users.avatar.delete')
        ->whereNumber('id');

    // send confirmation email
    $router
        ->post('/users/{id}/send-confirmation', $factory->toController(Controller\SendConfirmationEmailController::class))
        ->name('users.confirmation.send')
        ->whereNumber('id');

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    // List notifications for the current user
    $router
        ->get('/notifications', $factory->toController(Controller\ListNotificationsController::class))
        ->name('notifications.index');

    // Mark all notifications as read
    $router
        ->post('/notifications/read', $factory->toController(Controller\ReadAllNotificationsController::class))
        ->name('notifications.readAll');

    // Mark a single notification as read
    $router
        ->patch('/notifications/{id}', $factory->toController(Controller\UpdateNotificationController::class))
        ->name('notifications.update')
        ->whereNumber('id');

    // Delete all notifications for the current user.
    $router
        ->delete('/notifications', $factory->toController(Controller\DeleteAllNotificationsController::class))
        ->name('notifications.deleteAll');

    /*
    |--------------------------------------------------------------------------
    | Discussions
    |--------------------------------------------------------------------------
    */

    // List discussions
    $router
        ->get('/discussions', $factory->toController(Controller\ListDiscussionsController::class))
        ->name('discussions.index');

    // Create a discussion
    $router
        ->post('/discussions', $factory->toController(Controller\CreateDiscussionController::class))
        ->name('discussions.create');

    // Show a single discussion
    $router
        ->get('/discussions/{id}', $factory->toController(Controller\ShowDiscussionController::class))
        ->name('discussions.show')
        ->whereNumber('id');

    // Edit a discussion
    $router
        ->patch('/discussions/{id}', $factory->toController(Controller\UpdateDiscussionController::class))
        ->name('discussions.update')
        ->whereNumber('id');

    // Delete a discussion
    $router
        ->delete('/discussions/{id}', $factory->toController(Controller\DeleteDiscussionController::class))
        ->name('discussions.delete')
        ->whereNumber('id');

    /*
    |--------------------------------------------------------------------------
    | Posts
    |--------------------------------------------------------------------------
    */

    // List posts, usually for a discussion
    $router
        ->get('/posts', $factory->toController(Controller\ListPostsController::class))
        ->name('posts.index');

    // Create a post
    $router
        ->post('/posts', $factory->toController(Controller\CreatePostController::class))
        ->name('posts.create');

    // Show a single or multiple posts by ID
    $router
        ->get('/posts/{id}', $factory->toController(Controller\ShowPostController::class))
        ->name('posts.show')
        ->whereNumber('id');

    // Edit a post
    $router
        ->patch('/posts/{id}', $factory->toController(Controller\UpdatePostController::class))
        ->name('posts.update')
        ->whereNumber('id');

    // Delete a post
    $router
        ->delete('/posts/{id}', $factory->toController(Controller\DeletePostController::class))
        ->name('posts.delete')
        ->whereNumber('id');

    /*
    |--------------------------------------------------------------------------
    | Groups
    |--------------------------------------------------------------------------
    */

    // List groups
    $router
        ->get('/groups', $factory->toController(Controller\ListGroupsController::class))
        ->name('groups.index');

    // Create a group
    $router
        ->post('/groups', $factory->toController(Controller\CreateGroupController::class))
        ->name('groups.create');

    // Show a single group
    $router
        ->get('/groups/{id}', $factory->toController(Controller\ShowGroupController::class))
        ->name('groups.show')
        ->whereNumber('id');

    // Edit a group
    $router
        ->patch('/groups/{id}', $factory->toController(Controller\UpdateGroupController::class))
        ->name('groups.update')
        ->whereNumber('id');

    // Delete a group
    $router
        ->delete('/groups/{id}', $factory->toController(Controller\DeleteGroupController::class))
        ->name('groups.delete')
        ->whereNumber('id');

    /*
    |--------------------------------------------------------------------------
    | Administration
    |--------------------------------------------------------------------------
    */

    // Toggle an extension
    $router
        ->patch('/extensions/{name}', $factory->toController(Controller\UpdateExtensionController::class))
        ->name('extensions.update');

    // Uninstall an extension
    $router
        ->delete('/extensions/{name}', $factory->toController(Controller\UninstallExtensionController::class))
        ->name('extensions.delete');

    // Get readme for an extension
    $router
        ->get('/extension-readmes/{name}', $factory->toController(Controller\ShowExtensionReadmeController::class))
        ->name('extension-readmes.show');

    // Update settings
    $router
        ->post('/settings', $factory->toController(Controller\SetSettingsController::class))
        ->name('settings');

    // Update a permission
    $router
        ->post('/permission', $factory->toController(Controller\SetPermissionController::class))
        ->name('permission');

    // Upload a logo
    $router
        ->post('/logo', $factory->toController(Controller\UploadLogoController::class))
        ->name('logo');

    // Remove the logo
    $router
        ->delete('/logo', $factory->toController(Controller\DeleteLogoController::class))
        ->name('logo.delete');

    // Upload a favicon
    $router
        ->post('/favicon', $factory->toController(Controller\UploadFaviconController::class))
        ->name('favicon');

    // Remove the favicon
    $router
        ->delete('/favicon', $factory->toController(Controller\DeleteFaviconController::class))
        ->name('favicon.delete');

    // Clear the cache
    $router
        ->delete('/cache', $factory->toController(Controller\ClearCacheController::class))
        ->name('cache.clear');

    // List available mail drivers, available fields and validation status
    $router
        ->get('/mail/settings', $factory->toController(Controller\ShowMailSettingsController::class))
        ->name('mailSettings.index');

    // Send test mail post
    $router
        ->post('/mail/test', $factory->toController(Controller\SendTestMailController::class))
        ->name('mailTest');

};
