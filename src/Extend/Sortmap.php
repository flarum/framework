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

class Sortmap implements ExtenderInterface
{
    private $sortFields = [];

    public function addSort(string $key, string $value): self
    {
        $this->sortFields[$key] = $value;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('flarum.forum.sortmap', function (array $sortFields) {
            return array_merge($sortFields, $this->sortFields);
        });
    }
}
