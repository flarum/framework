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
    /**
     * @var Paths
     */
    protected $paths;

    /**
     * @var string
     */
    private $baseUrl;

    public function __construct(Paths $paths, string $baseUrl)
    {
        $this->paths = $paths;
        $this->baseUrl = $baseUrl;
    }

    /**
     * Create and boot a Flarum application instance.
     *
     * @return AppInterface
     */
    public function bootApp(): AppInterface
    {
        return new Installer(
            $this->bootLaravel()
        );
    }

    protected function bootLaravel(): Container
    {
        $container = new \Illuminate\Container\Container;
        $laravel = new Application($container, $this->paths);

        $container->instance('env', 'production');
        $container->instance('flarum.config', new Config(['url' => $this->baseUrl]));
        $container->alias('flarum.config', Config::class);
        $container->instance('flarum.debug', true);
        $container->instance('config', $config = $this->getIlluminateConfig());

        $this->registerLogger($container);

        $laravel->register(ErrorServiceProvider::class);
        $laravel->register(LocaleServiceProvider::class);
        $laravel->register(FilesystemServiceProvider::class);
        $laravel->register(SessionServiceProvider::class);
        $laravel->register(ValidationServiceProvider::class);

        $laravel->register(InstallServiceProvider::class);

        $container->singleton(
            SettingsRepositoryInterface::class,
            UninstalledSettingsRepository::class
        );

        $container->singleton('view', function ($app) {
            $engines = new EngineResolver();
            $engines->register('php', function () {
                return new PhpEngine();
            });
            $finder = new FileViewFinder($app->make('files'), []);
            $dispatcher = $app->make(Dispatcher::class);

            return new \Illuminate\View\Factory(
                $engines,
                $finder,
                $dispatcher
            );
        });

        $laravel->boot();

        return $container;
    }

    /**
     * @return ConfigRepository
     */
    protected function getIlluminateConfig()
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

    protected function registerLogger(Container $container)
    {
        $logPath = $this->paths->storage.'/logs/flarum-installer.log';
        $handler = new StreamHandler($logPath, Logger::DEBUG);
        $handler->setFormatter(new LineFormatter(null, null, true, true));

        $container->instance('log', new Logger('Flarum Installer', [$handler]));
        $container->alias('log', LoggerInterface::class);
    }
}
