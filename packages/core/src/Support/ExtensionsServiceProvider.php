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

        $events = $this->app->make('events');

        foreach ($extensions as $extension) {
            if (file_exists($file = public_path().'/extensions/'.$extension.'/bootstrap.php')) {
                $provider = require $file;

                if (is_string($provider)) {
                    $this->app->register($provider)->listen($events);
                } elseif (is_callable($provider)) {
                    $provider($events);
                }
            }
        }
    }
}
