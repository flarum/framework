<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Illuminate\Contracts\Container\Container;

class Theme implements ExtenderInterface
{
    private $lessImportOverrides = [];

    public function overrideLessImport(string $file, string $newFilePath, string $extensionId = null): self
    {
        $this->lessImportOverrides[] = compact('file', 'newFilePath', 'extensionId');

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('flarum.assets.factory', function (callable $factory) {
            return function (...$args) use ($factory) {
                $assets = $factory(...$args);

                $assets->setLessImportOverrides($this->lessImportOverrides);

                return $assets;
            };
        });
    }
}
