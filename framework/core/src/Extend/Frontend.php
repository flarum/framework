<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Event\Disabled;
use Flarum\Extension\Event\Enabled;
use Flarum\Extension\Extension;
use Flarum\Foundation\ContainerUtil;
use Flarum\Foundation\Event\ClearingCache;
use Flarum\Frontend\Assets;
use Flarum\Frontend\Compiler\Source\SourceCollector;
use Flarum\Frontend\Document;
use Flarum\Frontend\Driver\TitleDriverInterface;
use Flarum\Frontend\Frontend as ActualFrontend;
use Flarum\Frontend\RecompileFrontendAssets;
use Flarum\Http\RouteCollection;
use Flarum\Http\RouteHandlerFactory;
use Flarum\Locale\LocaleManager;
use Illuminate\Contracts\Container\Container;
use Psr\Http\Message\ServerRequestInterface;

class Frontend implements ExtenderInterface
{
    private array $css = [];
    private ?string $js = null;
    private array $routes = [];
    private array $removedRoutes = [];
    private array $content = [];
    private array $preloadArrs = [];
    private ?string $titleDriver = null;
    private array $jsDirectory = [];
    private array $extraDocumentAttributes = [];

    /**
     * @param string $frontend: The name of the frontend.
     */
    public function __construct(
        private readonly string $frontend
    ) {
    }

    /**
     * Add a CSS file to load in the frontend.
     *
     * @param string $path: The path to the CSS file.
     * @return self
     */
    public function css(string $path): self
    {
        $this->css[] = $path;

        return $this;
    }

    /**
     * Add a JavaScript file to load in the frontend.
     *
     * @param string $path: The path to the JavaScript file.
     * @return self
     */
    public function js(string $path): self
    {
        $this->js = $path;

        return $this;
    }

    /**
     * Add a directory of JavaScript files to include in the JS assets public directory.
     * Primarily used to copy JS chunks.
     *
     * @param string $path The path to the specific frontend chunks directory.
     * @return $this
     */
    public function jsDirectory(string $path): self
    {
        $this->jsDirectory[] = $path;

        return $this;
    }

    /**
     * Add a route to the frontend.
     *
     * @param string $path: The path of the route.
     * @param string $name: The name of the route, must be unique.
     * @param (callable(Document $document, ServerRequestInterface $request): void)|class-string|null $content
     *
     * The content can be a closure or an invokable class, and should accept:
     * - \Flarum\Frontend\Document $document
     * - \Psr\Http\Message\ServerRequestInterface $request
     *
     * The callable should return void.
     *
     * @return self
     */
    public function route(string $path, string $name, callable|string|null $content = null): self
    {
        $this->routes[] = compact('path', 'name', 'content');

        return $this;
    }

    /**
     * Remove a route from the frontend.
     * This is necessary before overriding a route.
     *
     * @param string $name: The name of the route.
     * @return self
     */
    public function removeRoute(string $name): self
    {
        $this->removedRoutes[] = $name;

        return $this;
    }

    /**
     * Modify the content of the frontend.
     *
     * @param (callable(Document $document, ServerRequestInterface $request): void)|class-string|null $callback
     *
     * The content can be a closure or an invokable class, and should accept:
     * - \Flarum\Frontend\Document $document
     * - \Psr\Http\Message\ServerRequestInterface $request
     *
     * The callable should return void.
     * @param int $priority: The priority of the content. Higher priorities are executed first.
     *
     * @return self
     */
    public function content(callable|string|null $callback, int $priority = 0): self
    {
        $this->content[] = compact('callback', 'priority');

        return $this;
    }

    /**
     * Adds multiple asset preloads.
     *
     * The parameter should be an array of preload arrays, or a callable that returns this.
     *
     * A preload array must contain keys that pertain to the `<link rel="preload">` tag.
     *
     * For example, the following will add preload tags for a script and font file:
     * ```
     * $frontend->preloads([
     *   [
     *     'href' => '/assets/my-script.js',
     *     'as' => 'script',
     *   ],
     *   [
     *     'href' => '/assets/fonts/my-font.woff2',
     *     'as' => 'font',
     *     'type' => 'font/woff2',
     *     'crossorigin' => ''
     *   ]
     * ]);
     * ```
     *
     * @param callable|array $preloads
     * @return self
     */
    public function preloads(callable|array $preloads): self
    {
        $this->preloadArrs[] = $preloads;

        return $this;
    }

    /**
     * Register a new title driver to change the title of frontend documents.
     *
     * @param class-string<TitleDriverInterface> $driverClass
     */
    public function title(string $driverClass): self
    {
        $this->titleDriver = $driverClass;

        return $this;
    }

    /**
     * Adds document root attributes.
     *
     * @example ['data-test' => 'value']
     * @example ['data-test' => function (ServerRequestInterface $request) { return 'value'; }]
     *
     * @param array<string, string|callable> $attributes
     */
    public function extraDocumentAttributes(array $attributes): self
    {
        $this->extraDocumentAttributes[] = $attributes;

        return $this;
    }

