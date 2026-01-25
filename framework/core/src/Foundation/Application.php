<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Flarum\Foundation\Concerns\InteractsWithLaravel;
use Illuminate\Container\Container as IlluminateContainer;
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
    public const VERSION = '2.0.0-beta.6';

    protected bool $booted = false;

    protected array $bootingCallbacks = [];

    protected array $bootedCallbacks = [];

    protected array $serviceProviders = [];

    protected array $loadedProviders = [];

    public function __construct(
        protected Paths $paths
    ) {
        $this->registerBaseBindings();
        $this->registerBaseServiceProviders();
        $this->registerCoreContainerAliases();
    }

    public function getConfig(): Config
    {
        return $this->make(Config::class);
    }

    public function config(string $key, mixed $default = null): mixed
    {
        $config = $this->getConfig();

        return $config[$key] ?? $default;
    }

    public function url(?string $path = null): string
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
        $this->alias('app', IlluminateContainer::class);

        $this->instance('container', $this);
        $this->alias('container', IlluminateContainer::class);

        $this->instance('flarum', $this);
        $this->alias('flarum', self::class);

        $this->instance('flarum.paths', $this->paths);
        $this->alias('flarum.paths', Paths::class);
    }

    protected function registerBaseServiceProviders(): void
    {
        $this->register(new EventServiceProvider($this));
    }

    public function register($provider, $force = false): ServiceProvider
    {
        if (! $force && ($registered = $this->getProvider($provider))) {
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
        $name = is_string($provider) ? $provider : $provider::class;

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
        $this['events']->dispatch($class = $provider::class, [$provider]);

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

    protected function bootProvider(ServiceProvider $provider): mixed
    {
        if (method_exists($provider, 'boot')) {
            return $this->call([$provider, 'boot']);
        }

        return null;
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
            $callback($this);
        }
    }

    public function registerCoreContainerAliases(): void
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
            'flarum.assets' => [\Flarum\Frontend\AssetManager::class],
            'hash' => [\Illuminate\Contracts\Hashing\Hasher::class],
            'mailer' => [\Illuminate\Mail\Mailer::class, \Illuminate\Contracts\Mail\Mailer::class, \Illuminate\Contracts\Mail\MailQueue::class],
            'validator' => [\Illuminate\Validation\Factory::class, \Illuminate\Contracts\Validation\Factory::class],
            'view' => [\Illuminate\View\Factory::class, \Illuminate\Contracts\View\Factory::class],
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
