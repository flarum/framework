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
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;

class ApiSerializer implements ExtenderInterface
{
    private $serializerClass;
    private $attributeHandlers = [];
    private $settings = [];
    private $relationships = [];

    public function __construct(string $serializerClass)
    {
        $this->serializerClass = $serializerClass;
    }

    /**
     * @param callable|string $handler
     * @return self
     */
    public function attributes($handler)
    {
        $this->attributeHandlers[] = $handler;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return self
     */
    public function setting(string $key, $default = null)
    {
        $this->settings[$key] = $default;

        return $this;
    }

    /**
     * @param string $name
     * @param string $serializerClass
     * @return self
     */
    public function hasOneRelationship(string $name, string $serializerClass)
    {
        return $this->relationship($name, function (AbstractSerializer $serializer, $model) use ($serializerClass, $name) {
            return $serializer->hasOne($model, $serializerClass, $name);
        });
    }

    /**
     * @param string $name
     * @param string $serializerClass
     * @return self
     */
    public function hasManyRelationship(string $name, string $serializerClass)
    {
        return $this->relationship($name, function (AbstractSerializer $serializer, $model) use ($serializerClass, $name) {
            return $serializer->hasMany($model, $serializerClass, $name);
        });
    }

    /**
     * @param string $name
     * @param callable|string $callback
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
            if (is_string($attributeHandler)) {
                $attributeHandler = function () use ($container, $attributeHandler) {
                    $attributeHandler = $container->make($attributeHandler);

                    return call_user_func_array($attributeHandler, func_get_args());
                };
            }

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
                if (is_string($callback)) {
                    $callback = function () use ($container, $callback) {
                        $callback = $container->make($callback);

                        return call_user_func_array($callback, func_get_args());
                    };
                }

                AbstractSerializer::setRelationship($serializerClass, $relation, $callback);
            }
        }
    }
}
