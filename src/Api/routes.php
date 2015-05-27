<?php

use Flarum\Api\Request;
use Psr\Http\Message\ServerRequestInterface;

$action = function ($class) {
    return function (ServerRequestInterface $httpRequest, $routeParams) use ($class) {
        $action = $this->app->make($class);
        $actor = $this->app->make('Flarum\Support\Actor');

        $input = array_merge($httpRequest->getAttributes(), $routeParams);
        $request = new Request($input, $actor, $httpRequest);

        return $action->handle($request);
    };
};

/** @var Flarum\Http\Router $router */
$router = $this->app->make('Flarum\Http\Router');

// Get forum information
$router->get('/api/forum', 'flarum.api.forum.show', $action('Flarum\Api\Actions\Forum\ShowAction'));

// Retrieve authentication token
$router->post('/api/token', 'flarum.api.token', $action('Flarum\Api\Actions\TokenAction'));

// Send forgot password email
$router->post('/forgot', 'flarum.api.forgot', $action('Flarum\Api\Actions\ForgotAction'));

/*
|--------------------------------------------------------------------------
| Users
|--------------------------------------------------------------------------
*/

// List users
$router->get('/api/users', 'flarum.api.users.index', $action('Flarum\Api\Actions\Users\IndexAction'));

// Register a user
$router->post('/api/users', 'flarum.api.users.create', $action('Flarum\Api\Actions\Users\CreateAction'));

// Get a single user
$router->get('/api/users/{id}', 'flarum.api.users.show', $action('Flarum\Api\Actions\Users\ShowAction'));

// Edit a user
$router->put('/api/users/{id}', 'flarum.api.users.update', $action('Flarum\Api\Actions\Users\UpdateAction'));

// Delete a user
$router->delete('/api/users/{id}', 'flarum.api.users.delete', $action('Flarum\Api\Actions\Users\DeleteAction'));

// Upload avatar
$router->post('/api/users/{id}/avatar', 'flarum.api.users.avatar.upload', $action('Flarum\Api\Actions\Users\UploadAvatarAction'));

// Remove avatar
$router->delete('/api/users/{id}/avatar', 'flarum.api.users.avatar.delete', $action('Flarum\Api\Actions\Users\DeleteAvatarAction'));

/*
|--------------------------------------------------------------------------
| Activity
|--------------------------------------------------------------------------
*/

// List activity
$router->get('/api/activity', 'flarum.api.activity.index', $action('Flarum\Api\Actions\Activity\IndexAction'));

// List notifications for the current user
$router->get('/api/notifications', 'flarum.api.notifications.index', $action('Flarum\Api\Actions\Notifications\IndexAction'));

// Mark a single notification as read
$router->put('/api/notifications/{id}', 'flarum.api.notifications.update', $action('Flarum\Api\Actions\Notifications\UpdateAction'));

/*
|--------------------------------------------------------------------------
| Discussions
|--------------------------------------------------------------------------
*/

// List discussions
$router->get('/api/discussions', 'flarum.api.discussions.index', $action('Flarum\Api\Actions\Discussions\IndexAction'));

// Create a discussion
$router->post('/api/discussions', 'flarum.api.discussions.create', $action('Flarum\Api\Actions\Discussions\CreateAction'));

// Show a single discussion
$router->get('/api/discussions/{id}', 'flarum.api.discussions.show', $action('Flarum\Api\Actions\Discussions\ShowAction'));

// Edit a discussion
$router->put('/api/discussions/{id}', 'flarum.api.discussions.update', $action('Flarum\Api\Actions\Discussions\UpdateAction'));

// Delete a discussion
$router->delete('/api/discussions/{id}', 'flarum.api.discussions.delete', $action('Flarum\Api\Actions\Discussions\DeleteAction'));

/*
|--------------------------------------------------------------------------
| Posts
|--------------------------------------------------------------------------
*/

// List posts, usually for a discussion
$router->get('/api/posts', 'flarum.api.posts.index', $action('Flarum\Api\Actions\Posts\IndexAction'));

// Create a post
// @todo consider 'discussions/{id}/links/posts'?
$router->post('/api/posts', 'flarum.api.posts.create', $action('Flarum\Api\Actions\Posts\CreateAction'));

// Show a single or multiple posts by ID
$router->get('/api/posts/{id}', 'flarum.api.posts.show', $action('Flarum\Api\Actions\Posts\ShowAction'));

// Edit a post
$router->put('/api/posts/{id}', 'flarum.api.posts.update', $action('Flarum\Api\Actions\Posts\UpdateAction'));

// Delete a post
$router->delete('/api/posts/{id}', 'flarum.api.posts.delete', $action('Flarum\Api\Actions\Posts\DeleteAction'));

/*
|--------------------------------------------------------------------------
| Groups
|--------------------------------------------------------------------------
*/

// List groups
$router->get('/api/groups', 'flarum.api.groups.index', $action('Flarum\Api\Actions\Groups\IndexAction'));

// Create a group
$router->post('/api/groups', 'flarum.api.groups.create', $action('Flarum\Api\Actions\Groups\CreateAction'));

// Show a single group
$router->get('/api/groups/{id}', 'flarum.api.groups.show', $action('Flarum\Api\Actions\Groups\ShowAction'));

// Edit a group
$router->put('/api/groups/{id}', 'flarum.api.groups.update', $action('Flarum\Api\Actions\Groups\UpdateAction'));

// Delete a group
$router->delete('/api/groups/{id}', 'flarum.api.groups.delete', $action('Flarum\Api\Actions\Groups\DeleteAction'));
