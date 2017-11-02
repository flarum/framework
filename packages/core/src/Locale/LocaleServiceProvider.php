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
use Illuminate\Translation\Translator;
use Illuminate\Translation\FileLoader;
//use Symfony\Component\Translation\MessageSelector;
//use Symfony\Component\Translation\Translator;
//use Symfony\Component\Translation\TranslatorInterface;

class LocaleServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot(Dispatcher $events)
    {
        $locales = $this->app->make('flarum.localeManager');

        $locales->addLocale($this->getDefaultLocale(), 'Default');

        $events->fire(new ConfigureLocales($locales));
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton(LocaleManager::class);
        $this->app->alias(LocaleManager::class, 'flarum.localeManager');

        $this->app->singleton('translator', function () {
            $defaultLocale = $this->getDefaultLocale();

            $translator = new Translator($defaultLocale, new MessageSelector());
            $translator->setFallbackLocales([$defaultLocale, 'en']);
            $translator->addLoader('prefixed_yaml', new PrefixedYamlFileLoader());

            return $translator;
        });
        $this->app->alias('translator', Translator::class);
        $this->app->alias('translator', TranslatorInterface::class);
    }

    private function getDefaultLocale()
    {
        return $this->app->isInstalled() && $this->app->isUpToDate()
            ? $this->app->make('flarum.settings')->get('default_locale', 'en')
            : 'en';
    }
}
