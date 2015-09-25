<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Locale;

use Flarum\Events\RegisterLocales;
use Flarum\Support\ServiceProvider;

class LocaleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $manager = $this->app->make('flarum.localeManager');

        event(new RegisterLocales($manager));
    }

    public function register()
    {
        $this->app->singleton('Flarum\Locale\LocaleManager');

        $this->app->alias('Flarum\Locale\LocaleManager', 'flarum.localeManager');

        $this->app->instance('translator', new Translator);
    }
}
