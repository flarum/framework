<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Flarum\Install\Installer;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;

class UninstalledSite implements SiteInterface
{
    public function __construct(
        protected Paths $paths,
        private readonly string $baseUrl
    ) {
    }

    public function init(): AppInterface
    {
        return new Installer(
            $this->createApp()
        );
    }

    protected function createApp(): ApplicationContract
    {
        $app = new Application($this->paths);

        $app->instance('env', 'production');
        $app->instance('flarum.config', new Config(['url' => $this->baseUrl]));
        $app->alias('flarum.config', Config::class);
        $app->instance('flarum.debug', true);
        $app->instance('config', $this->getIlluminateConfig());

        return $app;
    }

    public function bootstrappers(): array
    {
        return [
            \Flarum\Foundation\Bootstrap\PrepareInstaller::class,
            \Flarum\Foundation\Bootstrap\BootProviders::class,
        ];
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
}
