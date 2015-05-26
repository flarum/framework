<?php

use Flarum\Api\Request;

$action = function ($class) {
    return function () use ($class) {
        $action = $this->app->make($class);

        $httpRequest = $this->app['request']->instance();
        $routeParams = $this->app['router']->current()->parameters();
        $actor = $this->app['Flarum\Support\Actor'];

        if (str_contains($httpRequest->header('CONTENT_TYPE'), 'application/vnd.api+json')) {
            $input = $httpRequest->json();
        } else {
            $input = $httpRequest->all();
        }
        $input = array_merge($input, $routeParams);

        $request = new Request($input, $actor, $httpRequest);

        return $action->handle($request);
    };
};

Route::group(['prefix' => 'api', 'middleware' => 'Flarum\Api\Middleware\LoginWithHeader'], function () use ($action) {

    // Get forum information
    Route::get('forum', [
        'as' => 'flarum.api.forum.show',
        'uses' => $action('Flarum\Api\Actions\Forum\ShowAction')
    ]);

    // Retrieve authentication token
    Route::post('token', [
        'as' => 'flarum.api.token',
        'uses' => $action('Flarum\Api\Actions\TokenAction')
    ]);

    // Send forgot password email
    Route::post('forgot', [
        'as' => 'flarum.api.forgot',
        'uses' => $action('Flarum\Api\Actions\Users\ForgotAction')
    ]);

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    */

    // List users
    Route::get('users', [
        'as' => 'flarum.api.users.index',
        'uses' => $action('Flarum\Api\Actions\Users\IndexAction')
    ]);

    // Register a user
    Route::post('users', [
        'as' => 'flarum.api.users.create',
        'uses' => $action('Flarum\Api\Actions\Users\CreateAction')
    ]);

    // Get a single user
    Route::get('users/{id}', [
        'as' => 'flarum.api.users.show',
        'uses' => $action('Flarum\Api\Actions\Users\ShowAction')
    ]);

    // Edit a user
    Route::put('users/{id}', [
        'as' => 'flarum.api.users.update',
        'uses' => $action('Flarum\Api\Actions\Users\UpdateAction')
    ]);

    // Delete a user
    Route::delete('users/{id}', [
        'as' => 'flarum.api.users.delete',
        'uses' => $action('Flarum\Api\Actions\Users\DeleteAction')
    ]);

    // Upload avatar
    Route::post('users/{id}/avatar', [
        'as' => 'flarum.api.users.avatar.upload',
        'uses' => $action('Flarum\Api\Actions\Users\UploadAvatarAction')
    ]);

    // Remove avatar
    Route::delete('users/{id}/avatar', [
        'as' => 'flarum.api.users.avatar.delete',
        'uses' => $action('Flarum\Api\Actions\Users\DeleteAvatarAction')
    ]);

    /*
    |--------------------------------------------------------------------------
    | Activity
    |--------------------------------------------------------------------------
    */

    // List activity
    Route::get('activity', [
        'as' => 'flarum.api.activity.index',
        'uses' => $action('Flarum\Api\Actions\Activity\IndexAction')
    ]);

    // List notifications for the current user
    Route::get('notifications', [
        'as' => 'flarum.api.notifications.index',
        'uses' => $action('Flarum\Api\Actions\Notifications\IndexAction')
    ]);

    // Mark a single notification as read
    Route::put('notifications/{id}', [
        'as' => 'flarum.api.notifications.update',
        'uses' => $action('Flarum\Api\Actions\Notifications\UpdateAction')
    ]);

    /*
    |--------------------------------------------------------------------------
    | Discussions
    |--------------------------------------------------------------------------
    */

    // List discussions
    Route::get('discussions', [
        'as' => 'flarum.api.discussions.index',
        'uses' => $action('Flarum\Api\Actions\Discussions\IndexAction')
    ]);

    // Create a discussion
    Route::post('discussions', [
        'as' => 'flarum.api.discussions.create',
        'uses' => $action('Flarum\Api\Actions\Discussions\CreateAction')
    ]);

    // Show a single discussion
    Route::get('discussions/{id}', [
        'as' => 'flarum.api.discussions.show',
        'uses' => $action('Flarum\Api\Actions\Discussions\ShowAction')
    ]);

    // Edit a discussion
    Route::put('discussions/{id}', [
        'as' => 'flarum.api.discussions.update',
        'uses' => $action('Flarum\Api\Actions\Discussions\UpdateAction')
    ]);

    // Delete a discussion
    Route::delete('discussions/{id}', [
        'as' => 'flarum.api.discussions.delete',
        'uses' => $action('Flarum\Api\Actions\Discussions\DeleteAction')
    ]);

    /*
    |--------------------------------------------------------------------------
    | Posts
    |--------------------------------------------------------------------------
    */

    // List posts, usually for a discussion
    Route::get('posts', [
        'as' => 'flarum.api.posts.index',
        'uses' => $action('Flarum\Api\Actions\Posts\IndexAction')
    ]);

    // Create a post
    // @todo consider 'discussions/{id}/links/posts'?
    Route::post('posts', [
        'as' => 'flarum.api.posts.create',
        'uses' => $action('Flarum\Api\Actions\Posts\CreateAction')
    ]);

    // Show a single or multiple posts by ID
    Route::get('posts/{id}', [
        'as' => 'flarum.api.posts.show',
        'uses' => $action('Flarum\Api\Actions\Posts\ShowAction')
    ]);

    // Edit a post
    Route::put('posts/{id}', [
        'as' => 'flarum.api.posts.update',
        'uses' => $action('Flarum\Api\Actions\Posts\UpdateAction')
    ]);

    // Delete a post
    Route::delete('posts/{id}', [
        'as' => 'flarum.api.posts.delete',
        'uses' => $action('Flarum\Api\Actions\Posts\DeleteAction')
    ]);

    /*
    |--------------------------------------------------------------------------
    | Groups
    |--------------------------------------------------------------------------
    */

    // List groups
    Route::get('groups', [
        'as' => 'flarum.api.groups.index',
        'uses' => $action('Flarum\Api\Actions\Groups\IndexAction')
    ]);

    // Create a group
    Route::post('groups', [
        'as' => 'flarum.api.groups.create',
        'uses' => $action('Flarum\Api\Actions\Groups\CreateAction')
    ]);

    // Show a single group
    Route::get('groups/{id}', [
        'as' => 'flarum.api.groups.show',
        'uses' => $action('Flarum\Api\Actions\Groups\ShowAction')
    ]);

    // Edit a group
    Route::put('groups/{id}', [
        'as' => 'flarum.api.groups.update',
        'uses' => $action('Flarum\Api\Actions\Groups\UpdateAction')
    ]);

    // Delete a group
    Route::delete('groups/{id}', [
        'as' => 'flarum.api.groups.delete',
        'uses' => $action('Flarum\Api\Actions\Groups\DeleteAction')
    ]);

});
