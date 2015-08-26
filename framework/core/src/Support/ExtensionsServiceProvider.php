<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Support;

class ExtensionsServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('flarum.extensions', 'Flarum\Support\ExtensionManager');

        $config = $this->app->make('Flarum\Core\Settings\SettingsRepository')->get('extensions_enabled');
        $extensions = json_decode($config, true);
        $providers = [];

        foreach ($extensions as $extension) {
            if (file_exists($file = public_path().'/extensions/'.$extension.'/bootstrap.php')) {
                $providerName = require $file;
                $providers[$extension] = $this->app->register($providerName);
            }
        }

        $events = $this->app->make('events');

        foreach ($providers as $provider) {
            $provider->listen($events);
        }
    }
}
