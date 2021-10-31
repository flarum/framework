<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Settings;

use Flarum\Foundation\AbstractServiceProvider;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;

class SettingsServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->singleton('flarum.settings.default', function () {
            return new Collection();
        });

        $this->container->singleton(SettingsRepositoryInterface::class, function (Container $container) {
            return new DefaultSettingsRepository(
                new MemoryCacheSettingsRepository(
                    new DatabaseSettingsRepository(
                        $container->make(ConnectionInterface::class)
                    )
                ),
                $container->make('flarum.settings.default')
            );
        });

        $this->container->alias(SettingsRepositoryInterface::class, 'flarum.settings');
    }
}
