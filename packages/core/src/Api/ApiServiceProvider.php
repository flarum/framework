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
use Flarum\Http\UrlGenerator;
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
            'Flarum\Http\UrlGeneratorInterface',
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
            'flarum.api.forum.show',
            $this->action('Flarum\Api\Actions\Forum\ShowAction')
        );

        // Save forum information
        $routes->patch(
            '/forum',
            'flarum.api.forum.update',
            $this->action('Flarum\Api\Actions\Forum\UpdateAction')
        );

        // Retrieve authentication token
        $routes->post(
            '/token',
            'flarum.api.token',
            $this->action('Flarum\Api\Actions\TokenAction')
        );

        // Send forgot password email
        $routes->post(
            '/forgot',
            'flarum.api.forgot',
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
            'flarum.api.users.index',
            $this->action('Flarum\Api\Actions\Users\IndexAction')
        );

        // Register a user
        $routes->post(
            '/users',
            'flarum.api.users.create',
            $this->action('Flarum\Api\Actions\Users\CreateAction')
        );

        // Get a single user
        $routes->get(
            '/users/{id}',
            'flarum.api.users.show',
            $this->action('Flarum\Api\Actions\Users\ShowAction')
        );

        // Edit a user
        $routes->patch(
            '/users/{id}',
            'flarum.api.users.update',
            $this->action('Flarum\Api\Actions\Users\UpdateAction')
        );

        // Delete a user
        $routes->delete(
            '/users/{id}',
            'flarum.api.users.delete',
            $this->action('Flarum\Api\Actions\Users\DeleteAction')
        );

        // Upload avatar
        $routes->post(
            '/users/{id}/avatar',
            'flarum.api.users.avatar.upload',
            $this->action('Flarum\Api\Actions\Users\UploadAvatarAction')
        );

        // Remove avatar
        $routes->delete(
            '/users/{id}/avatar',
            'flarum.api.users.avatar.delete',
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
            'flarum.api.activity.index',
            $this->action('Flarum\Api\Actions\Activity\IndexAction')
        );

        // List notifications for the current user
        $routes->get(
            '/notifications',
            'flarum.api.notifications.index',
            $this->action('Flarum\Api\Actions\Notifications\IndexAction')
        );

        // Mark a single notification as read
        $routes->patch(
            '/notifications/{id}',
            'flarum.api.notifications.update',
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
            'flarum.api.discussions.index',
            $this->action('Flarum\Api\Actions\Discussions\IndexAction')
        );

        // Create a discussion
        $routes->post(
            '/discussions',
            'flarum.api.discussions.create',
            $this->action('Flarum\Api\Actions\Discussions\CreateAction')
        );

        // Show a single discussion
        $routes->get(
            '/discussions/{id}',
            'flarum.api.discussions.show',
            $this->action('Flarum\Api\Actions\Discussions\ShowAction')
        );

        // Edit a discussion
        $routes->patch(
            '/discussions/{id}',
            'flarum.api.discussions.update',
            $this->action('Flarum\Api\Actions\Discussions\UpdateAction')
        );

        // Delete a discussion
        $routes->delete(
            '/discussions/{id}',
            'flarum.api.discussions.delete',
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
            'flarum.api.posts.index',
            $this->action('Flarum\Api\Actions\Posts\IndexAction')
        );

        // Create a post
        $routes->post(
            '/posts',
            'flarum.api.posts.create',
            $this->action('Flarum\Api\Actions\Posts\CreateAction')
        );

        // Show a single or multiple posts by ID
        $routes->get(
            '/posts/{id}',
            'flarum.api.posts.show',
            $this->action('Flarum\Api\Actions\Posts\ShowAction')
        );

        // Edit a post
        $routes->patch(
            '/posts/{id}',
            'flarum.api.posts.update',
            $this->action('Flarum\Api\Actions\Posts\UpdateAction')
        );

        // Delete a post
        $routes->delete(
            '/posts/{id}',
            'flarum.api.posts.delete',
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
            'flarum.api.groups.index',
            $this->action('Flarum\Api\Actions\Groups\IndexAction')
        );

        // Create a group
        $routes->post(
            '/groups',
            'flarum.api.groups.create',
            $this->action('Flarum\Api\Actions\Groups\CreateAction')
        );

        // Edit a group
        $routes->patch(
            '/groups/{id}',
            'flarum.api.groups.update',
            $this->action('Flarum\Api\Actions\Groups\UpdateAction')
        );

        // Delete a group
        $routes->delete(
            '/groups/{id}',
            'flarum.api.groups.delete',
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
            'flarum.api.extensions.update',
            $this->action('Flarum\Api\Actions\Extensions\UpdateAction')
        );

        // Uninstall an extension
        $routes->delete(
            '/extensions/{name}',
            'flarum.api.extensions.delete',
            $this->action('Flarum\Api\Actions\Extensions\DeleteAction')
        );

        // Update config settings
        $routes->post(
            '/config',
            'flarum.api.config',
            $this->action('Flarum\Api\Actions\ConfigAction')
        );

        // Update a permission
        $routes->post(
            '/permission',
            'flarum.api.permission',
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
