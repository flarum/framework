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
     * @var array
     */
    private $paths;

    public function __construct(array $paths)
    {
        $this->paths = $paths;
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

    private function bootLaravel(): Application
    {
        $laravel = new Application($this->paths['base'], $this->paths['public']);

        $laravel->useStoragePath($this->paths['storage']);

        if (isset($this->paths['vendor'])) {
            $laravel->useVendorPath($this->paths['vendor']);
        }

        $laravel->instance('env', 'production');
        $laravel->instance('flarum.config', []);
        $laravel->instance('config', $config = $this->getIlluminateConfig());

        $this->registerLogger($laravel);

        $laravel->register(ErrorServiceProvider::class);
        $laravel->register(LocaleServiceProvider::class);
        $laravel->register(FilesystemServiceProvider::class);
        $laravel->register(SessionServiceProvider::class);
        $laravel->register(ValidationServiceProvider::class);

        $laravel->register(InstallServiceProvider::class);

        $laravel->singleton(
            SettingsRepositoryInterface::class,
            UninstalledSettingsRepository::class
        );

        $laravel->singleton('view', function ($app) {
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

        return $laravel;
    }

    /**
     * @return ConfigRepository
     */
    private function getIlluminateConfig()
    {
        return new ConfigRepository([
            'session' => [
                'lifetime' => 120,
                'files' => $this->paths['storage'].'/sessions',
                'cookie' => 'session'
            ],
            'view' => [
                'paths' => [],
            ],
        ]);
    }

    private function registerLogger(Application $app)
    {
        $logPath = $this->paths['storage'].'/logs/flarum-installer.log';
        $handler = new StreamHandler($logPath, Logger::DEBUG);
        $handler->setFormatter(new LineFormatter(null, null, true, true));

        $app->instance('log', new Logger('Flarum Installer', [$handler]));
        $app->alias('log', LoggerInterface::class);
    }
}
