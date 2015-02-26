<?php

$action = function ($class) {
    return function () use ($class) {
        $action = $this->app->make($class);
        $request = $this->app['request']->instance();
        $parameters = $this->app['router']->current()->parameters();
        return $action->handle($request, $parameters);
    };
};

Route::group(['middleware' => 'Flarum\Web\Middleware\LoginWithCookie'], function () use ($action) {

    Route::get('/', [
        'as' => 'flarum.index',
        'uses' => $action('Flarum\Web\Actions\IndexAction')
    ]);

    Route::get('logout', [
        'as' => 'flarum.logout',
        'uses' => $action('Flarum\Web\Actions\LogoutAction')
    ]);

});

Route::post('login', [
    'as' => 'flarum.login',
    'uses' => $action('Flarum\Web\Actions\LoginAction')
]);

Route::get('confirm/{id}/{token}', [
    'as' => 'flarum.confirm',
    'uses' => $action('Flarum\Web\Actions\ConfirmAction')
]);
