<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Flarum\User\SessionDriverInterface;
use Illuminate\Contracts\Container\Container;

class Session implements ExtenderInterface
{
    private array $drivers = [];

    /**
     * Register a new session driver.
     *
     * A driver can currently be selected by setting `session.driver` in `config.php`.
     *
     * @param string $name: The name of the driver.
     * @param class-string<SessionDriverInterface> $driverClass: The ::class attribute of the driver.
     *                             Driver must implement `\Flarum\User\SessionDriverInterface`.
     * @return self
     */
    public function driver(string $name, string $driverClass): self
    {
        $this->drivers[$name] = $driverClass;

        return $this;
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
        $container->extend('flarum.session.drivers', function ($drivers) {
            return array_merge($drivers, $this->drivers);
        });
    }
}
