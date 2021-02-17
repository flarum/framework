<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Locale;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Event\ClearingCache;
use Flarum\Foundation\Paths;
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
        $events->listen(ClearingCache::class, function () {
            $this->app->make('flarum.locales')->clearCache();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton(LocaleManager::class, function () {
            $locales = new LocaleManager(
                $this->app->make('translator'),
                $this->getCacheDir()
            );

            $locales->addLocale($this->getDefaultLocale(), 'Default');

            return $locales;
        });

        $this->app->alias(LocaleManager::class, 'flarum.locales');

        $this->app->singleton('translator', function () {
            $translator = new Translator(
                $this->getDefaultLocale(),
                null,
                $this->getCacheDir(),
                $this->app['flarum.debug']
            );

            $translator->setFallbackLocales(['en']);
            $translator->addLoader('prefixed_yaml', new PrefixedYamlFileLoader());
            $translator->addResource('prefixed_yaml', ['file' => __DIR__.'/../../locale/core.yml', 'prefix' => null], 'en');
            $translator->addResource('prefixed_yaml', ['file' => __DIR__.'/../../locale/validation.yml', 'prefix' => null], 'en');

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
        return $this->app[Paths::class]->storage.'/locale';
    }
}
