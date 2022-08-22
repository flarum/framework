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
use SessionHandlerInterface;

class SessionManager extends IlluminateSessionManager
{
    public function handler(string $driver = null): SessionHandlerInterface
    {
        $config = $this->container->make(Config::class);

        /** @var Store $driver */
        $driver = parent::driver($driver ?? $config['session.driver']);

        return $driver->getHandler();
    }
}
