<?php namespace Flarum\Core\Settings;

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
