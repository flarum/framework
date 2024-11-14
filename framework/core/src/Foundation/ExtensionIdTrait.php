<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Flarum\Extension\Extension;
use Flarum\Extension\ExtensionManager;

trait ExtensionIdTrait
{
    protected function getClassExtensionId(): ?string
    {
        $extensions = resolve(ExtensionManager::class);

        return $extensions->getExtensions()
                ->mapWithKeys(function (Extension $extension) {
                    return [$extension->getId() => $extension->getNamespace()];
                })
                ->filter(function ($namespace) {
                    return $namespace && str_starts_with(static::class, $namespace);
                })
                ->keys()
                ->first();
    }
}
