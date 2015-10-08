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

use Flarum\Event\ConfigureLocales;
use Flarum\Foundation\AbstractServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;

class LocaleServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot(Dispatcher $events)
    {
        $manager = $this->app->make('flarum.localeManager');

        $events->fire(new ConfigureLocales($manager));
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton('Flarum\Locale\LocaleManager');

        $this->app->alias('Flarum\Locale\LocaleManager', 'flarum.localeManager');

        $this->app->instance('translator', new Translator);
    }
}
