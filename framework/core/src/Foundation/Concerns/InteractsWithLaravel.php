<?php

namespace Flarum\Foundation\Concerns;

use Flarum\Locale\LocaleManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Implementation of the Laravel Application Contract,
 * for the sake of better integration with Laravel packages/ecosystem.
 */
trait InteractsWithLaravel
{
    protected array $terminatingCallbacks = [];
    protected ?bool $isRunningInConsole = null;

    public function terminating($callback): static
    {
        $this->terminatingCallbacks[] = $callback;

        return $this;
    }

    public function terminate(): void
    {
        $index = 0;

        while ($index < count($this->terminatingCallbacks)) {
            $this->call($this->terminatingCallbacks[$index]);

            $index++;
        }
    }

    public function basePath($path = ''): string
    {
        return $this->joinPaths($this->paths->base, $path);
    }

    public function publicPath($path = ''): string
    {
        return $this->joinPaths($this->paths->public, $path);
    }

    public function storagePath($path = ''): string
    {
        return $this->joinPaths($this->paths->storage, $path);
    }

    /** Not actually used/has no meaning in Flarum. */
    public function bootstrapPath($path = ''): string
    {
        return $this->joinPaths(
            $this->joinPaths($this->paths->base, 'bootstrap'), $path
        );
    }

    /** Not actually used/has no meaning in Flarum. */
    public function configPath($path = ''): string
    {
        return $this->joinPaths(
            $this->joinPaths($this->paths->storage, 'config'), $path
        );
    }

    /** Not actually used/has no meaning in Flarum. */
    public function databasePath($path = ''): string
    {
        return $this->joinPaths(
            $this->joinPaths($this->paths->base, 'database'), $path
        );
    }

    /** Not actually used/has no meaning in Flarum. */
    public function langPath($path = ''): string
    {
        return $this->joinPaths(
            $this->joinPaths($this->paths->base, 'lang'), $path
        );
    }

    /** Not actually used/has no meaning in Flarum. */
    public function resourcePath($path = ''): string
    {
        return $this->joinPaths(
            $this->joinPaths($this->paths->base, 'resources'), $path
        );
    }

    public function environment(...$environments)
    {
        if (count($environments) > 0) {
            $patterns = is_array($environments[0]) ? $environments[0] : $environments;

            return Str::is($patterns, $this['env']);
        }

        return $this['env'];
    }

    public function runningInConsole(): bool
    {
        if ($this->isRunningInConsole === null) {
            $this->isRunningInConsole = \PHP_SAPI === 'cli' || \PHP_SAPI === 'phpdbg';
        }

        return $this->isRunningInConsole;
    }

    public function runningUnitTests(): bool
    {
        return $this->bound('env') && $this['env'] === 'testing';
    }

    public function hasDebugModeEnabled(): bool
    {
        return $this->config('debug', true);
    }

    public function maintenanceMode()
    {
        // TODO: Implement maintenanceMode() method.
        return null;
    }

    public function isDownForMaintenance(): bool
    {
        // TODO: Implement isDownForMaintenance() method.
        return false;
    }

    public function registerConfiguredProviders()
    {
        //
    }

    public function registerDeferredProvider($provider, $service = null)
    {
        //
    }

    public function bootstrapWith(array $bootstrappers)
    {
        //
    }

    public function getLocale()
    {
        return $this->make(LocaleManager::class)->getLocale();
    }

    public function getNamespace(): string
    {
        return 'Flarum';
    }

    public function getProviders($provider): array
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        return Arr::where($this->serviceProviders, fn ($value) => $value instanceof $name);
    }

    public function hasBeenBootstrapped()
    {
        //
    }

    public function loadDeferredProviders()
    {
        //
    }

    public function setLocale($locale): void
    {
        $this->make(LocaleManager::class)->setLocale($locale);
    }

    public function shouldSkipMiddleware()
    {
        //
    }

    public function joinPaths($basePath, $path = ''): string
    {
        return $basePath.($path != '' ? DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR) : '');
    }
}
