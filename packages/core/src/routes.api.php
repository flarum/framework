<?php

function action_handler($class)
{
    return function () use ($class) {
        $action = \App::make($class);
        $request = app('request');
        $parameters = Route::current()->parameters();
        return $action->handle($request, $parameters);
    };
}

Route::group(['prefix' => 'api'], function () {

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    */

    // List users
    Route::get('users', [
        'as' => 'flarum.api.users.index',
        'uses' => action_handler('Flarum\Api\Actions\Users\Index')
    ]);

    // Register a user
    Route::post('users', [
        'as' => 'flarum.api.users.create',
        'uses' => action_handler('Flarum\Api\Actions\Users\Create')
    ]);

    // Get a single user
    Route::get('users/{id}', [
        'as' => 'flarum.api.users.show',
        'uses' => action_handler('Flarum\Api\Actions\Users\Show')
    ]);

    // Edit a user
    Route::put('users/{id}', [
        'as' => 'flarum.api.users.update',
        'uses' => action_handler('Flarum\Api\Actions\Users\Update')
    ]);

    // Delete a user
    Route::delete('users/{id}', [
        'as' => 'flarum.api.users.delete',
        'uses' => action_handler('Flarum\Api\Actions\Users\Delete')
    ]);

    /*
    |--------------------------------------------------------------------------
    | Activity
    |--------------------------------------------------------------------------
    */
   
    // List activity
    Route::get('activity', [
        'as' => 'flarum.api.activity.index',
        'uses' => action_handler('Flarum\Api\Actions\Activity\Index')
    ]);

    // List notifications for the current user
    Route::get('notifications', [
        'as' => 'flarum.api.notifications.index',
        'uses' => action_handler('Flarum\Api\Actions\Notifications\Index')
    ]);

    /*
    |--------------------------------------------------------------------------
    | Discussions
    |--------------------------------------------------------------------------
    */

    // List discussions
    Route::get('discussions', [
        'as' => 'flarum.api.discussions.index',
        'uses' => action_handler('Flarum\Api\Actions\Discussions\Index')
    ]);

    // Create a discussion
    Route::post('discussions', [
        'as' => 'flarum.api.discussions.create',
        'uses' => action_handler('Flarum\Api\Actions\Discussions\Create')
    ]);

    // Show a single discussion
    Route::get('discussions/{id}', [
        'as' => 'flarum.api.discussions.show',
        'uses' => action_handler('Flarum\Api\Actions\Discussions\Show')
    ]);

    // Edit a discussion
    Route::put('discussions/{id}', [
        'as' => 'flarum.api.discussions.update',
        'uses' => action_handler('Flarum\Api\Actions\Discussions\Update')
    ]);

    // Delete a discussion
    Route::delete('discussions/{id}', [
        'as' => 'flarum.api.discussions.delete',
        'uses' => action_handler('Flarum\Api\Actions\Discussions\Delete')
    ]);

    /*
    |--------------------------------------------------------------------------
    | Posts
    |--------------------------------------------------------------------------
    */

    // List posts, usually for a discussion
    Route::get('posts', [
        'as' => 'flarum.api.posts.index',
        'uses' => action_handler('Flarum\Api\Actions\Posts\Index')
    ]);

    // Create a post
    // @todo consider 'discussions/{id}/links/posts'?
    Route::post('posts', [
        'as' => 'flarum.api.posts.create',
        'uses' => action_handler('Flarum\Api\Actions\Posts\Create')
    ]);

    // Show a single or multiple posts by ID
    Route::get('posts/{id}', [
        'as' => 'flarum.api.posts.show',
        'uses' => action_handler('Flarum\Api\Actions\Posts\Show')
    ]);

    // Edit a post
    Route::put('posts/{id}', [
        'as' => 'flarum.api.posts.update',
        'uses' => action_handler('Flarum\Api\Actions\Posts\Update')
    ]);

    // Delete a post
    Route::delete('posts/{id}', [
        'as' => 'flarum.api.posts.delete',
        'uses' => action_handler('Flarum\Api\Actions\Posts\Delete')
    ]);

    /*
    |--------------------------------------------------------------------------
    | Groups
    |--------------------------------------------------------------------------
    */

    // List groups
    Route::get('groups', [
        'as' => 'flarum.api.groups.index',
        'uses' => action_handler('Flarum\Api\Actions\Groups\Index')
    ]);

    // Create a group
    Route::post('groups', [
        'as' => 'flarum.api.groups.create',
        'uses' => action_handler('Flarum\Api\Actions\Groups\Create')
    ]);

    // Show a single group
    Route::get('groups/{id}', [
        'as' => 'flarum.api.groups.show',
        'uses' => action_handler('Flarum\Api\Actions\Groups\Show')
    ]);

    // Edit a group
    Route::put('groups/{id}', [
        'as' => 'flarum.api.groups.update',
        'uses' => action_handler('Flarum\Api\Actions\Groups\Update')
    ]);

    // Delete a group
    Route::delete('groups/{id}', [
        'as' => 'flarum.api.groups.delete',
        'uses' => action_handler('Flarum\Api\Actions\Groups\Delete')
    ]);
    
});
