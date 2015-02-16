<?php

Route::get('/', function () {
    return View::make('flarum.web::index')
        ->with('title', Config::get('flarum::forum_title', 'Flarum Demo Forum'));
});

Route::get('confirm/{id}/{token}', ['as' => 'flarum.confirm', function ($userId, $token) {
    $command = new Flarum\Core\Users\Commands\ConfirmEmailCommand($userId, $token);

    $commandBus = App::make('Laracasts\Commander\CommandBus');
    $commandBus->execute($command);
}]);
