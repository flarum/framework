<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Config;
use Flarum\Foundation\Paths;
use Flarum\Frontend\Compiler\Source\SourceCollector;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\View\Factory as ViewFactory;

class FrontendServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->container->singleton('flarum.assets.factory', function (Container $container) {
            return function (string $name) use ($container) {
                $paths = $container[Paths::class];

                $assets = new Assets(
                    $name,
                    $container->make('filesystem')->disk('flarum-assets'),
                    $paths->storage
                );

                $assets->setLessImportDirs([
                    $paths->vendor.'/components/font-awesome/less' => ''
                ]);

                $assets->css([$this, 'addBaseCss']);
                $assets->localeCss([$this, 'addBaseCss']);

                return $assets;
            };
        });

        $this->container->singleton('flarum.frontend.factory', function (Container $container) {
            return function (string $name) use ($container) {
                $config = $container[Config::class];

                $frontend = $container->make(Frontend::class);

                $frontend->content(function (Document $document) use ($name) {
                    $document->layoutView = 'flarum::frontend.'.$name;
                });

                $frontend->content($container->make(Content\Assets::class)->forFrontend($name));
                $frontend->content($container->make(Content\CorePayload::class));
                $frontend->content($container->make(Content\Meta::class));

                $frontend->content(function (Document $document) use ($config) {
                    $fontawesome_preloads = [
                        [
                            'href' => $config->url()->getPath().'/assets/fonts/fa-solid-900.woff2',
                            'as' => 'font',
                            'type' => 'font/woff2',
                            'crossorigin' => ''
                        ], [
                            'href' => $config->url()->getPath().'/assets/fonts/fa-regular-400.woff2',
                            'as' => 'font',
                            'type' => 'font/woff2',
                            'crossorigin' => ''
                        ]
                    ];

                    // Add preloads for base CSS and JS assets. Extensions should add their own via the extender.
                    $js_preloads = [];
                    $css_preloads = [];

                    foreach ($document->css as $url) {
                        $css_preloads[] = [
                            'href' => $url,
                            'as' => 'style'
                        ];
                    }
                    foreach ($document->js as $url) {
                        $css_preloads[] = [
                            'href' => $url,
                            'as' => 'script'
                        ];
                    }


                    $document->preloads = array_merge(
                        $document->preloads,
                        $fontawesome_preloads,
                        $css_preloads,
                        $js_preloads,
                    );
                });

                return $frontend;
            };
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Container $container, ViewFactory $views)
    {
        $this->loadViewsFrom(__DIR__.'/../../views', 'flarum');

        $views->share([
            'translator' => $container->make('translator'),
            'url' => $container->make(UrlGenerator::class)
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
            $settings = $this->container->make(SettingsRepositoryInterface::class);

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
