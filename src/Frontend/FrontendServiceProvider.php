<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend;

use Flarum\Foundation\AbstractServiceProvider;
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
                $frontend = $container->make(Frontend::class);

                $frontend->content(function (Document $document) use ($name) {
                    $document->layoutView = 'flarum::frontend.'.$name;
                });

                $frontend->content($container->make(Content\Assets::class)->forFrontend($name));
                $frontend->content($container->make(Content\CorePayload::class));
                $frontend->content($container->make(Content\Meta::class));

                return $frontend;
            };
        });

        $this->container->singleton('flarum.less.config', function (Container $container) {
            return [
                'config-primary-color'   => [
                    'key' => 'theme_primary_color',
                    'default' => '#000',
                ],
                'config-secondary-color' => [
                    'key' => 'theme_secondary_color',
                    'default' => '#000',
                ],
                'config-dark-mode'       => [
                    'key' => 'theme_dark_mode',
                    'callback' => function ($value) {
                        return $value ? 'true' : 'false';
                    },
                ],
                'config-colored-header'  => [
                    'key' => 'theme_colored_header',
                    'callback' => function ($value) {
                        return $value ? 'true' : 'false';
                    },
                ],
            ];
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
            $vars = $this->container->make('flarum.less.config');
            $settings = $this->container->make(SettingsRepositoryInterface::class);

            return array_reduce(array_keys($vars), function ($string, $name) use ($vars, $settings) {
                $var = $vars[$name];
                $value = $settings->get($var['key'], $var['default'] ?? null);

                if (isset($var['callback'])) {
                    $value = $var['callback']($value);
                }

                return $string."@$name: {$value};";
            }, '');
        });
    }
}
