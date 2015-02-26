<?php

$action = function ($class) {
    return function () use ($class) {
        $action = $this->app->make($class);
        $request = $this->app['request']->instance();
        $parameters = $this->app['router']->current()->parameters();
        return $action->handle($request, $parameters);
    };
};

Route::group(['prefix' => 'api', 'middleware' => 'Flarum\Api\Middleware\LoginWithHeader'], function () use ($action) {

    Route::post('token', [
        'as' => 'flarum.api.token',
        'uses' => $action('Flarum\Api\Actions\TokenAction')
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
