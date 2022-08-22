<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\Foundation\Config;
use Illuminate\Contracts\Container\Container;
use Illuminate\Session\SessionManager as IlluminateSessionManager;
use Illuminate\Session\Store;
use SessionHandlerInterface;

class SessionManager extends IlluminateSessionManager
{
    protected $flarumConfig;

    public function __construct(Container $container, Config $flarumConfig)
    {
        parent::__construct($container);

        $this->flarumConfig = $flarumConfig;
    }

    public function handler(string $driver = null): SessionHandlerInterface
    {
        /** @var Store $driver */
        $driver = parent::driver($driver ?? $this->flarumConfig['session.driver']);

        return $driver->getHandler();
    }
}
