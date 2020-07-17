<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\View\Factory;

class ViewNamespace implements ExtenderInterface
{
    private $adds = [];

    /**
     * Register a new namespace of Laravel views.
     *
     * Views are php files that use the Laravel Blade syntax for creation of server-side generated html.
     * Flarum core uses them for error pages, the installer, HTML emails, and the skeletons for the forum and admin sites.
     * To create and use views in your extension, you will need to put them in a folder, and register that folder as a namespace.
     *
     * Views can then be used in your extension by injecting an instance of `Illuminate\Contracts\View\Factory`,
     * and calling its `make` method. The `make` method takes the view parameter in the format NAMESPACE::VIEW_NAME.
     * You can also pass variables into a view: for more information, see https://laravel.com/api/6.x/Illuminate/View/Factory.html#method_make
     *
     * @param  string  $namespace: The name of the namespace.
     * @param  string|array  $hints: This is a path (or an array of paths) to the folder(s)
     *                               where view files are stored, relative to the extend.php file.
     * @return $this
     */
    public function add($namespace, $hints)
    {
        $this->adds[$namespace] = $hints;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $factory = $container->make(Factory::class);

        foreach ($this->adds as $namespace => $hints) {
            $factory->addNamespace($namespace, $hints);
        }
    }
}
