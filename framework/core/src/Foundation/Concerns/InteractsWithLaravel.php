<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

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
    /**
     * @deprecated Not actually used/has no meaning in Flarum.
     */
    public function terminating($callback): static
    {
        return $this;
    }

    /**
     * @deprecated Not actually used/has no meaning in Flarum.
     */
    public function terminate(): void
    {
        //
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

    /**
     * @deprecated Not actually used/has no meaning in Flarum.
     */
    public function bootstrapPath($path = ''): string
    {
        return $this->joinPaths(
            $this->joinPaths($this->paths->base, 'bootstrap'),
            $path
        );
    }

    /**
     * @deprecated Not actually used/has no meaning in Flarum.
     */
    public function configPath($path = ''): string
    {
        return $this->joinPaths(
            $this->joinPaths($this->paths->storage, 'config'),
            $path
        );
    }

    /**
     * @deprecated Not actually used/has no meaning in Flarum.
     */
    public function databasePath($path = ''): string
    {
        return $this->joinPaths(
            $this->joinPaths($this->paths->base, 'database'),
            $path
        );
    }

    /**
     * @deprecated Not actually used/has no meaning in Flarum.
     */
    public function langPath($path = ''): string
    {
        return $this->joinPaths(
            $this->joinPaths($this->paths->base, 'lang'),
            $path
        );
    }

    /**
     * @deprecated Not actually used/has no meaning in Flarum.
     */
    public function resourcePath($path = ''): string
    {
        return $this->joinPaths(
            $this->joinPaths($this->paths->base, 'resources'),
            $path
        );
    }

    public function environment(...$environments): bool|string
    {
        if (count($environments) > 0) {
            $patterns = is_array($environments[0]) ? $environments[0] : $environments;

            return Str::is($patterns, $this['env']);
        }

        return $this['env'];
    }

    public function runningInConsole(): bool
    {
        return \PHP_SAPI === 'cli' || \PHP_SAPI === 'phpdbg';
    }

    public function runningUnitTests(): bool
    {
        return $this->bound('env') && $this['env'] === 'testing';
    }

    public function hasDebugModeEnabled(): bool
    {
        return $this->config('debug', true);
    }

    /**
     * @deprecated Not actually used/has no meaning in Flarum.
     */
    public function maintenanceMode()
    {
        // TODO: Implement maintenanceMode() method.
        return null;
    }

    /**
     * @deprecated Not actually used/has no meaning in Flarum.
     */
    public function isDownForMaintenance(): bool
    {
        // TODO: Implement isDownForMaintenance() method.
        return false;
    }

    /**
     * @deprecated Not actually used/has no meaning in Flarum.
     */
    public function registerConfiguredProviders()
    {
        //
    }

    /**
     * @deprecated Not actually used/has no meaning in Flarum.
     */
    public function registerDeferredProvider($provider, $service = null)
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

    /**
     * @deprecated Not actually used/has no meaning in Flarum.
     */
    public function loadDeferredProviders()
    {
        //
    }

    public function setLocale($locale): void
    {
        $this->make(LocaleManager::class)->setLocale($locale);
    }

    /**
     * @deprecated Not actually used/has no meaning in Flarum.
     */
    public function shouldSkipMiddleware()
    {
        //
    }

    public function joinPaths($basePath, $path = ''): string
    {
        return $basePath.($path != '' ? DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR) : '');
    }
}
