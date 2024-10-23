<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend;

use Flarum\Extension\Event\Disabled;
use Flarum\Extension\Event\Enabled;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\Event\ClearingCache;
use Flarum\Foundation\Paths;
use Flarum\Frontend\Compiler\Source\SourceCollector;
use Flarum\Frontend\Driver\BasicTitleDriver;
use Flarum\Frontend\Driver\TitleDriverInterface;
use Flarum\Http\RequestUtil;
use Flarum\Http\SlugManager;
use Flarum\Http\UrlGenerator;
use Flarum\Locale\LocaleManager;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Psr\Http\Message\ServerRequestInterface;

class FrontendServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->singleton('flarum.assets', function (Container $container) {
            return new AssetManager($container, $container->make(LocaleManager::class));
        });

        $this->container->singleton('flarum.assets.factory', function (Container $container) {
            return function (string $name) use ($container) {
                $paths = $container[Paths::class];

                $assets = new Assets(
                    $name,
                    $container->make('filesystem')->disk('flarum-assets'),
                    $paths->storage,
                    null,
                    $container->make('flarum.frontend.custom_less_functions')
                );

                $assets->setLessImportDirs([
                    $paths->vendor.'/components/font-awesome/css' => ''
                ]);

                $assets->css($this->addBaseCss(...));
                $assets->localeCss($this->addBaseCss(...));

                return $assets;
            };
        });

        $this->container->singleton('flarum.frontend.factory', function (Container $container) {
            return function (string $name) use ($container) {
                /** @var Frontend $frontend */
                $frontend = $container->make(Frontend::class);

                $frontend->content(function (Document $document) use ($name) {
                    $document->layoutView = 'flarum::frontend.'.$name;
                }, 200);

                $frontend->content($container->make(Content\Assets::class)->forFrontend($name), 190);
                $frontend->content($container->make(Content\CorePayload::class), 180);
                $frontend->content($container->make(Content\Meta::class), 170);

                $frontend->content(function (Document $document) use ($container) {
                    $default_preloads = $container->make('flarum.frontend.default_preloads');

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
                        $css_preloads,
                        $js_preloads,
                        $default_preloads,
                        $document->preloads,
                    );

                    /** @var SettingsRepositoryInterface $settings */
                    $settings = $container->make(SettingsRepositoryInterface::class);

                    // Add document classes/attributes for design use cases.
                    $document->extraAttributes['data-theme'] = function (ServerRequestInterface $request) use ($settings) {
                        return $settings->get('color_scheme') === 'auto'
                            ? RequestUtil::getActor($request)->getPreference('colorScheme')
                            : $settings->get('color_scheme');
                    };
                    $document->extraAttributes['data-colored-header'] = $settings->get('theme_colored_header') ? 'true' : 'false';
                    $document->extraAttributes['class'][] = function (ServerRequestInterface $request) {
                        return RequestUtil::getActor($request)->isGuest() ? 'guest-user' : 'logged-in';
                    };
                }, 160);

                return $frontend;
            };
        });

        $this->container->singleton(
            'flarum.frontend.default_preloads',
            function (Container $container) {
                $filesystem = $container->make('filesystem')->disk('flarum-assets');

                return [
                    [
                        'href' => $filesystem->url('fonts/fa-solid-900.woff2'),
                        'as' => 'font',
                        'type' => 'font/woff2',
                        'crossorigin' => ''
                    ], [
                        'href' => $filesystem->url('fonts/fa-regular-400.woff2'),
                        'as' => 'font',
                        'type' => 'font/woff2',
                        'crossorigin' => ''
                    ]
                ];
            }
        );

        $this->container->singleton(
            'flarum.frontend.custom_less_functions',
            function (Container $container) {
                $extensionsEnabled = json_decode($container->make(SettingsRepositoryInterface::class)->get('extensions_enabled'));

                // Please note that these functions do not go through the same transformation which the Theme extender's
                // `addCustomLessFunction` method does. You'll need to use the correct Less tree return type, and get
                // parameter values with `$arg->value`.
                return [
                    'is-extension-enabled' => function (\Less_Tree_Quoted $extensionId) use ($extensionsEnabled) {
                        return new \Less_Tree_Quoted('', in_array($extensionId->value, $extensionsEnabled) ? 'true' : 'false');
                    }
                ];
            }
        );

        $this->container->singleton(TitleDriverInterface::class, function (Container $container) {
            return $container->make(BasicTitleDriver::class);
        });

        $this->container->alias(TitleDriverInterface::class, 'flarum.frontend.title_driver');

        $this->container->singleton('flarum.less.config', function (Container $container) {
            return [
                'config-primary-color' => [
                    'key' => 'theme_primary_color',
                ],
                'config-secondary-color' => [
                    'key' => 'theme_secondary_color',
                ],
            ];
        });

        $this->container->singleton(
            'flarum.less.custom_variables',
            function (Container $container) {
                return [];
            }
        );

        $this->container->bind('flarum.assets.common', function (Container $container) {
            /** @var \Flarum\Frontend\Assets $assets */
            $assets = $container->make('flarum.assets.factory')('common');

            $assets->jsDirectory(function (SourceCollector $sources) {
                $sources->addDirectory(__DIR__.'/../../js/dist/common', 'core');
            });

            return $assets;
        });

        $this->container->afterResolving(AssetManager::class, function (AssetManager $assets) {
            $assets->register('common', 'flarum.assets.common');
        });
    }

    public function boot(Container $container, Dispatcher $events, ViewFactory $views): void
    {
        $this->loadViewsFrom(__DIR__.'/../../views', 'flarum');

        $views->share([
            'translator' => $container->make('translator'),
            'url' => $container->make(UrlGenerator::class),
            'slugManager' => $container->make(SlugManager::class)
        ]);

        $events->listen(
            [Enabled::class, Disabled::class, ClearingCache::class],
            function () use ($container) {
                $recompile = new RecompileFrontendAssets(
                    $container->make('flarum.assets.common'),
                    $container->make(LocaleManager::class)
                );
                $recompile->flush();
            }
        );
    }

    public function addBaseCss(SourceCollector $sources): void
    {
        $sources->addFile(__DIR__.'/../../less/common/variables.less');
        $sources->addFile(__DIR__.'/../../less/common/mixins.less');

        $this->addLessVariables($sources);
    }

    private function addLessVariables(SourceCollector $sources): void
    {
        $sources->addString(function () {
            $vars = $this->container->make('flarum.less.config');
            $extDefinedVars = $this->container->make('flarum.less.custom_variables');

            $settings = $this->container->make(SettingsRepositoryInterface::class);

            $customLess = array_reduce(array_keys($vars), function ($string, $name) use ($vars, $settings) {
                $var = $vars[$name];
                $value = $settings->get($var['key'], $var['default'] ?? null);

                if (isset($var['callback'])) {
                    $value = $var['callback']($value);
                }

                return $string."@$name: {$value};";
            }, '');

            foreach ($extDefinedVars as $name => $value) {
                $customLess .= "@$name: {$value()};";
            }

            return $customLess;
        });
    }
}
