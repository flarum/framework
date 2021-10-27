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
use Flarum\Settings\DefaultSettingsRepository;
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
     *
     * The callback can be a closure or an invokable class, and should accept:
     * - mixed $value: The value of the setting.
     *
     * The callable should return:
     * - mixed $value: The modified value.
     *
     * @todo remove $default in 2.0
     * @param mixed $default: Deprecated optional default serialized value. Will be run through the optional callback.
     * @return self
     */
    public function serializeToForum(string $attributeName, string $key, $callback = null, $default = null): self
    {
        $this->settings[$key] = compact('attributeName', 'callback', 'default');

        return $this;
    }

    /**
     * Set a default value for a setting.
     * Recommended instead of inserting the default value with a migration.
     */
    public function default(string $key, $value): self
    {
        $this->defaults[$key] = $value;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        if (! empty($this->defaults)) {
            $container->extend(DefaultSettingsRepository::class, function (DefaultSettingsRepository $defaultSettings) {
                foreach ($this->defaults as $key => $default) {
                    $defaultSettings->set($key, $default);
                }
            });
        }

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
