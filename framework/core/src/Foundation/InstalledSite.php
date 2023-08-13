<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Flarum\Extend\ExtenderInterface;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;

class InstalledSite implements SiteInterface
{
    /**
     * @var ExtenderInterface[]
     */
    protected array $extenders = [];

    public function __construct(
        protected Paths $paths,
        protected Config $config
    ) {
    }

    /**
     * Create and boot a Flarum application instance.
     *
     * @return InstalledApp
     */
    public function init(): AppInterface
    {
        return new InstalledApp(
            $this->createApp()
        );
    }

    /**
     * @param ExtenderInterface[] $extenders
     * @return InstalledSite
     */
    public function extendWith(array $extenders): self
    {
        $this->extenders = $extenders;

        return $this;
    }

    protected function createApp(): ApplicationContract
    {
        $app = new Application($this->paths);

        $app->instance('env', $this->config->environment());
        $app->instance('flarum.config', $this->config);
        $app->instance('flarum.debug', $this->config->inDebugMode());
        $app->instance('config', $this->getIlluminateConfig());

        $app->booting(function () use ($app) {
            // Run all local-site extenders before booting service providers
            // (but after those from "real" extensions, which have been set up
            // in a service provider above).
            foreach ($this->extenders as $extension) {
                $extension->extend($app);
            }
        });

        return $app;
    }

    public function bootstrappers(): array
    {
        return [
            \Flarum\Foundation\Bootstrap\RegisterMaintenanceHandler::class,
            \Flarum\Foundation\Bootstrap\RegisterLogger::class,
            \Flarum\Foundation\Bootstrap\RegisterCache::class,
            \Flarum\Foundation\Bootstrap\RegisterCoreProviders::class,
            \Flarum\Foundation\Bootstrap\BootProviders::class,
        ];
    }

    protected function getIlluminateConfig(): ConfigRepository
    {
        return new ConfigRepository([
            'app' => [
                'timezone' => 'UTC'
            ],
            'view' => [
                'paths' => [],
                'compiled' => $this->paths->storage.'/views',
            ],
            'session' => [
                'lifetime' => 120,
                'files' => $this->paths->storage.'/sessions',
                'cookie' => 'session'
            ]
        ]);
    }
}
