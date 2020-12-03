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
use Flarum\Settings\DefaultSettingsManager;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;

class Settings implements ExtenderInterface
{
    private $settings = [];
    private $defaults = [];

    /**
     * Serialize a setting value to the ForumSerializer attributes.
     *
     * @param string $attributeName: The attribute name to be used in the ForumSerializer attributes array.
     * @param string $key: The key of the setting.
     * @param string|callable|null $callback: Optional callback to modify the value before serialization.
     * @return $this
     */
    public function serializeToForum(string $attributeName, string $key, $callback = null)
    {
        $this->settings[$key] = compact('attributeName', 'callback');

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

    public function extend(Container $container, Extension $extension = null)
    {
        if (! empty($this->defaults)) {
            $container->extend(DefaultSettingsManager::class, function (DefaultSettingsManager $manager) {
                foreach ($this->defaults as $key => $default) {
                    $manager->set($key, $default);
                }
            });
        }

        if (! empty($this->settings)) {
            AbstractSerializer::addMutator(
                ForumSerializer::class,
                function () use ($container) {
                    $settings = $container->make(SettingsRepositoryInterface::class);
                    $attributes = [];

                    foreach ($this->settings as $key => $setting) {
                        $value = $settings->get($key, null);

                        if (isset($setting['callback'])) {
                            $setting['callback'] = ContainerUtil::wrapCallback($setting['callback'], $container);
                            $value = $setting['callback']($value);
                        }

                        $attributes[$setting['attributeName']] = $value;
                    }

                    return $attributes;
                }
            );
        }
    }
}
