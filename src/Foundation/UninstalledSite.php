<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Flarum\Install\Installer;
use Flarum\Install\InstallServiceProvider;
use Flarum\Locale\LocaleServiceProvider;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Settings\UninstalledSettingsRepository;
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
     * @var string
     */
    protected $basePath;

    /**
     * @var string
     */
    protected $publicPath;

    /**
     * @var string
     */
    protected $storagePath;

    public function __construct($basePath, $publicPath)
    {
        $this->basePath = $basePath;
        $this->publicPath = $publicPath;
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
        $laravel = new Application($this->basePath, $this->publicPath);

        if ($this->storagePath) {
            $laravel->useStoragePath($this->storagePath);
        }

        $laravel->instance('env', 'production');
        $laravel->instance('flarum.config', []);
        $laravel->instance('config', $config = $this->getIlluminateConfig($laravel));

        $this->registerLogger($laravel);

        $laravel->register(LocaleServiceProvider::class);
        $laravel->register(FilesystemServiceProvider::class);
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
                $engines, $finder, $dispatcher
            );
        });

        $laravel->boot();

        return $laravel;
    }

    /**
     * @param Application $app
     * @return ConfigRepository
     */
    protected function getIlluminateConfig(Application $app)
    {
        return new ConfigRepository([
            'session' => [
                'lifetime' => 120,
                'files' => $app->storagePath().'/sessions',
                'cookie' => 'session'
            ]
        ]);
    }

    protected function registerLogger(Application $app)
    {
        $logPath = $app->storagePath().'/logs/flarum-installer.log';
        $handler = new StreamHandler($logPath, Logger::DEBUG);
        $handler->setFormatter(new LineFormatter(null, null, true, true));

        $app->instance('log', new Logger('Flarum Installer', [$handler]));
        $app->alias('log', LoggerInterface::class);
    }
}
