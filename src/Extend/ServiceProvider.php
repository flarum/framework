<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Illuminate\Contracts\Container\Container;

class ServiceProvider implements ExtenderInterface
{
    private $providers = [];

    /**
     * Register a service provider.
     *
     * @param string $serviceProviderClass The ::class attribute of the service provider class.
     * @return self
     */
    public function register(string $serviceProviderClass)
    {
        $this->providers[] = $serviceProviderClass;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $app = $container->make('flarum');

        foreach ($this->providers as $provider) {
            $app->register($provider);
        }
    }
}
