<?php

namespace Flarum\Foundation\Bootstrap;

use Illuminate\Cache\FileStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;

class RegisterCache implements IlluminateBootstrapperInterface
{
    public function bootstrap(Application $app): void
    {
        /** @var \Flarum\Foundation\Paths $paths */
        $paths = $app['flarum.paths'];

        $app->singleton('cache.store', function ($app) {
            return new CacheRepository($app->make('cache.filestore'));
        });

        $app->singleton('cache.filestore', function () use ($paths) {
            return new FileStore(new Filesystem, $paths->storage.'/cache');
        });
    }
}
