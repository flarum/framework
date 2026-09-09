<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Locale;

use Flarum\Extension\Event\Disabled;
use Flarum\Extension\Event\Enabled;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Event\ClearingCache;
use Flarum\Foundation\Paths;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Symfony\Contracts\Translation\TranslatorInterface as SymfonyTranslatorInterface;

class LocaleServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(LocaleManager::class, function (Container $container) {
            $locales = new LocaleManager(
                $container->make('translator'),
                $this->getCacheDir($container),
                $container->make(SettingsRepositoryInterface::class)
            );

            $locales->addLocale($this->getDefaultLocale($container), 'Default');
            $locales->addTranslations('en', __DIR__.'/../../locale/core.yml');
            $locales->addTranslations('en', __DIR__.'/../../locale/validation.yml');

            return $locales;
        });

        $this->container->alias(LocaleManager::class, 'flarum.locales');

        $this->container->singleton('translator', function (Container $container) {
            $translator = new Translator(
                $this->getDefaultLocale($container),
                null,
                $this->getCacheDir($container),
                $container['flarum.debug']
            );

            $translator->setFallbackLocales(['en']);
            $translator->addLoader('prefixed_yaml', new PrefixedYamlFileLoader());

            // Symfony would otherwise treat a compiled catalogue as current for
            // as long as the file exists, whatever has changed behind it.
            // Resolved lazily: the manager is built from this translator, and
            // the files are registered on it afterwards.
            $translator->setConfigCacheFactory(new CatalogueCacheFactory(
                fn () => $container->make(LocaleManager::class)->revision()
            ));

            return $translator;
        });

        $this->container->alias('translator', Translator::class);
        $this->container->alias('translator', TranslatorContract::class);
        $this->container->alias('translator', SymfonyTranslatorInterface::class);
        $this->container->alias('translator', TranslatorInterface::class);
    }

    public function boot(Container $container, Dispatcher $events): void
    {
        // Bump the locale revision whenever translations may have changed in a
        // way the registered files cannot show.
        //
        // Toggling an extension changes the file list, so every instance works
        // that out for itself. Clearing the cache does not: it deletes the
        // catalogues on the instance that ran it and leaves every other
        // instance with files it still considers fresh. The stamp is what
        // carries "rebuild these" to instances that were never told anything —
        // they compare it on their next request and rebuild then.
        $events->listen(
            [Enabled::class, Disabled::class, ClearingCache::class],
            function () use ($container) {
                $container->make(SettingsRepositoryInterface::class)
                    ->set(LocaleManager::REVISION_STAMP_KEY, (string) microtime(true));
            }
        );
    }

    private function getDefaultLocale(Container $container): string
    {
        $repo = $container->make(SettingsRepositoryInterface::class);

        return $repo->get('default_locale', 'en');
    }

    private function getCacheDir(Container $container): string
    {
        return $container[Paths::class]->storage.'/locale';
    }
}
