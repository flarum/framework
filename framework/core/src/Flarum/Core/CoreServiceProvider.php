<?php namespace Flarum\Core;

use Illuminate\Support\ServiceProvider;
use Config;
use Event;

class CoreServiceProvider extends ServiceProvider
{
    /**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
    protected $defer = false;

    /**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
    public function boot()
    {
        $this->package('flarum/core', 'flarum');

        $this->app->make('validator')->extend('username', 'Flarum\Core\Users\UsernameValidator@validate');

        $this->app['config']->set('auth.model', 'Flarum\Core\Users\User');

        Event::listen('Flarum.Core.*', 'Flarum\Core\Listeners\DiscussionMetadataUpdater');
        Event::listen('Flarum.Core.*', 'Flarum\Core\Listeners\UserMetadataUpdater');
        Event::listen('Flarum.Core.*', 'Flarum\Core\Listeners\PostFormatter');
        Event::listen('Flarum.Core.*', 'Flarum\Core\Listeners\TitleChangePostCreator');
    }

    /**
	 * Register the service provider.
	 *
	 * @return void
	 */
    public function register()
    {
        // Start up the Laracasts Commander package. This is used as the basis
        // for the Commands & Domain Events architecture used to structure
        // Flarum's domain.
        $this->app->register('Laracasts\Commander\CommanderServiceProvider');

        // Register a singleton entity that represents this forum. This entity
        // will be used to check for global forum permissions (like viewing the
        // forum, registering, and starting discussions.)
        $this->app->singleton('flarum.forum', 'Flarum\Core\Forum');

        // Register the extensions manager object. This manages a list of
        // available extensions, and provides functionality to enable/disable
        // them.
        $this->app->singleton('flarum.extensions', 'Flarum\Core\Support\Extensions\Manager');

        // Register the permissions manager object. This reads the permissions
        // from the permissions repository and can determine whether or not a
        // user has explicitly been granted a certain permission.
        $this->app->singleton('flarum.permissions', 'Flarum\Core\Permissions\Manager');



        $this->app->bind('flarum.discussionFinder', 'Flarum\Core\Discussions\DiscussionFinder');

        
        // $this->app->singleton(
        // 	'Flarum\Core\Repositories\Contracts\DiscussionRepository',
        // 	function($app)
        // 	{
        // 		$discussion = new \Flarum\Core\Repositories\EloquentDiscussionRepository;
        // 		return new DiscussionCacheDecorator($discussion);
        // 	}
        // );
        // $this->app->singleton(
        // 	'Flarum\Core\Repositories\Contracts\UserRepository',
        // 	'Flarum\Core\Repositories\EloquentUserRepository'
        // );
        // $this->app->singleton(
        // 	'Flarum\Core\Repositories\Contracts\PostRepository',
        // 	'Flarum\Core\Repositories\EloquentPostRepository'
        // );
        // $this->app->singleton(
        // 	'Flarum\Core\Repositories\Contracts\GroupRepository',
        // 	'Flarum\Core\Repositories\EloquentGroupRepository'
        // );
    }

    /**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
    public function provides()
    {
        return array();
    }
}
