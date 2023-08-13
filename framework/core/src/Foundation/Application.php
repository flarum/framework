<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Flarum\Database\DatabaseServiceProvider;
use Flarum\Foundation\Concerns\InteractsWithLaravel;
use Flarum\Http\RoutingServiceProvider;
use Flarum\Settings\SettingsServiceProvider;
use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Foundation\Application as LaravelApplication;
use Illuminate\Events\EventServiceProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

class Application extends IlluminateContainer implements LaravelApplication
{
    use InteractsWithLaravel;

    /**
     * The Flarum version.
     *
     * @var string
     */
    const VERSION = '2.0-dev';

    protected bool $booted = false;

    protected array $bootingCallbacks = [];

    protected array $bootedCallbacks = [];

    protected array $serviceProviders = [];

    protected array $loadedProviders = [];

    protected bool $hasBeenBootstrapped = false;

    public function __construct(
        protected Paths $paths
    ) {
        $this->registerBaseBindings();
        $this->registerBaseServiceProviders();
        $this->registerCoreContainerAliases();
    }

    public function config(string $key, mixed $default = null): mixed
    {
        $config = $this->make('flarum.config');

        return $config[$key] ?? $default;
    }

    public function url(string $path = null): string
    {
        $config = $this->make('flarum.config');
        $url = (string) $config->url();

        if ($path) {
            $url .= '/'.($config["paths.$path"] ?? $path);
        }

        return $url;
    }

    protected function registerBaseBindings(): void
    {
        IlluminateContainer::setInstance($this);

        $this->instance('app', $this);
        $this->instance(Container::class, $this);
        $this->instance('flarum', $this);
        $this->instance('flarum.paths', $this->paths);
    }

    protected function registerBaseServiceProviders(): void
    {
        $this->register(new EventServiceProvider($this));
        $this->register(new RoutingServiceProvider($this));

        // Because we need to check very early if the version of the app
        // in the settings table matches the current version, we need
        // to register the settings provider and therefore the database
        // provider very early on.
        $this->register(new DatabaseServiceProvider($this));
        $this->register(new SettingsServiceProvider($this));
    }

    public function register($provider, $force = false): ServiceProvider
    {
        if (($registered = $this->getProvider($provider)) && ! $force) {
            return $registered;
        }

        // If the given "provider" is a string, we will resolve it, passing in the
        // application instance automatically for the developer. This is simply
        // a more convenient way of specifying your service provider classes.
        if (is_string($provider)) {
            $provider = $this->resolveProvider($provider);
        }

        $provider->register();

        $this->markAsRegistered($provider);

        // If the application has already booted, we will call this boot method on
        // the provider class so it has an opportunity to do its boot logic and
        // will be ready for any usage by the developer's application logics.
        if ($this->booted) {
            $this->bootProvider($provider);
        }

        return $provider;
    }

