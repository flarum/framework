<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager;

use Composer\Config;
use Composer\Console\Application;
use Composer\Util\Platform;
use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Paths;
use Flarum\Frontend\RecompileFrontendAssets;
use Flarum\Locale\LocaleManager;
use Flarum\ExtensionManager\Composer\ComposerAdapter;
use Flarum\ExtensionManager\Event\FlarumUpdated;
use Flarum\ExtensionManager\Extension\Event\Updated;
use Flarum\ExtensionManager\Listener\ClearCacheAfterUpdate;
use Flarum\ExtensionManager\Listener\ReCheckForUpdates;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

class ExtensionManagerServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->container->singleton(ComposerAdapter::class, function (Container $container) {
            // This should only ever be resolved when running composer commands,
            // because we modify other environment configurations.
            $composer = new Application();
            $composer->setAutoExit(false);

            /** @var Paths $paths */
            $paths = $container->make(Paths::class);

            Platform::putenv('COMPOSER_HOME', "$paths->storage/.composer");
            Platform::putenv('COMPOSER', "$paths->base/composer.json");
            Platform::putenv('COMPOSER_DISABLE_XDEBUG_WARN', '1');
            Config::$defaultConfig['vendor-dir'] = $paths->vendor;

            // When running simple require, update and remove commands on packages,
            // composer 2 doesn't really need this much unless the extensions are very loaded dependency wise,
            // but this is necessary for running flarum updates.
            @ini_set('memory_limit', '1G');
            @set_time_limit(5 * 60);

            return new ComposerAdapter(
                $composer,
                $container->make(OutputLogger::class),
                $container->make(Paths::class),
                $container->make(Filesystem::class)
            );
        });

        $this->container->alias(ComposerAdapter::class, 'flarum.composer');

        $this->container->singleton(OutputLogger::class, function (Container $container) {
            $logPath = $container->make(Paths::class)->storage.'/logs/composer/output.log';
            $handler = new RotatingFileHandler($logPath, Logger::INFO);
            $handler->setFormatter(new LineFormatter(null, null, true, true));

            $logger = new Logger('composer', [$handler]);

            return new OutputLogger($logger);
        });
    }

    public function boot(Container $container)
    {
        /** @var Dispatcher $events */
        $events = $container->make('events');

        $events->listen(
            [Updated::class],
            function (Updated $event) use ($container) {
                /** @var ExtensionManager $extensions */
                $extensions = $container->make(ExtensionManager::class);

                if ($extensions->isEnabled($event->extension->getId())) {
                    $recompile = new RecompileFrontendAssets(
                        $container->make('flarum.assets.forum'),
                        $container->make(LocaleManager::class)
                    );
                    $recompile->flush();

                    $extensions->migrate($event->extension);
                    $event->extension->copyAssetsTo($container->make('filesystem')->disk('flarum-assets'));
                }
            }
        );

        $events->listen(FlarumUpdated::class, ClearCacheAfterUpdate::class);
        $events->listen([FlarumUpdated::class, Updated::class], ReCheckForUpdates::class);
    }
}
