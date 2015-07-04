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
        $this->commands('Flarum\Console\ImportCommand');
        $this->commands('Flarum\Console\GenerateExtensionCommand');
    }

    public function register()
    {
    }
}
