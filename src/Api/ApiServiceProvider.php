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

use Flarum\Api\Serializers\ActivitySerializer;
use Flarum\Api\Serializers\NotificationSerializer;
use Flarum\Core\Users\Guest;
use Flarum\Events\RegisterApiRoutes;
use Flarum\Events\RegisterActivityTypes;
use Flarum\Events\RegisterNotificationTypes;
use Flarum\Http\RouteCollection;
use Flarum\Api\UrlGenerator;
use Illuminate\Support\ServiceProvider;
use Psr\Http\Message\ServerRequestInterface;

class ApiServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('flarum.actor', function () {
            return new Guest;
        });

        $this->app->singleton(
            UrlGenerator::class,
            function () {
                return new UrlGenerator($this->app->make('flarum.api.routes'));
            }
        );
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->routes();

        $this->registerNotificationSerializers();
    }

    /**
     * Register notification serializers.
     */
    protected function registerNotificationSerializers()
    {
        $blueprints = [];
        $serializers = [
            'discussionRenamed' => 'Flarum\Api\Serializers\DiscussionBasicSerializer'
        ];

        event(new RegisterNotificationTypes($blueprints, $serializers));

        foreach ($serializers as $type => $serializer) {
            NotificationSerializer::setSubjectSerializer($type, $serializer);
        }
    }

    protected function routes()
    {
        $this->app->instance('flarum.api.routes', $routes = new RouteCollection);

        // Get forum information
        $routes->get(
            '/forum',
            'forum.show',
            $this->action('Flarum\Api\Actions\Forum\ShowAction')
        );

        // Save forum information
        $routes->patch(
            '/forum',
            'forum.update',
            $this->action('Flarum\Api\Actions\Forum\UpdateAction')
        );

        // Retrieve authentication token
        $routes->post(
            '/token',
            'token',
            $this->action('Flarum\Api\Actions\TokenAction')
        );

        // Send forgot password email
        $routes->post(
            '/forgot',
            'forgot',
            $this->action('Flarum\Api\Actions\ForgotAction')
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
            $this->action('Flarum\Api\Actions\Users\IndexAction')
        );

        // Register a user
        $routes->post(
            '/users',
            'users.create',
            $this->action('Flarum\Api\Actions\Users\CreateAction')
        );

        // Get a single user
        $routes->get(
            '/users/{id}',
            'users.show',
            $this->action('Flarum\Api\Actions\Users\ShowAction')
        );

        // Edit a user
        $routes->patch(
            '/users/{id}',
            'users.update',
            $this->action('Flarum\Api\Actions\Users\UpdateAction')
        );

        // Delete a user
        $routes->delete(
            '/users/{id}',
            'users.delete',
            $this->action('Flarum\Api\Actions\Users\DeleteAction')
        );

        // Upload avatar
        $routes->post(
            '/users/{id}/avatar',
            'users.avatar.upload',
            $this->action('Flarum\Api\Actions\Users\UploadAvatarAction')
        );

        // Remove avatar
        $routes->delete(
            '/users/{id}/avatar',
            'users.avatar.delete',
            $this->action('Flarum\Api\Actions\Users\DeleteAvatarAction')
        );

        /*
        |--------------------------------------------------------------------------
        | Activity
        |--------------------------------------------------------------------------
        */

        // List activity
        $routes->get(
            '/activity',
            'activity.index',
            $this->action('Flarum\Api\Actions\Activity\IndexAction')
        );

        // List notifications for the current user
        $routes->get(
            '/notifications',
            'notifications.index',
            $this->action('Flarum\Api\Actions\Notifications\IndexAction')
        );

        // Mark all notifications as read
        $routes->post(
            '/notifications/read',
            'notifications.readAll',
            $this->action('Flarum\Api\Actions\Notifications\ReadAllAction')
        );

        // Mark a single notification as read
        $routes->patch(
            '/notifications/{id}',
            'notifications.update',
            $this->action('Flarum\Api\Actions\Notifications\UpdateAction')
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
            $this->action('Flarum\Api\Actions\Discussions\IndexAction')
        );

        // Create a discussion
        $routes->post(
            '/discussions',
            'discussions.create',
            $this->action('Flarum\Api\Actions\Discussions\CreateAction')
        );

        // Show a single discussion
        $routes->get(
            '/discussions/{id}',
            'discussions.show',
            $this->action('Flarum\Api\Actions\Discussions\ShowAction')
        );

        // Edit a discussion
        $routes->patch(
            '/discussions/{id}',
            'discussions.update',
            $this->action('Flarum\Api\Actions\Discussions\UpdateAction')
        );

        // Delete a discussion
        $routes->delete(
            '/discussions/{id}',
            'discussions.delete',
            $this->action('Flarum\Api\Actions\Discussions\DeleteAction')
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
            $this->action('Flarum\Api\Actions\Posts\IndexAction')
        );

        // Create a post
        $routes->post(
            '/posts',
            'posts.create',
            $this->action('Flarum\Api\Actions\Posts\CreateAction')
        );

        // Show a single or multiple posts by ID
        $routes->get(
            '/posts/{id}',
            'posts.show',
            $this->action('Flarum\Api\Actions\Posts\ShowAction')
        );

        // Edit a post
        $routes->patch(
            '/posts/{id}',
            'posts.update',
            $this->action('Flarum\Api\Actions\Posts\UpdateAction')
        );

        // Delete a post
        $routes->delete(
            '/posts/{id}',
            'posts.delete',
            $this->action('Flarum\Api\Actions\Posts\DeleteAction')
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
            $this->action('Flarum\Api\Actions\Groups\IndexAction')
        );

        // Create a group
        $routes->post(
            '/groups',
            'groups.create',
            $this->action('Flarum\Api\Actions\Groups\CreateAction')
        );

        // Edit a group
        $routes->patch(
            '/groups/{id}',
            'groups.update',
            $this->action('Flarum\Api\Actions\Groups\UpdateAction')
        );

        // Delete a group
        $routes->delete(
            '/groups/{id}',
            'groups.delete',
            $this->action('Flarum\Api\Actions\Groups\DeleteAction')
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
            $this->action('Flarum\Api\Actions\Extensions\UpdateAction')
        );

        // Uninstall an extension
        $routes->delete(
            '/extensions/{name}',
            'extensions.delete',
            $this->action('Flarum\Api\Actions\Extensions\DeleteAction')
        );

        // Update config settings
        $routes->post(
            '/config',
            'config',
            $this->action('Flarum\Api\Actions\ConfigAction')
        );

        // Update a permission
        $routes->post(
            '/permission',
            'permission',
            $this->action('Flarum\Api\Actions\PermissionAction')
        );

        event(new RegisterApiRoutes($routes));
    }

    protected function action($class)
    {
        return function (ServerRequestInterface $httpRequest, $routeParams) use ($class) {
            $action = app($class);
            $actor = app('flarum.actor');

            $input = array_merge(
                $httpRequest->getQueryParams(),
                $httpRequest->getAttributes(),
                $httpRequest->getParsedBody(),
                $routeParams
            );

            $request = new Request($input, $actor, $httpRequest);

            return $action->handle($request);
        };
    }
}
