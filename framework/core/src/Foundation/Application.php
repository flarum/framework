<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Illuminate\Contracts\Container\Container;
use Illuminate\Events\EventServiceProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

class Application
{
    /**
     * The Flarum version.
     *
     * @var string
     */
    const VERSION = '1.8.10';

    /**
     * The IoC container for the Flarum application.
     *
     * @var Container
     */
    private $container;

    /**
     * The paths for the Flarum installation.
     *
     * @var Paths
     */
    protected $paths;

    /**
     * Indicates if the application has "booted".
     *
     * @var bool
     */
    protected $booted = false;

    /**
     * The array of booting callbacks.
     *
     * @var array
     */
    protected $bootingCallbacks = [];

    /**
     * The array of booted callbacks.
     *
     * @var array
     */
    protected $bootedCallbacks = [];

    /**
     * All of the registered service providers.
     *
     * @var array
     */
    protected $serviceProviders = [];

    /**
     * The names of the loaded service providers.
     *
     * @var array
     */
    protected $loadedProviders = [];

    /**
     * Create a new Flarum application instance.
     *
     * @param Container $container
     * @param Paths $paths
     */
    public function __construct(Container $container, Paths $paths)
    {
        $this->container = $container;
        $this->paths = $paths;

        $this->registerBaseBindings();
        $this->registerBaseServiceProviders();
        $this->registerCoreContainerAliases();
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function config($key, $default = null)
    {
        $config = $this->container->make('flarum.config');

        return $config[$key] ?? $default;
    }

    /**
     * Check if Flarum is in debug mode.
     *
     * @return bool
     */
    public function inDebugMode()
    {
        return $this->config('debug', true);
    }

    /**
     * Get the URL to the Flarum installation.
     *
     * @param string $path
     * @return string
     */
    public function url($path = null)
    {
        $config = $this->container->make('flarum.config');
        $url = (string) $config->url();

        if ($path) {
            $url .= '/'.($config["paths.$path"] ?? $path);
        }

        return $url;
    }

    /**
     * Register the basic bindings into the container.
     */
    protected function registerBaseBindings()
    {
        \Illuminate\Container\Container::setInstance($this->container);

        /**
         * Needed for the laravel framework code.
         * Use container inside flarum instead.
         */
        $this->container->instance('app', $this->container);
        $this->container->alias('app', \Illuminate\Container\Container::class);

        $this->container->instance('container', $this->container);
        $this->container->alias('container', \Illuminate\Container\Container::class);

        $this->container->instance('flarum', $this);
        $this->container->alias('flarum', self::class);

        $this->container->instance('flarum.paths', $this->paths);
        $this->container->alias('flarum.paths', Paths::class);
    }

    /**
     * Register all of the base service providers.
     */
    protected function registerBaseServiceProviders()
    {
        $this->register(new EventServiceProvider($this->container));
    }

    /**
     * Register a service provider with the application.
     *
     * @param ServiceProvider|string $provider
     * @param array $options
     * @param bool $force
     * @return ServiceProvider
     */
    public function register($provider, $options = [], $force = false)
    {
        if (($registered = $this->getProvider($provider)) && ! $force) {
            return $registered;
        }

        // If the given "provider" is a string, we will resolve it, passing in the
        // application instance automatically for the developer. This is simply
        // a more convenient way of specifying your service provider classes.
        if (is_string($provider)) {
            $provider = $this->resolveProviderClass($provider);
        }

        $provider->register();

        // Once we have registered the service we will iterate through the options
        // and set each of them on the application so they will be available on
        // the actual loading of the service objects and for developer usage.
        foreach ($options as $key => $value) {
            $this[$key] = $value;
        }

        $this->markAsRegistered($provider);

        // If the application has already booted, we will call this boot method on
        // the provider class so it has an opportunity to do its boot logic and
        // will be ready for any usage by the developer's application logics.
        if ($this->booted) {
            $this->bootProvider($provider);
        }

        return $provider;
    }

    /**
     * Get the registered service provider instance if it exists.
     *
     * @param ServiceProvider|string $provider
     * @return ServiceProvider|null
     */
    public function getProvider($provider)
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        return Arr::first($this->serviceProviders, function ($key, $value) use ($name) {
            return $value instanceof $name;
        });
    }

