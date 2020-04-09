<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Database\AbstractModel;
use Flarum\Extension\Extension;
use Illuminate\Contracts\Container\Container;

class Model implements ExtenderInterface
{
    private $modelClass;
    private $dateCallbacks = [];
    private $defaultAttributeCallbacks = [];
    private $relationships = [];

    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
    }

    public function configureDates(callable $callback)
    {
        $this->dateCallbacks[] = $callback;

        return $this;
    }

    public function configureDefaultAttributes(callable $callable)
    {
        $this->defaultAttributeCallbacks[] = $callable;

        return $this;
    }

    public function relationship(string $name, callable $callable)
    {
        $this->relationships[$name] = $callable;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('flarum.model.customRelations', function ($existingRelations) {
            return array_merge_recursive($existingRelations, [$this->modelClass => $this->relationships]);
        });

        $container->extend('flarum.model.dateCallbacks', function ($existingCallbacks) {
            return array_merge_recursive($existingCallbacks, [$this->modelClass => $this->dateCallbacks]);
        });

        $container->extend('flarum.model.defaultAttributeCallbacks', function ($existingCallbacks) {
            return array_merge_recursive($existingCallbacks, [$this->modelClass => $this->defaultAttributeCallbacks]);
        });
    }
}
