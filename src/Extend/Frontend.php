<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Flarum\Frontend\Assets;
use Flarum\Frontend\Compiler\Source\SourceCollector;
use Flarum\Frontend\Frontend as ActualFrontend;
use Flarum\Frontend\RecompileFrontendAssets;
use Flarum\Http\RouteHandlerFactory;
use Illuminate\Contracts\Container\Container;

class Frontend implements ExtenderInterface
{
    protected $frontend;

    protected $css = [];
    protected $js;
    protected $routes = [];
    protected $content = [];

    public function __construct(string $frontend)
    {
        $this->frontend = $frontend;
    }

    public function css(string $path)
    {
        $this->css[] = $path;

        return $this;
    }

    public function js(string $path)
    {
        $this->js = $path;

        return $this;
    }

    public function route(string $path, string $name, $content = null)
    {
        $this->routes[] = compact('path', 'name', 'content');

        return $this;
    }

    /**
     * @param callable|string $callback
     * @return $this
     */
    public function content($callback)
    {
        $this->content[] = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $this->registerAssets($container, $this->getModuleName($extension));
        $this->registerRoutes($container);
        $this->registerContent($container);
    }

    private function registerAssets(Container $container, string $moduleName)
    {
        if (empty($this->css) && empty($this->js)) {
            return;
        }

        $abstract = 'flarum.assets.'.$this->frontend;

        $container->resolving($abstract, function (Assets $assets) use ($moduleName) {
            if ($this->js) {
                $assets->js(function (SourceCollector $sources) use ($moduleName) {
                    $sources->addString(function () {
                        return 'var module={}';
                    });
                    $sources->addFile($this->js);
                    $sources->addString(function () use ($moduleName) {
                        return "flarum.extensions['$moduleName']=module.exports";
                    });
                });
            }

            if ($this->css) {
                $assets->css(function (SourceCollector $sources) {
                    foreach ($this->css as $path) {
                        $sources->addFile($path);
                    }
                });
            }
        });

        if (! $container->bound($abstract)) {
            $container->bind($abstract, function (Container $container) {
                return $container->make('flarum.assets.factory')($this->frontend);
            });

            $container->make('events')->subscribe(
                new RecompileFrontendAssets(
                    $container->make($abstract),
                    $container->make('flarum.locales')
                )
            );
        }
    }

    private function registerRoutes(Container $container)
    {
        if (empty($this->routes)) {
            return;
        }

        $routes = $container->make("flarum.$this->frontend.routes");
        $factory = $container->make(RouteHandlerFactory::class);

        foreach ($this->routes as $route) {
            $routes->get(
                $route['path'], $route['name'],
                $factory->toFrontend($this->frontend, $route['content'])
            );
        }
    }

    private function registerContent(Container $container)
    {
        if (empty($this->content)) {
            return;
        }

        $container->resolving(
            "flarum.frontend.$this->frontend",
            function (ActualFrontend $frontend, Container $container) {
                foreach ($this->content as $content) {
                    if (is_string($content)) {
                        $content = $container->make($content);
                    }

                    $frontend->content($content);
                }
            }
        );
    }

    private function getModuleName(?Extension $extension): string
    {
        return $extension ? $extension->getId() : 'site-custom';
    }
}
