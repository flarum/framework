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
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Translator;

class LocaleServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot(Dispatcher $events)
    {
        $locales = $this->app->make('flarum.localeManager');

        $events->fire(new ConfigureLocales($locales));
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton('Flarum\Locale\LocaleManager');
        $this->app->alias('Flarum\Locale\LocaleManager', 'flarum.localeManager');

        $this->app->singleton('translator', function () {
            $defaultLocale = $this->app->isInstalled() ? $this->app->make('flarum.settings')->get('default_locale') : 'en';

            $translator = new Translator($defaultLocale, new MessageSelector());
            $translator->setFallbackLocales([$defaultLocale]);
            $translator->addLoader('yaml', new YamlFileLoader());

            return $translator;
        });
        $this->app->alias('translator', 'Symfony\Component\Translation\Translator');
        $this->app->alias('translator', 'Symfony\Component\Translation\TranslatorInterface');
    }
}
