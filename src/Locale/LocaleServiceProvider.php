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
use Symfony\Component\Translation\TranslatorInterface as DeprecatedTranslatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LocaleServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot(Dispatcher $events)
    {
        $events->listen(ClearingCache::class, function () {
            $this->container->make('flarum.locales')->clearCache();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->singleton(LocaleManager::class, function () {
            $locales = new LocaleManager(
                $this->container->make('translator'),
                $this->getCacheDir()
            );

            $locales->addLocale($this->getDefaultLocale(), 'Default');

            return $locales;
        });

        $this->container->alias(LocaleManager::class, 'flarum.locales');

        $this->container->singleton('translator', function () {
            $translator = new Translator(
                $this->getDefaultLocale(),
                null,
                $this->getCacheDir(),
                $this->container['flarum.debug']
            );

            $translator->setFallbackLocales(['en']);
            $translator->addLoader('prefixed_yaml', new PrefixedYamlFileLoader());
            $translator->addResource('prefixed_yaml', ['file' => __DIR__.'/../../locale/core.yml', 'prefix' => null], 'en');
            $translator->addResource('prefixed_yaml', ['file' => __DIR__.'/../../locale/validation.yml', 'prefix' => null], 'en');

            return $translator;
        });

        $this->container->alias('translator', Translator::class);
        $this->container->alias('translator', TranslatorContract::class);
        $this->container->alias('translator', TranslatorInterface::class);
        $this->container->alias('translator', DeprecatedTranslatorInterface::class);
    }

    private function getDefaultLocale(): string
    {
        $repo = $this->container->make(SettingsRepositoryInterface::class);

        return $repo->get('default_locale', 'en');
    }

    private function getCacheDir(): string
    {
        return $this->container[Paths::class]->storage.'/locale';
    }
}
