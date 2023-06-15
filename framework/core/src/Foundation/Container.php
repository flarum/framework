<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Illuminate\Container\Container as LaravelContainer;

class Container extends LaravelContainer
{
    /**
     * Laravel's application is the container itself.
     * So as we upgrade Laravel versions, some of its internals that we rely on
     * make calls to methods that don't exist in our container, but do in Laravel's Application.
     *
     * @TODO: Implement the Application contract and merge the container into it.
     */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->get('flarum')->$name(...$arguments);
    }
}
