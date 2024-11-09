<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Flarum\Extension\ExtensionManager;
use Illuminate\Contracts\Container\Container;

/**
 * The Conditional extender allows developers to conditionally apply other extenders
 * based on either boolean values or results from callable functions.
 *
 * This is useful for applying extenders only if certain conditions are met,
 * such as the presence of an enabled extension or a specific configuration setting.
 */
class Conditional implements ExtenderInterface
{
    /**
     * An array of conditions and their associated extenders.
     *
     * Each entry should have:
     * - 'condition': a boolean or callable that should return a boolean.
     * - 'extenders': an array of extenders, a callable returning an array of extenders, or an invokable class string.
     *
     * @var array<array{condition: bool|callable, extenders: ExtenderInterface[]|callable|string}>
     */
    protected $conditions = [];

    /**
     * Apply extenders only if a specific extension is enabled.
     *
     * @param string $extensionId The ID of the extension.
     * @param ExtenderInterface[]|callable|string $extenders An array of extenders, a callable returning an array of extenders, or an invokable class string.
     * @return self
     */
    public function whenExtensionEnabled(string $extensionId, $extenders): self
    {
        return $this->when(function (ExtensionManager $extensions) use ($extensionId) {
            return $extensions->isEnabled($extensionId);
        }, $extenders);
    }

    /**
     * Apply extenders only if a specific extension is disabled/not installed.
     *
     * @param string $extensionId The ID of the extension.
     * @param ExtenderInterface[]|callable|string $extenders A callable returning an array of extenders, or an invokable class string.
     * @return self
     */
    public function whenExtensionDisabled(string $extensionId, $extenders): self
    {
        return $this->when(function (ExtensionManager $extensions) use ($extensionId) {
            return ! $extensions->isEnabled($extensionId);
        }, $extenders);
    }

    /**
     * Apply extenders based on a condition.
     *
     * @param bool|callable $condition A boolean or callable that should return a boolean.
     *                                 If this evaluates to true, the extenders will be applied.
     * @param ExtenderInterface[]|callable|string $extenders An array of extenders, a callable returning an array of extenders, or an invokable class string.
     * @return self
     */
    public function when($condition, $extenders): self
    {
        $this->conditions[] = [
            'condition' => $condition,
            'extenders' => $extenders,
        ];

        return $this;
    }

    /**
     * Iterates over the conditions and applies the associated extenders if the conditions are met.
     *
     * @param Container $container
     * @param Extension|null $extension
     * @return void
     */
    public function extend(Container $container, Extension $extension = null)
    {
        foreach ($this->conditions as $condition) {
            if (is_callable($condition['condition'])) {
                $condition['condition'] = $container->call($condition['condition']);
            }

            if ($condition['condition']) {
                $extenders = $condition['extenders'];

                if (is_string($extenders) || is_callable($extenders)) {
                    $extenders = $container->call($extenders);
                }

                foreach ($extenders as $extender) {
                    $extender->extend($container, $extension);
                }
            }
        }
    }
}
