<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Settings;

use Flarum\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('Flarum\Core\Settings\SettingsRepository', function () {
            return new MemoryCacheSettingsRepository(
                new DatabaseSettingsRepository(
                    $this->app->make('Illuminate\Database\ConnectionInterface')
                )
            );
        });
    }
}
