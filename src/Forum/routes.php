<?php

$action = function ($class) {
    return function () use ($class) {
        $action = $this->app->make($class);
        $request = $this->app['request']->instance();
        $parameters = $this->app['router']->current()->parameters();
        return $action->handle($request, $parameters);
    };
};

Route::group(['middleware' => 'Flarum\Forum\Middleware\LoginWithCookie'], function () use ($action) {

    Route::get('/', [
        'as' => 'flarum.forum.index',
        'uses' => $action('Flarum\Forum\Actions\IndexAction')
    ]);

    Route::get('logout', [
        'as' => 'flarum.forum.logout',
        'uses' => $action('Flarum\Forum\Actions\LogoutAction')
    ]);

});

Route::post('login', [
    'as' => 'flarum.forum.login',
    'uses' => $action('Flarum\Forum\Actions\LoginAction')
]);

Route::get('confirm/{id}/{token}', [
    'as' => 'flarum.forum.confirm',
    'uses' => $action('Flarum\Forum\Actions\ConfirmAction')
]);
