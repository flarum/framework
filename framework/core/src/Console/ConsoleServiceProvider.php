<?php namespace Flarum\Console;

use Illuminate\Support\ServiceProvider;

class ConsoleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->commands('Flarum\Console\InstallCommand');
        $this->commands('Flarum\Console\SeedCommand');
    }

    public function register()
    {
    }
}
