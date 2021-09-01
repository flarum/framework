<?php

namespace SychO\PackageManager;

use Flarum\Foundation\Paths;
use Illuminate\Contracts\Container\Container;
use SychO\PackageManager\Composer\ComposerEnvironment;
use Flarum\Foundation\AbstractServiceProvider;
use Illuminate\Filesystem\Filesystem;

class ComposerEnvironmentProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->container->singleton(ComposerEnvironment::class, function(Container $container) {
            return new ComposerEnvironment(
                $container->make(Paths::class)->base,
                $container->make(Paths::class)->storage.'/composer-home',
                $container->make(Filesystem::class),
                $container->make(Paths::class)
            );
        });
    }
}
