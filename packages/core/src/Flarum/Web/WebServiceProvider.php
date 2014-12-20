<?php namespace Flarum\Web;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\AssetPublisher;
use Artisan;

class WebServiceProvider extends ServiceProvider {

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
		$this->package('flarum/web', 'flarum.web');


		// Shouldn't do all this asset stuff in boot, because then it gets called on API requests
		$assetManager = $this->app['flarum.web.assetManager'];

		$assetManager->add('flarum/core', [
			'assets/vendor.css',
			'assets/flarum.css',
			'assets/vendor.js',
			'assets/flarum.js'
		]);

		// publish assets in dev environment
		$publisher = new AssetPublisher($this->app['files'], $this->app['path.public']);
		$publisher->setPackagePath(base_path().'/vendor');
		$publisher->publishPackage('flarum/core');

		include __DIR__.'/../../routes.php';
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['flarum.web.assetManager'] = $this->app->share(function($app)
		{
			return new AssetManager($app['files'], $app['path.public']);
		});
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
