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

    /**
     * Serialize a setting value to the ForumSerializer attributes.
     *
     * @param string $attributeName: The attribute name to be used in the ForumSerializer attributes array.
     * @param string $key: The key of the setting.
     * @param string|callable|null $callback: Optional callback to modify the value before serialization.
     * @param mixed $default: Optional default serialized value. Will be run through the optional callback.
     * @return $this
     */
    public function serializeToForum(string $attributeName, string $key, $callback = null, $default = null)
    {
        $this->settings[$key] = compact('attributeName', 'callback', 'default');

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        if (! empty($this->settings)) {
            AbstractSerializer::addAttributeMutator(
                ForumSerializer::class,
                function () use ($container) {
                    $settings = $container->make(SettingsRepositoryInterface::class);
                    $attributes = [];

                    foreach ($this->settings as $key => $setting) {
                        $value = $settings->get($key, $setting['default']);

                        if (isset($setting['callback'])) {
                            $callback = ContainerUtil::wrapCallback($setting['callback'], $container);
                            $value = $callback($value);
                        }

                        $attributes[$setting['attributeName']] = $value;
                    }

                    return $attributes;
                }
            );
        }
    }
}
