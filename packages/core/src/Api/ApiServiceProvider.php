<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api;

use Flarum\Api\Controller\AbstractSerializeController;
use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Api\Serializer\NotificationSerializer;
use Flarum\Event\ConfigureApiRoutes;
use Flarum\Event\ConfigureNotificationTypes;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Http\GenerateRouteHandlerTrait;
use Flarum\Http\RouteCollection;
use Tobscure\JsonApi\ErrorHandler;
use Tobscure\JsonApi\Exception\Handler\FallbackExceptionHandler;
use Tobscure\JsonApi\Exception\Handler\InvalidParameterExceptionHandler;

class ApiServiceProvider extends AbstractServiceProvider
{
    use GenerateRouteHandlerTrait;

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton(UrlGenerator::class, function () {
            return new UrlGenerator($this->app, $this->app->make('flarum.api.routes'));
        });

        $this->app->singleton('flarum.api.routes', function () {
            return new RouteCollection;
        });

        $this->app->singleton(ErrorHandler::class, function () {
            $handler = new ErrorHandler;

            $handler->registerHandler(new Handler\FloodingExceptionHandler);
            $handler->registerHandler(new Handler\IlluminateValidationExceptionHandler);
            $handler->registerHandler(new Handler\InvalidAccessTokenExceptionHandler);
            $handler->registerHandler(new Handler\InvalidConfirmationTokenExceptionHandler);
            $handler->registerHandler(new Handler\MethodNotAllowedExceptionHandler);
            $handler->registerHandler(new Handler\ModelNotFoundExceptionHandler);
            $handler->registerHandler(new Handler\PermissionDeniedExceptionHandler);
            $handler->registerHandler(new Handler\RouteNotFoundExceptionHandler);
            $handler->registerHandler(new Handler\TokenMismatchExceptionHandler);
            $handler->registerHandler(new Handler\ValidationExceptionHandler);
            $handler->registerHandler(new InvalidParameterExceptionHandler);
            $handler->registerHandler(new FallbackExceptionHandler($this->app->inDebugMode()));

            return $handler;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->populateRoutes($this->app->make('flarum.api.routes'));

        $this->registerNotificationSerializers();

        AbstractSerializeController::setContainer($this->app);
        AbstractSerializeController::setEventDispatcher($events = $this->app->make('events'));

        AbstractSerializer::setContainer($this->app);
        AbstractSerializer::setEventDispatcher($events);
    }

    /**
     * Register notification serializers.
     */
    protected function registerNotificationSerializers()
    {
        $blueprints = [];
        $serializers = [
            'discussionRenamed' => 'Flarum\Api\Serializer\DiscussionBasicSerializer'
        ];

        $this->app->make('events')->fire(
            new ConfigureNotificationTypes($blueprints, $serializers)
        );

        foreach ($serializers as $type => $serializer) {
            NotificationSerializer::setSubjectSerializer($type, $serializer);
        }
    }

    /**
     * Populate the API routes.
     *
     * @param RouteCollection $routes
     */
    protected function populateRoutes(RouteCollection $routes)
    {
        $toController = $this->getHandlerGenerator($this->app);

        // Get forum information
        $routes->get(
            '/forum',
            'forum.show',
            $toController('Flarum\Api\Controller\ShowForumController')
        );

        // Retrieve authentication token
        $routes->post(
            '/token',
            'token',
            $toController('Flarum\Api\Controller\TokenController')
        );

        // Send forgot password email
        $routes->post(
            '/forgot',
            'forgot',
            $toController('Flarum\Api\Controller\ForgotPasswordController')
        );

        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        */

        // List users
        $routes->get(
            '/users',
            'users.index',
            $toController('Flarum\Api\Controller\ListUsersController')
        );

        // Register a user
        $routes->post(
            '/users',
            'users.create',
            $toController('Flarum\Api\Controller\CreateUserController')
        );

        // Get a single user
        $routes->get(
            '/users/{id}',
            'users.show',
            $toController('Flarum\Api\Controller\ShowUserController')
        );

        // Edit a user
        $routes->patch(
            '/users/{id}',
            'users.update',
            $toController('Flarum\Api\Controller\UpdateUserController')
        );

        // Delete a user
        $routes->delete(
            '/users/{id}',
            'users.delete',
            $toController('Flarum\Api\Controller\DeleteUserController')
        );

        // Upload avatar
        $routes->post(
            '/users/{id}/avatar',
            'users.avatar.upload',
            $toController('Flarum\Api\Controller\UploadAvatarController')
        );

        // Remove avatar
        $routes->delete(
            '/users/{id}/avatar',
            'users.avatar.delete',
            $toController('Flarum\Api\Controller\DeleteAvatarController')
        );

        // send confirmation email
        $routes->post(
            '/users/{id}/send-confirmation',
            'users.confirmation.send',
            $toController('Flarum\Api\Controller\SendConfirmationEmailController')
        );

        /*
        |--------------------------------------------------------------------------
        | Notifications
        |--------------------------------------------------------------------------
        */

        // List notifications for the current user
        $routes->get(
            '/notifications',
            'notifications.index',
            $toController('Flarum\Api\Controller\ListNotificationsController')
        );

        // Mark all notifications as read
        $routes->post(
            '/notifications/read',
            'notifications.readAll',
            $toController('Flarum\Api\Controller\ReadAllNotificationsController')
        );

        // Mark a single notification as read
        $routes->patch(
            '/notifications/{id}',
            'notifications.update',
            $toController('Flarum\Api\Controller\UpdateNotificationController')
        );

        /*
        |--------------------------------------------------------------------------
        | Discussions
        |--------------------------------------------------------------------------
        */

        // List discussions
        $routes->get(
            '/discussions',
            'discussions.index',
            $toController('Flarum\Api\Controller\ListDiscussionsController')
        );

        // Create a discussion
        $routes->post(
            '/discussions',
            'discussions.create',
            $toController('Flarum\Api\Controller\CreateDiscussionController')
        );

        // Show a single discussion
        $routes->get(
            '/discussions/{id}',
            'discussions.show',
            $toController('Flarum\Api\Controller\ShowDiscussionController')
        );

        // Edit a discussion
        $routes->patch(
            '/discussions/{id}',
            'discussions.update',
            $toController('Flarum\Api\Controller\UpdateDiscussionController')
        );

        // Delete a discussion
        $routes->delete(
            '/discussions/{id}',
            'discussions.delete',
            $toController('Flarum\Api\Controller\DeleteDiscussionController')
        );

        /*
        |--------------------------------------------------------------------------
        | Posts
        |--------------------------------------------------------------------------
        */

        // List posts, usually for a discussion
        $routes->get(
            '/posts',
            'posts.index',
            $toController('Flarum\Api\Controller\ListPostsController')
        );

        // Create a post
        $routes->post(
            '/posts',
            'posts.create',
            $toController('Flarum\Api\Controller\CreatePostController')
        );

        // Show a single or multiple posts by ID
        $routes->get(
            '/posts/{id}',
            'posts.show',
            $toController('Flarum\Api\Controller\ShowPostController')
        );

        // Edit a post
        $routes->patch(
            '/posts/{id}',
            'posts.update',
            $toController('Flarum\Api\Controller\UpdatePostController')
        );

        // Delete a post
        $routes->delete(
            '/posts/{id}',
            'posts.delete',
            $toController('Flarum\Api\Controller\DeletePostController')
        );

        /*
        |--------------------------------------------------------------------------
        | Groups
        |--------------------------------------------------------------------------
        */

        // List groups
        $routes->get(
            '/groups',
            'groups.index',
            $toController('Flarum\Api\Controller\ListGroupsController')
        );

        // Create a group
        $routes->post(
            '/groups',
            'groups.create',
            $toController('Flarum\Api\Controller\CreateGroupController')
        );

        // Edit a group
        $routes->patch(
            '/groups/{id}',
            'groups.update',
            $toController('Flarum\Api\Controller\UpdateGroupController')
        );

        // Delete a group
        $routes->delete(
            '/groups/{id}',
            'groups.delete',
            $toController('Flarum\Api\Controller\DeleteGroupController')
        );

        /*
        |--------------------------------------------------------------------------
        | Administration
        |--------------------------------------------------------------------------
        */

        // Toggle an extension
        $routes->patch(
            '/extensions/{name}',
            'extensions.update',
            $toController('Flarum\Api\Controller\UpdateExtensionController')
        );

        // Uninstall an extension
        $routes->delete(
            '/extensions/{name}',
            'extensions.delete',
            $toController('Flarum\Api\Controller\UninstallExtensionController')
        );

        // Update settings
        $routes->post(
            '/settings',
            'settings',
            $toController('Flarum\Api\Controller\SetSettingsController')
        );

        // Update a permission
        $routes->post(
            '/permission',
            'permission',
            $toController('Flarum\Api\Controller\SetPermissionController')
        );

        $this->app->make('events')->fire(
            new ConfigureApiRoutes($routes, $toController)
        );
    }
}
