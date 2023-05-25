<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Flarum\Mail\DriverInterface;
use Illuminate\Contracts\Container\Container;

class Mail implements ExtenderInterface
{
    private array $drivers = [];

    /**
     * Add a mail driver.
     *
     * @param string $identifier: Identifier for mail driver. E.g. 'smtp' for SmtpDriver.
     * @param class-string<DriverInterface> $driver: ::class attribute of driver class, which must implement \Flarum\Mail\DriverInterface.
     * @return self
     */
    public function driver(string $identifier, string $driver): self
    {
        $this->drivers[$identifier] = $driver;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null): void
    {
        $container->extend('mail.supported_drivers', function ($existingDrivers) {
            return array_merge($existingDrivers, $this->drivers);
        });
    }
}
