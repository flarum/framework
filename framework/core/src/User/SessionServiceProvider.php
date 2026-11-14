<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Config;
use Flarum\Http\SessionConfig;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use SessionHandlerInterface;

class SessionServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->singleton('flarum.session.drivers', function () {
            return [];
        });

        $this->container->singleton('session', function (Container $container) {
            $manager = new SessionManager($container);
            $drivers = $container->make('flarum.session.drivers');
            $settings = $container->make(SettingsRepositoryInterface::class);
            $config = $container->make(Config::class);

            /**
             * Default to the file driver already defined by Laravel.
             *
             * @see \Illuminate\Session\SessionManager::createFileDriver()
             */
            $manager->setDefaultDriver('file');

            foreach ($drivers as $driver => $className) {
                /** @var SessionDriverInterface $driverInstance */
                $driverInstance = $container->make($className);

                $manager->extend($driver, function () use ($settings, $config, $driverInstance) {
                    return $driverInstance->build($settings, $config);
                });
            }

            return $manager;
        });

        $this->container->alias('session', SessionManager::class);

        $this->container->singleton('session.handler', function (Container $container): SessionHandlerInterface {
            /** @var SessionManager $manager */
            $manager = $container->make('session');

            return $manager->handler();
        });

        $this->container->alias('session.handler', SessionHandlerInterface::class);

        // A handler decides, server-side, whether a session is still alive, and
        // reads how long that is from Laravel's `session.lifetime` config: the
        // file driver does, and so do handlers an extension binds itself. Put the
        // configured lifetime there before any of them is built.
        $this->container->beforeResolving('session.handler', function () {
            $this->container->make(ConfigRepository::class)->set('session.lifetime', $this->container->make(SessionConfig::class)->lifetime());
        });
    }
}
