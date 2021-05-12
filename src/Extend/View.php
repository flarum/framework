<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Flarum\Foundation\Paths;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\View\Factory;

class View implements ExtenderInterface, LifecycleInterface
{
    private $namespaces = [];

    /**
     * Register a new namespace of Laravel views.
     *
     * Views are php files that use the Laravel Blade syntax for creation of server-side generated html.
     * Flarum core uses them for error pages, the installer, HTML emails, and the skeletons for the forum and admin sites.
     * To create and use views in your extension, you will need to put them in a folder, and register that folder as a namespace.
     *
     * Views can then be used in your extension by injecting an instance of `Illuminate\Contracts\View\Factory`,
     * and calling its `make` method. The `make` method takes the view parameter in the format NAMESPACE::VIEW_NAME.
     * You can also pass variables into a view: for more information, see https://laravel.com/api/8.x/Illuminate/View/Factory.html#method_make
     *
     * @param  string  $namespace: The name of the namespace.
     * @param  string|string[]  $hints: This is a path (or an array of paths) to the folder(s)
     *                               where view files are stored, relative to the extend.php file.
     * @return self
     */
    public function namespace(string $namespace, $hints): self
    {
        $this->namespaces[$namespace] = $hints;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->resolving(Factory::class, function (Factory $view) {
            foreach ($this->namespaces as $namespace => $hints) {
                $view->addNamespace($namespace, $hints);
            }
        });
    }

    /**
     * @param Container $container
     * @param Extension $extension
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function onEnable(Container $container, Extension $extension)
    {
        $storagePath = $container->make(Paths::class)->storage;
        array_map('unlink', glob($storagePath.'/views/*'));
    }

    /**
     * @param Container $container
     * @param Extension $extension
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function onDisable(Container $container, Extension $extension)
    {
        $storagePath = $container->make(Paths::class)->storage;
        array_map('unlink', glob($storagePath.'/views/*'));
    }
}
