<?php

/**
 *
 */

namespace SychO\PackageManager;

use Composer\Config;
use Composer\Console\Application;
use Flarum\Extension\Extension;
use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Paths;
use Flarum\Frontend\RecompileFrontendAssets;
use Flarum\Locale\LocaleManager;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use SychO\PackageManager\Event\FlarumUpdated;
use SychO\PackageManager\Extension\Event\Installed;
use SychO\PackageManager\Extension\Event\Updated;
use SychO\PackageManager\Listener\PostUpdateListener;

class PackageManagerServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->container->singleton(Application::class, function (Container $container) {
            // This should only ever be resolved when running composer commands,
            // because we modify other environment configurations.
            $composer = new Application();
            $composer->setAutoExit(false);

            $paths = $container->make(Paths::class);

            putenv("COMPOSER_HOME={$paths->storage}/.composer");
            putenv("COMPOSER={$paths->base}/composer.json");
            Config::$defaultConfig['vendor-dir'] = $paths->base.'/vendor';

            // When running simple require, update and remove commands on packages,
            // composer 2 doesn't really need this much unless the extensions are very loaded dependency wise,
            // but this is necessary for running flarum updates.
            @ini_set('memory_limit', '1G');
            @set_time_limit(5 * 60);

            return $composer;
        });

        $this->container->alias(Application::class, 'flarum.composer');
    }

    public function boot(Container $container)
    {
        /** @var Dispatcher $events */
        $events = $container->make('events');

        $events->listen(
            [Updated::class],
            function (Updated $event) use ($container) {
                $recompile = new RecompileFrontendAssets(
                    $container->make('flarum.assets.forum'),
                    $container->make(LocaleManager::class)
                );
                $recompile->flush();

                $container->make(ExtensionManager::class)->migrate($event->extension);
                $event->extension->copyAssetsTo($container->make('filesystem')->disk('flarum-assets'));
            }
        );

        $events->listen(FlarumUpdated::class, PostUpdateListener::class);
    }
}
