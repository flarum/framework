<?php

namespace Flarum\Core;

use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application as LaravelApplication;

class Application extends Container implements LaravelApplication
{
    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var array
     */
    protected $serviceProviders = [];

    /**
     * @var array
     */
    protected $bootingListeners = [];

    /**
     * @var array
     */
    protected $bootedListeners = [];

    /**
     * Create the application instance.
     *
     * @param $basePath
     */
    public function __construct($basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * Get the version number of the application.
     *
     * @return string
     */
    public function version()
    {
        return '1.0.dev';
    }

    /**
     * Get the base path of the Laravel installation.
     *
     * @return string
     */
    public function basePath()
    {
        return $this->basePath;
    }

    /**
     * Get or check the current application environment.
     *
     * @param  mixed
     * @return string
     */
    public function environment()
    {
        return 'production';
    }

    /**
     * Determine if the application is currently down for maintenance.
     *
     * @return bool
     */
    public function isDownForMaintenance()
    {
        return false;
    }

    /**
     * Register all of the configured providers.
     *
     * @return void
     */
    public function registerConfiguredProviders()
    {
        foreach ($this->serviceProviders as $provider) {
            $provider->register();
        }
    }

    /**
     * Register a service provider with the application.
     *
     * @param  \Illuminate\Support\ServiceProvider|string $provider
     * @param  array $options
     * @param  bool $force
     * @return \Illuminate\Support\ServiceProvider
     */
    public function register($provider, $options = array(), $force = false)
    {
        $this->serviceProviders[] = $provider;
    }

    /**
     * Register a deferred provider and service.
     *
     * @param  string $provider
     * @param  string $service
     * @return void
     */
    public function registerDeferredProvider($provider, $service = null)
    {
        $this->register($provider);
    }

    /**
     * Boot the application's service providers.
     *
     * @return void
     */
    public function boot()
    {
        $this->fireListeners($this->bootingListeners);

        foreach ($this->serviceProviders as $provider) {
            $this->call([$provider, 'boot']);
        }

        $this->fireListeners($this->bootedListeners);
    }

    /**
     * Register a new boot listener.
     *
     * @param  mixed $callback
     * @return void
     */
    public function booting($callback)
    {
        $this->bootingListeners[] = $callback;
    }

    /**
     * Register a new "booted" listener.
     *
     * @param  mixed $callback
     * @return void
     */
    public function booted($callback)
    {
        $this->bootedListeners[] = $callback;
    }

    /**
     * Fire the given array of listener callbacks.
     *
     * @param array $listeners
     * @return void
     */
    protected function fireListeners(array $listeners)
    {
        foreach ($listeners as $listener) {
            $listener($this);
        }
    }
}
