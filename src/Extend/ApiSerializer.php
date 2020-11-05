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
use Illuminate\Contracts\Container\Container;

class ApiSerializer implements ExtenderInterface
{
    private $serializerClass;
    private $attributeHandlers = [];

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
    }
}