    /**
     * Adds document root classes.
     *
     * Can either be a string or an array of strings.
     *
     * An array can be of a format acceptable by the @class blade directive.
     *
     * @example ['class1', 'class2' => true, 'class3' => false]
     */
    public function extraDocumentClasses(string|array|callable $classes): self
    {
        return $this->extraDocumentAttributes(['class' => $classes]);
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
        $this->registerAssets($container, $this->getModuleName($extension));
        $this->registerRoutes($container);
        $this->registerContent($container);
        $this->registerPreloads($container);
        $this->registerTitleDriver($container);
        $this->registerDocumentAttributes($container);
    }

    private function registerAssets(Container $container, string $moduleName): void
    {
        if (empty($this->css) && empty($this->js) && empty($this->jsDirectory)) {
            return;
        }

        $abstract = 'flarum.assets.'.$this->frontend;

        $container->resolving($abstract, function (Assets $assets) use ($moduleName) {
            if ($this->js) {
                $assets->js(function (SourceCollector $sources) use ($moduleName) {
                    $sources->addString(function () {
                        return 'var module={};';
                    });
                    $sources->addFile($this->js);
                    $sources->addString(function () use ($moduleName) {
                        return "flarum.extensions['$moduleName']=module.exports;";
                    });
                });
            }

            if ($this->css) {
                $assets->css(function (SourceCollector $sources) use ($moduleName) {
                    foreach ($this->css as $path) {
                        $sources->addFile($path, $moduleName);
                    }
                });
            }

            if (! empty($this->jsDirectory)) {
                $assets->jsDirectory(function (SourceCollector $sources) use ($moduleName) {
                    foreach ($this->jsDirectory as $path) {
                        $sources->addDirectory($path, $moduleName);
                    }
                });
            }
        });

        if (! $container->bound($abstract)) {
            $container->bind($abstract, function (Container $container) {
                return $container->make('flarum.assets.factory')($this->frontend);
            });

            /** @var \Illuminate\Contracts\Events\Dispatcher $events */
            $events = $container->make('events');

            $events->listen(
                [Enabled::class, Disabled::class, ClearingCache::class],
                function () use ($container, $abstract) {
                    $recompile = new RecompileFrontendAssets(
                        $container->make($abstract),
                        $container->make(LocaleManager::class)
                    );
                    $recompile->flush();
                }
            );
        }
    }

    private function registerRoutes(Container $container): void
    {
        if (empty($this->routes) && empty($this->removedRoutes)) {
            return;
        }

        $container->resolving(
            "flarum.{$this->frontend}.routes",
            function (RouteCollection $collection, Container $container) {
                /** @var RouteHandlerFactory $factory */
                $factory = $container->make(RouteHandlerFactory::class);

                foreach ($this->removedRoutes as $routeName) {
                    $collection->removeRoute($routeName);
                }

                foreach ($this->routes as $route) {
                    $collection->get(
                        $route['path'],
                        $route['name'],
                        $factory->toFrontend($this->frontend, $route['content'])
                    );
                }
            }
        );
    }

    private function registerContent(Container $container): void
    {
        if (empty($this->content)) {
            return;
        }

        $container->resolving(
            "flarum.frontend.$this->frontend",
            function (ActualFrontend $frontend, Container $container) {
                foreach ($this->content as $content) {
                    $frontend->content(ContainerUtil::wrapCallback($content['callback'], $container), $content['priority']);
                }
            }
        );
    }

    private function registerPreloads(Container $container): void
    {
        if (empty($this->preloadArrs)) {
            return;
        }

        $container->resolving(
            "flarum.frontend.$this->frontend",
            function (ActualFrontend $frontend, Container $container) {
                $frontend->content(function (Document $document) use ($container) {
                    foreach ($this->preloadArrs as $preloadArr) {
                        $preloads = is_callable($preloadArr) ? ContainerUtil::wrapCallback($preloadArr, $container)($document) : $preloadArr;
                        $document->preloads = array_merge($document->preloads, $preloads);
                    }
                }, 110);
            }
        );
    }

    private function getModuleName(?Extension $extension): string
    {
        return $extension ? $extension->getId() : 'site-custom';
    }

    private function registerTitleDriver(Container $container): void
    {
        if ($this->titleDriver) {
            $container->extend('flarum.frontend.title_driver', function ($driver, Container $container) {
                return $container->make($this->titleDriver);
            });
        }
    }

    private function registerDocumentAttributes(Container $container): void
    {
        if (empty($this->extraDocumentAttributes)) {
            return;
        }

        $container->resolving(
            "flarum.frontend.$this->frontend",
            function (ActualFrontend $frontend, Container $container) {
                $frontend->content(function (Document $document) use ($container) {
                    foreach ($this->extraDocumentAttributes as $classes) {
                        $classes = is_callable($classes) ? ContainerUtil::wrapCallback($classes, $container) : $classes;
                        $document->extraAttributes[] = $classes;
                    }
                }, 111);
            }
        );
    }
}
