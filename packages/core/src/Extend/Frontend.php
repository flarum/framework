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
use Flarum\Frontend\Asset\ExtensionAssets;
use Flarum\Frontend\CompilerFactory;
use Flarum\Http\RouteHandlerFactory;
use Illuminate\Contracts\Container\Container;

class Frontend implements ExtenderInterface
{
    protected $frontend;

    protected $css = [];
    protected $js;
    protected $routes = [];

    public function __construct($frontend)
    {
        $this->frontend = $frontend;
    }

    public function css($path)
    {
        $this->css[] = $path;

        return $this;
    }

    public function js($path)
    {
        $this->js = $path;

        return $this;
    }

    public function route($path, $name, $content = null)
    {
        $this->routes[] = compact('path', 'name', 'content');

        return $this;
    }

    public function __invoke(Container $container, Extension $extension = null)
    {
        $this->registerAssets($container, $this->getModuleName($extension));
        $this->registerRoutes($container);
    }

    private function registerAssets(Container $container, string $moduleName)
    {
        if (empty($this->css) && empty($this->js)) {
            return;
        }

        $container->resolving(
            "flarum.$this->frontend.assets",
            function (CompilerFactory $assets) use ($moduleName) {
                $assets->add(function () use ($moduleName) {
                    return new ExtensionAssets(
                        $moduleName, $this->css, $this->js
                    );
                });
            }
        );
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

    private function getModuleName(?Extension $extension): string
    {
        return $extension ? $extension->getId() : 'site-custom';
    }
}
