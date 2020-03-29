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
    private $relationships = [];

    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
    }

    public function relationship(string $name, callable $callable)
    {
        $this->relationships[$name] = $callable;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $model = $container->make($this->modelClass);

        foreach ($this->relationships as $name => $relationship) {
            if (is_string($relationship)) {
                $relationship = $container->make($relationship);
            }

            AbstractModel::addCustomRelation($this->modelClass, $name, $relationship);
        }
    }
}
