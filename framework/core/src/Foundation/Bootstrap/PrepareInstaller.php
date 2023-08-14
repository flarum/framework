<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\Bootstrap;

use Flarum\Foundation\ErrorServiceProvider;
use Flarum\Install\InstallServiceProvider;
use Flarum\Locale\LocaleServiceProvider;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Settings\UninstalledSettingsRepository;
use Flarum\User\SessionServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\FileViewFinder;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class PrepareInstaller implements IlluminateBootstrapperInterface
{
    public function bootstrap(Application $app): void
    {
        $app->register(ErrorServiceProvider::class);
        $app->register(LocaleServiceProvider::class);
        $app->register(FilesystemServiceProvider::class);
        $app->register(SessionServiceProvider::class);
        $app->register(ValidationServiceProvider::class);

        $app->register(InstallServiceProvider::class);

        $this->registerLogger($app);

        $app->singleton(
            SettingsRepositoryInterface::class,
            UninstalledSettingsRepository::class
        );

        $app->singleton('view', function ($app) {
            $engines = new EngineResolver();
            $engines->register('php', function () use ($app) {
                return $app->make(PhpEngine::class);
            });
            $finder = new FileViewFinder($app->make('files'), []);
            $dispatcher = $app->make(Dispatcher::class);

            return new \Illuminate\View\Factory(
                $engines,
                $finder,
                $dispatcher
            );
        });
    }

    protected function registerLogger(Application $app): void
    {
        /** @var \Flarum\Foundation\Paths $paths */
        $paths = $app['flarum.paths'];

        $logPath = $paths->storage.'/logs/flarum-installer.log';
        $handler = new StreamHandler($logPath, Level::Debug);
        $handler->setFormatter(new LineFormatter(null, null, true, true));

        $app->instance('log', new Logger('Flarum Installer', [$handler]));
        $app->alias('log', LoggerInterface::class);
    }
}
