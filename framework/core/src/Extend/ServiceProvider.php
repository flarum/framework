<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Flarum\Foundation\AbstractServiceProvider;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider implements ExtenderInterface
{
    private array $providers = [];

    /**
     * Register a service provider.
     *
     * Service providers are an advanced feature and might give access to Flarum internals that do not come with backward compatibility.
     * Please read our documentation about service providers for recommendations.
     * @see https://docs.flarum.org/extend/service-provider/
     *
     * @param class-string<AbstractServiceProvider|LaravelServiceProvider> $serviceProviderClass The ::class attribute of the service provider class.
     */
    public function register(string $serviceProviderClass): self
    {
        $this->providers[] = $serviceProviderClass;

        return $this;
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
        $app = $container->make('flarum');

        foreach ($this->providers as $provider) {
            $app->register($provider);
        }
    }
}
