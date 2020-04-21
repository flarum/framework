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
use Illuminate\Support\Arr;

class Model implements ExtenderInterface
{
    private $modelClass;
    private $dateAttributes = [];
    private $defaults = [];
    private $relationships = [];

    /**
     * @param string $modelClass The ::class attribute of the model you are modifying.
     *                           This model should extend from \Flarum\Database\AbstractModel.
     */
    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
    }

    /**
     * Add an attribute to be treated as a date.
     *
     * @param string $attribute
     */
    public function dateAttribute(string $attribute)
    {
        Arr::set(AbstractModel::$dateAttributes, $this->modelClass, array_merge(Arr::get(AbstractModel::$dateAttributes, $this->modelClass, []), [$attribute]));

        return $this;
    }

    /**
     * Add a default value for a given attribute, which can be an explicit value, or a closure.
     *
     * @param string $attribute
     * @param mixed $value
     */
    public function default(string $attribute, $value)
    {
        Arr::set(AbstractModel::$defaults, "$this->modelClass.$attribute", $value);

        return $this;
    }

    /**
     * Add a relationship from this model to another model.
     *
     * @param string $name: the name of the relation. This doesn't have to match anything,
     *                      but has to be unique from other relation names for this model.
     * @param callable $callable
     *
     * The callable can be a closure or invokable class, and should accept:
     * - $instance: An instance of this model.
     *
     * The callable should return:
     * - $relationship: A Laravel Relationship object. See relevant methods of models
     *                  like \Flarum\User\User for examples of how relationships should be returned.
     */
    public function relationship(string $name, callable $callable)
    {
        Arr::set(AbstractModel::$customRelations, "$this->modelClass.$name", $callable);

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        // Nothing needed here.
    }
}
