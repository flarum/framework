<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Settings;

use Flarum\Foundation\AbstractServiceProvider;
use Illuminate\Database\ConnectionInterface;

class SettingsServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton(SettingsRepositoryInterface::class, function () {
            return new MemoryCacheSettingsRepository(
                new DatabaseSettingsRepository(
                    $this->app->make(ConnectionInterface::class)
                )
            );
        });

        $this->app->alias(SettingsRepositoryInterface::class, 'flarum.settings');
    }
}
