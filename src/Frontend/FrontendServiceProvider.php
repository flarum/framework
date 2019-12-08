<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Frontend\Compiler\Source\SourceCollector;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\View\Factory as ViewFactory;

class FrontendServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->app->singleton('flarum.assets.factory', function () {
            return function (string $name) {
                $assets = new Assets(
                    $name,
                    $this->app->make('filesystem')->disk('flarum-assets'),
                    $this->app->storagePath()
                );

                $assets->setLessImportDirs([
                    $this->app->vendorPath().'/components/font-awesome/less' => ''
                ]);

                $assets->css([$this, 'addBaseCss']);
                $assets->localeCss([$this, 'addBaseCss']);

                return $assets;
            };
        });

        $this->app->singleton('flarum.frontend.factory', function () {
            return function (string $name) {
                $frontend = $this->app->make(Frontend::class);

                $frontend->content(function (Document $document) use ($name) {
                    $document->layoutView = 'flarum::frontend.'.$name;
                });

                $frontend->content($this->app->make(Content\Assets::class)->forFrontend($name));
                $frontend->content($this->app->make(Content\CorePayload::class));
                $frontend->content($this->app->make(Content\Meta::class));

                return $frontend;
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

    public function addBaseCss(SourceCollector $sources)
    {
        $sources->addFile(__DIR__.'/../../less/common/variables.less');
        $sources->addFile(__DIR__.'/../../less/common/mixins.less');

        $this->addLessVariables($sources);
    }

    private function addLessVariables(SourceCollector $sources)
    {
        $sources->addString(function () {
            $settings = $this->app->make(SettingsRepositoryInterface::class);

            $vars = [
                'config-primary-color'   => $settings->get('theme_primary_color', '#000'),
                'config-secondary-color' => $settings->get('theme_secondary_color', '#000'),
                'config-dark-mode'       => $settings->get('theme_dark_mode') ? 'true' : 'false',
                'config-colored-header'  => $settings->get('theme_colored_header') ? 'true' : 'false'
            ];

            return array_reduce(array_keys($vars), function ($string, $name) use ($vars) {
                return $string."@$name: {$vars[$name]};";
            }, '');
        });
    }
}
