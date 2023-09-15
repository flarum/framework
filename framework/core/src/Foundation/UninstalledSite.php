<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Flarum\Install\Installer;
use Flarum\Install\InstallServiceProvider;
use Flarum\Locale\LocaleServiceProvider;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Settings\UninstalledSettingsRepository;
use Flarum\User\SessionServiceProvider;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\FileViewFinder;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class UninstalledSite implements SiteInterface
{
    public function __construct(
        protected Paths $paths,
        private readonly string $baseUrl
    ) {
    }

    /**
     * Create and boot a Flarum application instance.
     */
    public function bootApp(): AppInterface
    {
        return new Installer(
            $this->bootLaravel()
        );
    }

    protected function bootLaravel(): Container
    {
        $app = new Application($this->paths);

        $app->instance('env', 'production');
        $app->instance('flarum.config', new Config(['url' => $this->baseUrl]));
        $app->alias('flarum.config', Config::class);
        $app->instance('flarum.debug', true);
        $app->instance('config', $config = $this->getIlluminateConfig());

        $this->registerLogger($app);

        $app->register(ErrorServiceProvider::class);
        $app->register(LocaleServiceProvider::class);
        $app->register(FilesystemServiceProvider::class);
        $app->register(SessionServiceProvider::class);
        $app->register(ValidationServiceProvider::class);

        $app->register(InstallServiceProvider::class);

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

        $app->boot();

        return $app;
    }

    protected function getIlluminateConfig(): ConfigRepository
    {
        return new ConfigRepository([
            'session' => [
                'lifetime' => 120,
                'files' => $this->paths->storage.'/sessions',
                'cookie' => 'session'
            ],
            'view' => [
                'paths' => [],
            ],
        ]);
    }

    protected function registerLogger(Container $container): void
    {
        $logPath = $this->paths->storage.'/logs/flarum-installer.log';
        $handler = new StreamHandler($logPath, Logger::DEBUG);
        $handler->setFormatter(new LineFormatter(null, null, true, true));

        $container->instance('log', new Logger('Flarum Installer', [$handler]));
        $container->alias('log', LoggerInterface::class);
    }
}
