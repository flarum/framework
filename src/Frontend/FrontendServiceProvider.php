<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Frontend;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Http\UrlGenerator;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Filesystem\FilesystemAdapter;

class FrontendServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->registerAssetsFilesystem();
    }

    protected function registerAssetsFilesystem()
    {
        $assetsFilesystem = function (Container $app) {
            return $app->make(FilesystemFactory::class)->disk('flarum-assets');
        };

        $this->app->when(FrontendAssetsFactory::class)
            ->needs(FilesystemAdapter::class)
            ->give($assetsFilesystem);
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../../views', 'flarum');

        $this->app->make(ViewFactory::class)->share([
            'translator' => $this->app->make('translator'),
            'url' => $this->app->make(UrlGenerator::class)
        ]);
    }
}
