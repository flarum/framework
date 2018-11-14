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

use Exception;
use Flarum\Extension\Extension;
use Flarum\Frontend\Asset\ExtensionAssets;
use Flarum\Frontend\CompilerFactory;
use Flarum\Frontend\HtmlDocumentFactory;
use Flarum\Http\RouteHandlerFactory;
use Illuminate\Contracts\Container\Container;

class Frontend implements ExtenderInterface
{
    protected $frontend;
    protected $inheritFrom;

    protected $css = [];
    protected $js;
    protected $routes = [];
    protected $content = [];

    public function __construct($frontend)
    {
        $this->frontend = $frontend;
    }

    public function inherits($from)
    {
        $this->inheritFrom = $from;

        return $this;
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
        $this->ensureCanRegisterRoutes();

        $this->routes[] = compact('path', 'name', 'content');

        return $this;
    }

    private function ensureCanRegisterRoutes()
    {
        if (in_array($this->frontend, ['forum', 'admin'])) {
            return;
        }

        throw new Exception(
            'The Frontend extender can only handle routes for the forum and '.
            'admin frontends. Other routes (e.g. for the API) need to be '.
            'registered through the Routes extender.'
        );
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
        $this->registerFrontend($container);
        $this->registerAssets($container, $this->getModuleName($extension));
        $this->registerRoutes($container);
        $this->registerContent($container);
    }

    private function registerFrontend(Container $container)
    {
        if ($container->bound("flarum.$this->frontend.frontend")) {
            return;
        }

        $container->bind(
            "flarum.$this->frontend.frontend",
            function ($c) {
                $view = $c->make('flarum.frontend.view.defaults')($this->frontend);

                $view->setAssets($c->make("flarum.$this->frontend.assets"));

                return $view;
            }
        );

        $container->bind("flarum.$this->frontend.assets", function ($c) {
            if ($this->inheritFrom) {
                // FIXME: will contain Assets\CoreAssets instance with wrong name
                return $c->make("flarum.$this->inheritFrom.assets")
                    ->inherit($this->frontend);
            } else {
                return $c->make('flarum.frontend.assets.defaults')($this->frontend);
            }
        });
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

    private function registerContent(Container $container)
    {
        if (empty($this->content)) {
            return;
        }

        $container->resolving(
            "flarum.$this->frontend.frontend",
            function (HtmlDocumentFactory $view, Container $container) {
                foreach ($this->content as $content) {
                    if (is_string($content)) {
                        $content = $container->make($content);
                    }

                    $view->add($content);
                }
            }
        );
    }

    private function getModuleName(?Extension $extension): string
    {
        return $extension ? $extension->getId() : 'site-custom';
    }
}
