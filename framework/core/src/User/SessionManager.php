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
use Illuminate\Session\Store;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use SessionHandlerInterface;

class SessionManager extends IlluminateSessionManager
{
    public function handler(string $driver = null): SessionHandlerInterface
    {
        $config = $this->container->make(Config::class);

        /** @var Store $driver */
        try {
            $driverInstance = parent::driver($driver ?? $config['session.driver']);
        } catch (InvalidArgumentException $e) {
            if (! $driver) {
                // If we're expecting the default driver, and it's not available,
                // then we'll fall back to the default driver.
                $driverInstance = parent::driver($this->getDefaultDriver());

                // But we will log a critical error to the webmaster.
                $this->container->make(LoggerInterface::class)->critical(
                    'The default session driver is not available. Please check your configuration.'
                );
            } else {
                throw $e;
            }
        }

        return $driverInstance->getHandler();
    }
}
