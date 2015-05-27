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

Route::get('confirm/{token}', [
    'as' => 'flarum.forum.confirmEmail',
    'uses' => $action('Flarum\Forum\Actions\ConfirmEmailAction')
]);

Route::get('reset/{token}', [
    'as' => 'flarum.forum.resetPassword',
    'uses' => $action('Flarum\Forum\Actions\ResetPasswordAction')
]);

Route::post('reset', [
    'as' => 'flarum.forum.savePassword',
    'uses' => $action('Flarum\Forum\Actions\SavePasswordAction')
]);
