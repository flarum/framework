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

class Csrf implements ExtenderInterface
{
    protected $csrfExemptRoutes = [];

    /**
     * Exempt a named route from CSRF checks.
     *
     * @param string $routeName
     */
    public function exemptRoute(string $routeName)
    {
        $this->csrfExemptRoutes[] = $routeName;

        return $this;
    }

    /**
     * Exempt a path from csrf checks. Wildcards are supported.
     *
     * @deprecated beta 15, remove beta 16. Exempt routes should be used instead.
     */
    public function exemptPath(string $path)
    {
        $this->csrfExemptRoutes[] = $path;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('flarum.http.csrfExemptPaths', function ($existingExemptPaths) {
            return array_merge($existingExemptPaths, $this->csrfExemptRoutes);
        });
    }
}
