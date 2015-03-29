<?php

$action = function ($class) {
    return function () use ($class) {
        $action = $this->app->make($class);
        $request = $this->app['request']->instance();
        $parameters = $this->app['router']->current()->parameters();
        return $action->handle($request, $parameters);
    };
};

Route::group(['prefix' => 'admin', 'middleware' => 'Flarum\Admin\Middleware\LoginWithCookieAndCheckAdmin'], function () use ($action) {

    Route::get('/', [
        'as' => 'flarum.admin.index',
        'uses' => $action('Flarum\Admin\Actions\IndexAction')
    ]);

});
