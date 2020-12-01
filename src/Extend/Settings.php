<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Extension\Extension;
use Flarum\Foundation\ContainerUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;

class Settings implements ExtenderInterface
{
    private $settings = [];
    private $defaults = [];
    private $modifiers = [];

    /**
     * Serialize a setting value to the ForumSerializer attributes.
     *
     * @param string $attributeName: The attribute name to be used in the ForumSerializer attributes array.
     * @param string $key: The key of the setting.
     * @return $this
     */
    public function serializeToForum(string $attributeName, string $key)
    {
        $this->settings[$key] = $attributeName;

        return $this;
    }

    /**
     * Set a default value for a setting selected for serialization.
     *
     * @param string $key
     * @param $value
     * @return $this
     */
    public function default(string $key, $value)
    {
        $this->defaults[$key] = $value;

        return $this;
    }

    /**
     * Modify a setting's value.
     *
     * @param string $key
     * @param $callback
     * @return $this
     */
    public function modifier(string $key, $callback)
    {
        $this->modifiers[$key] = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        if (! empty($this->settings)) {
            AbstractSerializer::addMutator(
                ForumSerializer::class,
                function () use ($container) {
                    $settings = $container->make(SettingsRepositoryInterface::class);
                    $attributes = [];

                    foreach ($this->settings as $key => $attributeName) {
                        $value = $settings->get($key, $this->defaults[$key] ?? null);

                        if (isset($this->modifiers[$key])) {
                            $callback = ContainerUtil::wrapCallback($this->modifiers[$key], $container);
                            $value = $callback($value);
                        }

                        $attributes[$attributeName] = $value;
                    }

                    return $attributes;
                }
            );
        }
    }
}
