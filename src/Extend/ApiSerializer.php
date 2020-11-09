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
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;

class ApiSerializer implements ExtenderInterface
{
    private $serializerClass;
    private $attributeHandlers = [];
    private $settings = [];
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
     * Add to or modify the attributes array of this serializer.
     *
     * @param callable|string $callback
     *
     * The callback can be a closure or an invokable class, and should accept:
     * - $attributes: An array of existing attributes.
     * - $model: An instance of the model being serialized.
     * - $serializer: An instance of this serializer.
     *
     * The callable should return:
     * - An array of additional attributes to merge with the existing array.
     *   Or a modified $attributes array.
     *
     * @return self
     */
    public function attributes($callback)
    {
        $this->attributeHandlers[] = $callback;

        return $this;
    }

    /**
     * Add a setting value to the attributes of this serializer.
     *
     * @param string $key: The key of the setting. It should be prefixed with the extension ID. (ex: extension-id.setting_key).
     * @param mixed $default: The default value of the setting.
     * @return self
     */
    public function setting(string $key, $default = null)
    {
        $this->settings[$key] = $default;

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
    public function hasOneRelationship(string $name, string $serializerClass)
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
    public function hasManyRelationship(string $name, string $serializerClass)
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
    public function relationship(string $name, $callback)
    {
        $this->relationships[$this->serializerClass][$name] = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        foreach ($this->attributeHandlers as $attributeHandler) {
            $attributeHandler = ContainerUtil::wrapCallback($attributeHandler, $container);

            AbstractSerializer::addAttributeHandler($this->serializerClass, $attributeHandler);
        }

        if (! empty($this->settings)) {
            AbstractSerializer::addAttributeHandler($this->serializerClass, function (array $attributes) use ($container) {
                $settings = $container->make(SettingsRepositoryInterface::class);

                foreach ($this->settings as $key => $default) {
                    $attributes[$key] = $settings->get($key, $default);
                }

                return $attributes;
            });
        }

        foreach ($this->relationships as $serializerClass => $relationships) {
            foreach ($relationships as $relation => $callback) {
                $callback = ContainerUtil::wrapCallback($callback, $container);

                AbstractSerializer::setRelationship($serializerClass, $relation, $callback);
            }
        }
    }
}
