<?php

Route::get('/', function()
{
	return View::make('flarum.web::index')
		->with('title', Config::get('flarum::forum_title', 'Flarum Demo Forum'));
});
