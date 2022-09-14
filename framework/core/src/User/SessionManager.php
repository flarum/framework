<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\Foundation\Config;
use Illuminate\Session\SessionManager as IlluminateSessionManager;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use SessionHandlerInterface;

class SessionManager extends IlluminateSessionManager
{
    /**
     * Returns the configured session handler.
     * Picks up the driver from `config.php` using the `session.driver` item.
     * Falls back to the default driver if the configured one is not available,
     *  and logs a critical error in that case.
     */
    public function handler(): SessionHandlerInterface
    {
        $config = $this->container->make(Config::class);
        $driverName = Arr::get($config, 'session.driver');

        try {
            $driverInstance = parent::driver($driverName);
        } catch (InvalidArgumentException $e) {
            $defaultDriverName = $this->getDefaultDriver();
            $driverInstance = parent::driver($defaultDriverName);

            // But we will log a critical error to the webmaster.
            $this->container->make(LoggerInterface::class)->critical(
                "The configured session driver [$driverName] is not available. Falling back to default [$defaultDriverName]. Please check your configuration."
            );
        }

        return $driverInstance->getHandler();
    }
}
