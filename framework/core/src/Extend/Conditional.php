<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Condition;
use Flarum\Extension\Extension;
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
     * - 'extenders': a callable returning an array of extenders, or an invokable class string.
     *
     * @var array<array{condition: bool|callable, extenders: callable|string}>
     */
    protected array $conditions = [];

    /**
     * Apply extenders only if a specific extension is enabled.
     *
     * @param string $extensionId The ID of the extension.
     * @param callable|string $extenders A callable returning an array of extenders, or an invokable class string.
     */
    public function whenExtensionEnabled(string $extensionId, callable|string $extenders): self
    {
        return $this->when(Condition::extensionEnabled($extensionId), $extenders);
    }

    /**
     * Apply extenders only if a specific extension is disabled.
     *
     * @param string $extensionId The ID of the extension.
     * @param callable|string $extenders A callable returning an array of extenders, or an invokable class string.
     */
    public function whenExtensionDisabled(string $extensionId, callable|string $extenders): self
    {
        return $this->when(Condition::extensionDisabled($extensionId), $extenders);
    }

    /**
     * Apply extenders only if all of the given extensions are enabled.
     *
     * @param string[] $extensionIds The IDs of the extensions.
     * @param callable|string $extenders A callable returning an array of extenders, or an invokable class string.
     */
    public function whenExtensionsEnabled(array $extensionIds, callable|string $extenders): self
    {
        return $this->when(Condition::extensionsEnabled($extensionIds), $extenders);
    }

    /**
     * Apply extenders only if all of the given extensions are disabled.
     *
     * @param string[] $extensionIds The IDs of the extensions.
     * @param callable|string $extenders A callable returning an array of extenders, or an invokable class string.
     */
    public function whenExtensionsDisabled(array $extensionIds, callable|string $extenders): self
    {
        return $this->when(Condition::extensionsDisabled($extensionIds), $extenders);
    }

    /**
     * Apply extenders only if a setting matches an expected value.
     *
     * @param string $key The settings key.
     * @param mixed $expected The expected value.
     * @param callable|string $extenders A callable returning an array of extenders, or an invokable class string.
     * @param bool $strict Whether to use strict comparison (===). Defaults to false (loose comparison ==).
     */
    public function whenSetting(string $key, mixed $expected, callable|string $extenders, bool $strict = false): self
    {
        return $this->when(Condition::setting($key, $expected, $strict), $extenders);
    }

    /**
     * Apply extenders based on a condition.
     *
     * @param bool|callable $condition A boolean or callable that should return a boolean.
     *                                 If this evaluates to true, the extenders will be applied.
     * @param callable|string $extenders A callable returning an array of extenders, or an invokable class string.
     */
    public function when(callable|bool $condition, callable|string $extenders): self
    {
        $this->conditions[] = [
            'condition' => $condition,
            'extenders' => $extenders,
        ];

        return $this;
    }

    /**
     * Iterates over the conditions and applies the associated extenders if the conditions are met.
     */
    public function extend(Container $container, ?Extension $extension = null): void
    {
        $this->forEachExtender($container, fn ($extender) => $extender->extend($container, $extension));
    }

    /** @internal */
    public function forEachExtender(Container $container, callable $callback): void
    {
        foreach ($this->conditions as $condition) {
            if (is_callable($condition['condition'])) {
                $condition['condition'] = $container->call($condition['condition']);
            }

            if ($condition['condition']) {
                $extenders = $condition['extenders'];

                $extenders = $container->call($extenders);

                foreach ($extenders as $extender) {
                    $callback($extender);
                }
            }
        }
    }
}
