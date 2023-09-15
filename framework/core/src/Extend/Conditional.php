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

class Conditional implements ExtenderInterface
{
    /**
     * @var array<array{condition: bool|callable, extenders: ExtenderInterface[]}>
     */
    protected array $conditions = [];

    /**
     * @param ExtenderInterface[] $extenders
     */
    public function whenExtensionEnabled(string $extensionId, array $extenders): self
    {
        return $this->when(function (ExtensionManager $extensions) use ($extensionId) {
            return $extensions->isEnabled($extensionId);
        }, $extenders);
    }

    /**
     * @param ExtenderInterface[] $extenders
     */
    public function whenExtensionDisabled(string $extensionId, array $extenders): self
    {
        return $this->when(function (ExtensionManager $extensions) use ($extensionId) {
            return ! $extensions->isEnabled($extensionId);
        }, $extenders);
    }

    public function when(callable|bool $condition, array $extenders): self
    {
        $this->conditions[] = [
            'condition' => $condition,
            'extenders' => $extenders,
        ];

        return $this;
    }

    public function extend(Container $container, Extension $extension = null): void
    {
        foreach ($this->conditions as $condition) {
            if (is_callable($condition['condition'])) {
                $condition['condition'] = $container->call($condition['condition']);
            }

            if ($condition['condition']) {
                foreach ($condition['extenders'] as $extender) {
                    $extender->extend($container, $extension);
                }
            }
        }
    }
}
