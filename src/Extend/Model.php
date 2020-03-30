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
        $model = $container->make($this->modelClass);

        foreach ($this->dateCallbacks as $callback) {
            if (is_string($callback)) {
                $callback = $container->make($callback);
            }

            AbstractModel::addDateCallback($this->modelClass, $callback);
        }

        foreach ($this->defaultAttributeCallbacks as $callback) {
            if (is_string($callback)) {
                $callback = $container->make($callback);
            }

            AbstractModel::addDefaultAttributeCallback($this->modelClass, $callback);
        }

        foreach ($this->relationships as $name => $relationship) {
            if (is_string($relationship)) {
                $relationship = $container->make($relationship);
            }

            AbstractModel::addCustomRelation($this->modelClass, $name, $relationship);
        }
    }
}
