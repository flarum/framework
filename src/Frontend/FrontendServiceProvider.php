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
use Illuminate\Contracts\View\Factory as ViewFactory;

class FrontendServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        // Yo dawg, I heard you like factories, so I made you a factory to
        // create your factory. We expose a couple of factory functions that
        // will create frontend factories and configure them with some default
        // settings common to both the forum and admin frontends.

        $this->app->singleton('flarum.frontend.assets.defaults', function () {
            return function (string $name) {
                $assets = new CompilerFactory(
                    $name,
                    $this->app->make('filesystem')->disk('flarum-assets'),
                    $this->app->storagePath()
                );

                $assets->setLessImportDirs([
                    $this->app->basePath().'/vendor/components/font-awesome/less' => ''
                ]);

                $assets->add(function () use ($name) {
                    $translations = $this->app->make(Asset\Translations::class);
                    $translations->setFilter(function (string $id) use ($name) {
                        return preg_match('/^.+(?:\.|::)(?:'.$name.'|lib)\./', $id);
                    });

                    return [
                        new Asset\CoreAssets($name),
                        $this->app->make(Asset\LessVariables::class),
                        $translations,
                        $this->app->make(Asset\LocaleAssets::class)
                    ];
                });

                return $assets;
            };
        });

        $this->app->singleton('flarum.frontend.view.defaults', function () {
            return function (string $name) {
                $view = $this->app->make(HtmlDocumentFactory::class);

                $view->setCommitAssets($this->app->inDebugMode());

                $view->add(new Content\Layout('flarum::frontend.'.$name));
                $view->add($this->app->make(Content\CorePayload::class));
                $view->add($this->app->make(Content\Meta::class));

                return $view;
            };
        });
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
