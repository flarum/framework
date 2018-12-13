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
use Flarum\Foundation\Event\ClearingCache;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Symfony\Component\Translation\TranslatorInterface;

class LocaleServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot(Dispatcher $events)
    {
        $locales = $this->app->make('flarum.locales');

        $locales->addLocale($this->getDefaultLocale(), 'Default');

        $events->dispatch(new ConfigureLocales($locales));

        $events->listen(ClearingCache::class, function () use ($locales) {
            $locales->clearCache();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton(LocaleManager::class, function () {
            return new LocaleManager(
                $this->app->make('translator'),
                $this->getCacheDir()
            );
        });

        $this->app->alias(LocaleManager::class, 'flarum.locales');

        $this->app->singleton('translator', function () {
            $translator = new Translator(
                $this->getDefaultLocale(),
                null,
                $this->getCacheDir(),
                $this->app->inDebugMode()
            );

            $translator->setFallbackLocales(['en']);
            $translator->addLoader('prefixed_yaml', new PrefixedYamlFileLoader());

            return $translator;
        });
        $this->app->alias('translator', Translator::class);
        $this->app->alias('translator', TranslatorContract::class);
        $this->app->alias('translator', TranslatorInterface::class);
    }

    private function getDefaultLocale(): string
    {
        $repo = $this->app->make(SettingsRepositoryInterface::class);

        return $repo->get('default_locale', 'en');
    }

    private function getCacheDir(): string
    {
        return $this->app->storagePath().'/locale';
    }
}
