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
                $driverInstance = parent::driver($this->getDefaultDriver());
            } else {
                throw $e;
            }
        }

        return $driverInstance->getHandler();
    }
}
