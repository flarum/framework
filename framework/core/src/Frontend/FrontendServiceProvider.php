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
use Flarum\Foundation\FontAwesome;
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

                // Always include FontAwesome LESS import paths
                // Even when using CDN/Kit, we need the base CSS classes compiled into the bundle
                // The CDN/Kit will override font-face declarations but class definitions remain the same
                $assets->setLessImportDirs([
                    $paths->vendor.'/fortawesome/font-awesome/css' => ''
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

                    // CSS files are preloaded via the async <link rel="preload" as="style"> tags
                    // that makeHead() now emits directly — no need to duplicate them here.
                    // JS files still get explicit preload hints so the browser fetches them early.
                    $js_preloads = [];

                    foreach ($document->js as $url) {
                        $js_preloads[] = [
                            'href' => $url,
                            'as' => 'script',
                            'fetchpriority' => 'low',
                        ];
                    }

                    $document->preloads = array_merge(
                        $js_preloads,
                        $default_preloads,
                        $document->preloads,
                    );

                    // Add FontAwesome CDN or Kit if configured
                    /** @var FontAwesome $fontAwesome */
                    $fontAwesome = $container->make(FontAwesome::class);

                    if ($fontAwesome->useCdn()) {
                        $cdnUrl = $fontAwesome->cdnUrl();
                        if (! empty($cdnUrl)) {
                            $cdnParts = parse_url($cdnUrl);
                            if (! empty($cdnParts['host'])) {
                                $cdnOrigin = e($cdnParts['scheme'].'://'.$cdnParts['host']);
                                $document->head[] = '<link rel="preconnect" href="'.$cdnOrigin.'" crossorigin>';
                                $document->head[] = '<link rel="dns-prefetch" href="'.$cdnOrigin.'">';
                            }
                            // Load asynchronously — FA icons are only rendered after JS boots the
                            // SPA, so there is no FOUC risk from deferring this stylesheet.
                            // The <noscript> fallback covers JS-disabled browsers.
                            $escaped = e($cdnUrl);
                            $document->head[] = '<link rel="preload" href="'.$escaped.'" as="style" crossorigin="anonymous" onload="this.onload=null;this.rel=\'stylesheet\'">'
                                .'<noscript><link rel="stylesheet" href="'.$escaped.'" crossorigin="anonymous"></noscript>';
                        }
                    } elseif ($fontAwesome->useKit()) {
                        $kitUrl = $fontAwesome->kitUrl();
                        if (! empty($kitUrl)) {
                            $kitParts = parse_url($kitUrl);
                            if (! empty($kitParts['host'])) {
                                $kitOrigin = e($kitParts['scheme'].'://'.$kitParts['host']);
                                $document->head[] = '<link rel="preconnect" href="'.$kitOrigin.'" crossorigin>';
                                $document->head[] = '<link rel="dns-prefetch" href="'.$kitOrigin.'">';
                            }
                            // Defer Kit JS — it has no dependencies and nothing depends on it
                            // executing synchronously; defer keeps it out of the critical path
                            // while preserving execution order relative to other deferred scripts.
                            $document->head[] = '<script src="'.e($kitUrl).'" crossorigin="anonymous" defer></script>';
                        }
                    }

                    /** @var SettingsRepositoryInterface $settings */
                    $settings = $container->make(SettingsRepositoryInterface::class);

                    // Add document classes/attributes for design use cases.
                    $document->extraAttributes['data-theme'] = function (ServerRequestInterface $request) use ($settings) {
                        return $settings->get('color_scheme') === 'auto'
                            ? RequestUtil::getActor($request)->getPreference('colorScheme')
                            : $settings->get('color_scheme');
                    };

                    // Inline script that sets data-theme on <html> before first paint so that
                    // the critical CSS block can apply the correct background colour without a
                    // flash in dark mode. Uses the forum-level default; per-user preference is
                    // applied by JS after boot (acceptable tradeoff).
                    $forumColorScheme = $settings->get('color_scheme') ?? 'auto';
                    $document->head[] = '<script>(function(){var s='.json_encode($forumColorScheme).';'
                        .'if(s==="auto")s=window.matchMedia("(prefers-color-scheme:dark)").matches?"dark":"light";'
                        .'document.documentElement.setAttribute("data-theme",s)})()</script>';

                    // Build critical CSS with the theme-accurate dark body background.
                    // Formula mirrors the LESS: hsl(@secondary-hue, min(20%, @secondary-sat), 10%).
                    $secondaryHex = $settings->get('theme_secondary_color') ?? '#536F90';
                    $darkBg = self::computeDarkBodyBg($secondaryHex);
                    $document->criticalCss = 'body{margin:0;background:#fff}'
                        .'[data-theme^=dark] body{background:'.$darkBg.'}'
                        .'#flarum-loading{text-align:center;padding:50px 0;font-size:18px;color:#aaa}';
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
                /** @var FontAwesome $fontAwesome */
                $fontAwesome = $container->make(FontAwesome::class);

                $preloads = [];

                // Only preload local fonts if using local source
                if ($fontAwesome->useLocalFonts()) {
                    $filesystem = $container->make('filesystem')->disk('flarum-assets');

                    $preloads = [
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

                return $preloads;
            }
        );

        $this->container->singleton(
            'flarum.frontend.custom_less_functions',
            function (Container $container) {
                $extensionsEnabled = json_decode($container->make(SettingsRepositoryInterface::class)->get('extensions_enabled'), associative: true);

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

    /**
     * Compute the dark-mode body background colour from the forum's secondary colour hex.
     * Mirrors the LESS formula: hsl(@secondary-hue, min(20%, @secondary-sat), 10%).
     */
    private static function computeDarkBodyBg(string $hex): string
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return '#1a2333'; // safe fallback
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $delta = $max - $min;

        // Hue
        if ($delta == 0) {
            $h = 0;
        } elseif ($max === $r) {
            $h = 60 * fmod(($g - $b) / $delta, 6);
        } elseif ($max === $g) {
            $h = 60 * (($b - $r) / $delta + 2);
        } else {
            $h = 60 * (($r - $g) / $delta + 4);
        }

        if ($h < 0) {
            $h += 360;
        }

        // Saturation (HSL)
        $l = ($max + $min) / 2;
        $s = $delta == 0 ? 0 : $delta / (1 - abs(2 * $l - 1));

        // Apply formula: hsl(hue, min(20%, sat), 10%)
        $sFinal = min(0.20, $s) * 100;
        $lFinal = 10;

        return 'hsl('.round($h).','.round($sFinal).'%,'.$lFinal.'%)';
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
