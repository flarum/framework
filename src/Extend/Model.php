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

class Model implements ExtenderInterface
{
    private $modelClass;
    private $dateCallbacks = [];
    private $defaultAttributeCallbacks = [];
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
     * Modify which attributes of this model are treated as dates.
     *
     * @param callable $callable
     *
     * The callable can be a closure or invokable class, and should accept:
     * - \Flarum\User\User $user: the user in question.
     * - array $dateAttributes: an array of attributes which are processed as dates.
     *
     * The callable should return:
     * - array $dateAttributes: an array of attributes which are processed as dates.
     */
    public function configureDates(callable $callable)
    {
        $this->dateCallbacks[] = $callable;

        return $this;
    }

    /**
     * Modify default attribute values for this model.
     *
     * @param callable $callable
     *
     * The callable can be a closure or invokable class, and should accept:
     * - \Flarum\User\User $user: the user in question.
     * - array[string] $dateAttributes: an associative array of attributes => default values.
     *
     * The callable should return:
     * - array[string] $dateAttributes: an associative array of attributes => default values.
     */
    public function configureDefaultAttributes(callable $callable)
    {
        $this->defaultAttributeCallbacks[] = $callable;

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
