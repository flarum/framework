<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Extension\Extension;
use Flarum\Foundation\ContainerUtil;
use Illuminate\Contracts\Container\Container;

class ApiSerializer implements ExtenderInterface
{
    private $serializerClass;
    private $attribute = [];
    private $attributes = [];
    private $relationships = [];

    /**
     * @param string $serializerClass The ::class attribute of the serializer you are modifying.
     *                                This serializer should extend from \Flarum\Api\Serializer\AbstractSerializer.
     */
    public function __construct(string $serializerClass)
    {
        $this->serializerClass = $serializerClass;
    }

    /**
     * Add a single attribute to this serializer.
     *
     * @param string $name: The name of the attribute.
     * @param callable|string $callback
     *
     * The callback can be a closure or an invokable class, and should accept:
     * - $serializer: An instance of this serializer.
     * - $model: An instance of the model being serialized.
     * - $attributes: An array of existing attributes.
     *
     * The callable should return:
     * - The value of the attribute.
     *
     * @return self
     */
    public function attribute(string $name, $callback): self
    {
        $this->attribute[$name] = $callback;

        return $this;
    }

    /**
     * Add to or modify the attributes array of this serializer.
     *
     * @param callable|string $callback
     *
     * The callback can be a closure or an invokable class, and should accept:
     * - $serializer: An instance of this serializer.
     * - $model: An instance of the model being serialized.
     * - $attributes: An array of existing attributes.
     *
     * The callable should return:
     * - An array of additional attributes to merge with the existing array.
     *   Or a modified $attributes array.
     *
     * @return self
     */
    public function attributes($callback): self
    {
        $this->attributes[] = $callback;

        return $this;
    }

    /**
     * Establish a simple hasOne relationship from this serializer to another serializer.
     * This represents a one-to-one relationship.
     *
     * @param string $name: The name of the relation. Has to be unique from other relation names.
     *                      The relation has to exist in the model handled by this serializer.
     * @param string $serializerClass: The ::class attribute the serializer that handles this relation.
     *                                 This serializer should extend from \Flarum\Api\Serializer\AbstractSerializer.
     * @return self
     */
    public function hasOne(string $name, string $serializerClass): self
    {
        return $this->relationship($name, function (AbstractSerializer $serializer, $model) use ($serializerClass, $name) {
            return $serializer->hasOne($model, $serializerClass, $name);
        });
    }

    /**
     * Establish a simple hasMany relationship from this serializer to another serializer.
     * This represents a one-to-many relationship.
     *
     * @param string $name: The name of the relation. Has to be unique from other relation names.
     *                      The relation has to exist in the model handled by this serializer.
     * @param string $serializerClass: The ::class attribute the serializer that handles this relation.
     *                                 This serializer should extend from \Flarum\Api\Serializer\AbstractSerializer.
     * @return self
     */
    public function hasMany(string $name, string $serializerClass): self
    {
        return $this->relationship($name, function (AbstractSerializer $serializer, $model) use ($serializerClass, $name) {
            return $serializer->hasMany($model, $serializerClass, $name);
        });
    }

    /**
     * Add a relationship from this serializer to another serializer.
     *
     * @param string $name: The name of the relation. Has to be unique from other relation names.
     *                      The relation has to exist in the model handled by this serializer.
     * @param callable|string $callback
     *
     * The callable can be a closure or an invokable class, and should accept:
     * - $serializer: An instance of this serializer.
     * - $model: An instance of the model being serialized.
     *
     * The callable should return:
     * - $relationship: An instance of \Tobscure\JsonApi\Relationship.
     *
     * @return self
     */
    public function relationship(string $name, $callback): self
    {
        $this->relationships[$this->serializerClass][$name] = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        if (! empty($this->attribute)) {
            $this->attributes[] = function ($serializer, $model, $attributes) use ($container) {
                foreach ($this->attribute as $attributeName => $callback) {
                    $callback = ContainerUtil::wrapCallback($callback, $container);

                    $attributes[$attributeName] = $callback($serializer, $model, $attributes);
                }

                return $attributes;
            };
        }

        foreach ($this->attributes as $callback) {
            $callback = ContainerUtil::wrapCallback($callback, $container);

            AbstractSerializer::addAttributeMutator($this->serializerClass, $callback);
        }

        foreach ($this->relationships as $serializerClass => $relationships) {
            foreach ($relationships as $relation => $callback) {
                $callback = ContainerUtil::wrapCallback($callback, $container);

                AbstractSerializer::setRelationship($serializerClass, $relation, $callback);
            }
        }
    }
}