    /**
     * Resolve a service provider instance from the class name.
     *
     * @param string $provider
     * @return ServiceProvider
     */
    public function resolveProviderClass($provider)
    {
        return new $provider($this->container);
    }

    /**
     * Mark the given provider as registered.
     *
     * @param ServiceProvider $provider
     * @return void
     */
    protected function markAsRegistered($provider)
    {
        $this->container['events']->dispatch($class = get_class($provider), [$provider]);

        $this->serviceProviders[] = $provider;

        $this->loadedProviders[$class] = true;
    }

    /**
     * Determine if the application has booted.
     *
     * @return bool
     */
    public function isBooted()
    {
        return $this->booted;
    }

    /**
     * Boot the application's service providers.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->booted) {
            return;
        }

        // Once the application has booted we will also fire some "booted" callbacks
        // for any listeners that need to do work after this initial booting gets
        // finished. This is useful when ordering the boot-up processes we run.
        $this->fireAppCallbacks($this->bootingCallbacks);

        array_walk($this->serviceProviders, function ($p) {
            $this->bootProvider($p);
        });

        $this->booted = true;

        $this->fireAppCallbacks($this->bootedCallbacks);
    }

    /**
     * Boot the given service provider.
     *
     * @param ServiceProvider $provider
     * @return mixed
     */
    protected function bootProvider(ServiceProvider $provider)
    {
        if (method_exists($provider, 'boot')) {
            return $this->container->call([$provider, 'boot']);
        }
    }

    /**
     * Register a new boot listener.
     *
     * @param mixed $callback
     * @return void
     */
    public function booting($callback)
    {
        $this->bootingCallbacks[] = $callback;
    }

    /**
     * Register a new "booted" listener.
     *
     * @param mixed $callback
     * @return void
     */
    public function booted($callback)
    {
        $this->bootedCallbacks[] = $callback;

        if ($this->isBooted()) {
            $this->fireAppCallbacks([$callback]);
        }
    }

    /**
     * Call the booting callbacks for the application.
     *
     * @param array $callbacks
     * @return void
     */
    protected function fireAppCallbacks(array $callbacks)
    {
        foreach ($callbacks as $callback) {
            call_user_func($callback, $this);
        }
    }

    /**
     * Register the core class aliases in the container.
     */
    public function registerCoreContainerAliases()
    {
        $aliases = [
            'app' => [\Illuminate\Contracts\Container\Container::class, \Illuminate\Contracts\Foundation\Application::class,  \Psr\Container\ContainerInterface::class],
            'blade.compiler' => [\Illuminate\View\Compilers\BladeCompiler::class],
            'cache' => [\Illuminate\Cache\CacheManager::class, \Illuminate\Contracts\Cache\Factory::class],
            'cache.store' => [\Illuminate\Cache\Repository::class, \Illuminate\Contracts\Cache\Repository::class],
            'config' => [\Illuminate\Config\Repository::class, \Illuminate\Contracts\Config\Repository::class],
            'db' => [\Illuminate\Database\DatabaseManager::class],
            'db.connection' => [\Illuminate\Database\Connection::class, \Illuminate\Database\ConnectionInterface::class],
            'events' => [\Illuminate\Events\Dispatcher::class, \Illuminate\Contracts\Events\Dispatcher::class],
            'files' => [\Illuminate\Filesystem\Filesystem::class],
            'filesystem' => [\Illuminate\Filesystem\FilesystemManager::class, \Illuminate\Contracts\Filesystem\Factory::class],
            'filesystem.disk' => [\Illuminate\Contracts\Filesystem\Filesystem::class],
            'filesystem.cloud' => [\Illuminate\Contracts\Filesystem\Cloud::class],
            'hash' => [\Illuminate\Contracts\Hashing\Hasher::class],
            'mailer' => [\Illuminate\Mail\Mailer::class, \Illuminate\Contracts\Mail\Mailer::class, \Illuminate\Contracts\Mail\MailQueue::class],
            'validator' => [\Illuminate\Validation\Factory::class, \Illuminate\Contracts\Validation\Factory::class],
            'view' => [\Illuminate\View\Factory::class, \Illuminate\Contracts\View\Factory::class],
        ];

        foreach ($aliases as $key => $aliases) {
            foreach ((array) $aliases as $alias) {
                $this->container->alias($key, $alias);
            }
        }
    }
}