    public function getProvider(string|ServiceProvider $provider): ?ServiceProvider
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        return Arr::first($this->serviceProviders, function ($key, $value) use ($name) {
            return $value instanceof $name;
        });
    }

    /**
     * Resolve a service provider instance from the class name.
     *
     * @param class-string<ServiceProvider> $provider
     */
    public function resolveProvider($provider): ServiceProvider
    {
        return new $provider($this);
    }

    protected function markAsRegistered(ServiceProvider $provider): void
    {
        $this['events']->dispatch($class = get_class($provider), [$provider]);

        $this->serviceProviders[] = $provider;

        $this->loadedProviders[$class] = true;
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }

    public function boot(): void
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

    protected function bootProvider(ServiceProvider $provider): void
    {
        $provider->callBootingCallbacks();

        if (method_exists($provider, 'boot')) {
            $this->call([$provider, 'boot']);
        }

        $provider->callBootedCallbacks();
    }

    public function booting(mixed $callback): void
    {
        $this->bootingCallbacks[] = $callback;
    }

    public function booted(mixed $callback): void
    {
        $this->bootedCallbacks[] = $callback;

        if ($this->isBooted()) {
            $this->fireAppCallbacks([$callback]);
        }
    }

    protected function fireAppCallbacks(array $callbacks): void
    {
        foreach ($callbacks as $callback) {
            call_user_func($callback, $this);
        }
    }

    public function bootstrapWith(array $bootstrappers): void
    {
        $this->hasBeenBootstrapped = true;

        foreach ($bootstrappers as $bootstrapper) {
            $this['events']->dispatch('bootstrapping: '.$bootstrapper, [$this]);

            $this->make($bootstrapper)->bootstrap($this);

            $this['events']->dispatch('bootstrapped: '.$bootstrapper, [$this]);
        }
    }

    public function hasBeenBootstrapped(): bool
    {
        return $this->hasBeenBootstrapped;
    }

    public function registerCoreContainerAliases(): void
    {
        $aliases = [
            'app'                  => [\Illuminate\Contracts\Container\Container::class, \Illuminate\Contracts\Foundation\Application::class, \Psr\Container\ContainerInterface::class],
            'blade.compiler'       => [\Illuminate\View\Compilers\BladeCompiler::class],
            'cache'                => [\Illuminate\Cache\CacheManager::class, \Illuminate\Contracts\Cache\Factory::class],
            'cache.filestore'      => [\Illuminate\Cache\FileStore::class, \Illuminate\Contracts\Cache\Store::class],
            'cache.store'          => [\Illuminate\Cache\Repository::class, \Illuminate\Contracts\Cache\Repository::class],
            'config'               => [\Illuminate\Config\Repository::class, \Illuminate\Contracts\Config\Repository::class],
            'container'            => [\Illuminate\Contracts\Container\Container::class, \Psr\Container\ContainerInterface::class],
            'db'                   => [\Illuminate\Database\ConnectionResolverInterface::class, \Illuminate\Database\DatabaseManager::class],
            'db.connection'        => [\Illuminate\Database\Connection::class, \Illuminate\Database\ConnectionInterface::class],
            'events'               => [\Illuminate\Events\Dispatcher::class, \Illuminate\Contracts\Events\Dispatcher::class],
            'files'                => [\Illuminate\Filesystem\Filesystem::class],
            'filesystem'           => [\Illuminate\Filesystem\FilesystemManager::class, \Illuminate\Contracts\Filesystem\Factory::class],
            'filesystem.disk'      => [\Illuminate\Contracts\Filesystem\Filesystem::class],
            'filesystem.cloud'     => [\Illuminate\Contracts\Filesystem\Cloud::class],
            'flarum'               => [self::class, \Illuminate\Contracts\Container\Container::class, \Illuminate\Contracts\Foundation\Application::class, \Psr\Container\ContainerInterface::class],
            'flarum.config'        => [Config::class],
            'flarum.paths'         => [Paths::class],
            'flarum.settings'      => [\Flarum\Settings\SettingsRepositoryInterface::class],
            'hash'                 => [\Illuminate\Contracts\Hashing\Hasher::class],
            'mailer'               => [\Illuminate\Mail\Mailer::class, \Illuminate\Contracts\Mail\Mailer::class, \Illuminate\Contracts\Mail\MailQueue::class],
            'request'              => [\Illuminate\Http\Request::class, \Symfony\Component\HttpFoundation\Request::class],
            'router'               => [\Flarum\Http\Router::class, \Illuminate\Routing\Router::class, \Illuminate\Contracts\Routing\Registrar::class, \Illuminate\Contracts\Routing\BindingRegistrar::class],
            'session'              => [\Illuminate\Session\SessionManager::class],
            'session.store'        => [\Illuminate\Session\Store::class, \Illuminate\Contracts\Session\Session::class],
            'url'                  => [\Flarum\Http\UrlGenerator::class, \Illuminate\Routing\UrlGenerator::class, \Illuminate\Contracts\Routing\UrlGenerator::class],
            'validator'            => [\Illuminate\Validation\Factory::class, \Illuminate\Contracts\Validation\Factory::class],
            'view'                 => [\Illuminate\View\Factory::class, \Illuminate\Contracts\View\Factory::class],
        ];

        foreach ($aliases as $key => $aliasGroup) {
            foreach ($aliasGroup as $alias) {
                $this->alias($key, $alias);
            }
        }
    }

    public function version(): string
    {
        return static::VERSION;
    }
}
