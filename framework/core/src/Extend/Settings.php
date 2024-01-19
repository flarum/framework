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
use Illuminate\Support\Collection;

class Settings implements ExtenderInterface
{
    private array $settings = [];
    private array $defaults = [];
    private array $lessConfigs = [];

    /**
     * Serialize a setting value to the ForumSerializer attributes.
     *
     * @param string $attributeName: The attribute name to be used in the ForumSerializer attributes array.
     * @param string $key: The key of the setting.
     * @param (callable(mixed $value): mixed)|class-string|null $callback: Optional callback to modify the value before serialization.
     *
     * The callback can be a closure or an invokable class, and should accept:
     * - mixed $value: The value of the setting.
     *
     * The callable should return:
     * - mixed $value: The modified value.
     *
     * @return self
     */
    public function serializeToForum(string $attributeName, string $key, callable|string $callback = null): self
    {
        $this->settings[$key] = compact('attributeName', 'callback');

        return $this;
    }

    /**
     * Set a default value for a setting.
     * Replaces inserting the default value with a migration.
     *
     * @param string $key: The setting key, must be unique. Namespace it with the extension ID (example: 'my-extension-id.setting_key').
     * @param mixed $value: The setting value.
     * @return self
     */
    public function default(string $key, mixed $value): self
    {
        $this->defaults[$key] = $value;

        return $this;
    }

    /**
     * Register a setting as a LESS configuration variable.
     *
     * @param string $configName: The name of the configuration variable, in hyphen case.
     * @param string $key: The key of the setting.
     * @param (callable(mixed $value): mixed)|class-string|null $callback: Optional callback to modify the value.
     *
     * The callback can be a closure or an invokable class, and should accept:
     * - mixed $value: The value of the setting.
     *
     * The callable should return:
     * - mixed $value: The modified value.
     *
     * @return self
     */
    public function registerLessConfigVar(string $configName, string $key, callable|string $callback = null): self
    {
        $this->lessConfigs[$configName] = compact('key', 'callback');

        return $this;
    }

    public function extend(Container $container, Extension $extension = null): void
    {
        if (! empty($this->defaults)) {
            $container->extend('flarum.settings.default', function (Collection $defaults) {
                foreach ($this->defaults as $key => $value) {
                    if ($defaults->has($key)) {
                        throw new \RuntimeException("Cannot modify immutable default setting $key.");
                    }

                    $defaults->put($key, $value);
                }

                return $defaults;
            });
        }

        if (! empty($this->settings)) {
            AbstractSerializer::addAttributeMutator(
                ForumSerializer::class,
                function () use ($container) {
                    $settings = $container->make(SettingsRepositoryInterface::class);
                    $attributes = [];

                    foreach ($this->settings as $key => $setting) {
                        $value = $settings->get($key);

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

        if (! empty($this->lessConfigs)) {
            $container->extend('flarum.less.config', function (array $existingConfig, Container $container) {
                $config = $this->lessConfigs;

                foreach ($config as $var => $data) {
                    if (isset($data['callback'])) {
                        $config[$var]['callback'] = ContainerUtil::wrapCallback($data['callback'], $container);
                    }
                }

                return array_merge($existingConfig, $config);
            });
        }
    }
}
