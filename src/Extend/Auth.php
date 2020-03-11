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
use Illuminate\Events\Dispatcher;

class Auth implements ExtenderInterface
{
    protected $addAuthDrivers = [];

    protected $addAuthLifecycleHandlers = [];

    /**
     * @param string $identifier URL-friendly, lowercase identifier (ex. 'github', 'saml', 'google', 'facebook', 'wechat')
     * @param $driver Class attribute of driver that implements Flarum\Forum\Auth\ExternalAuthDriverInterface
     */
    public function addAuthDriver($identifier, $driver)
    {
        $this->addAuthDrivers[$identifier] = $driver;

        return $this;
    }

    /**
     * @param $handler class attribute of Auth Lifecycle Handler that extends Flarum\User\AbstractAuthLifecycleHandler
     */
    public function addAuthLifecycleHandler($handler)
    {
        $this->addAuthLifecycleHandlers[] = $handler;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend("flarum.auth.supported_drivers", function ($existingMiddleware) {
            return array_merge($existingMiddleware, $this->addAuthDrivers);
        });

        $events = $container->make(Dispatcher::class);

        foreach ($this->addAuthLifecycleHandlers as $handler) {
            $events->subscribe($container->make($handler));
        }
    }
}
