<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Flarum\Frontend\Assets;
use Illuminate\Contracts\Container\Container;

class Theme implements ExtenderInterface
{
    private $lessImportOverrides = [];
    private $fileSourceOverrides = [];
    private $customFunctions = [];

    /**
     * This can be used to override LESS files that are imported within the code.
     * For example, core's `forum.less` file imports a `forum/DiscussionListItem.less` file.
     * The contents of this file can be overriden with this method.
     *
     * @param string $file : Relative path of the file to override, for example: `forum/Hero.less`
     * @param string $newFilePath : Absolute path of the new file.
     * @param string|null $extensionId : If overriding an extension file, specify its ID, for example: `flarum-tags`.
     * @return self
     */
    public function overrideLessImport(string $file, string $newFilePath, string $extensionId = null): self
    {
        $this->lessImportOverrides[] = compact('file', 'newFilePath', 'extensionId');

        return $this;
    }

    /**
     * This method allows overriding LESS file sources.
     * For example `forum.less`, `admin.less`, `mixins.less` and `variables.less` are file sources,
     * and can therefore be overriden using this method.
     *
     * @param string $file : Name of the file to override, for example: `admin.less`
     * @param string $newFilePath : Absolute path of the new file.
     * @param string|null $extensionId : If overriding an extension file, specify its ID, for example: `flarum-tags`.
     * @return self
     */
    public function overrideFileSource(string $file, string $newFilePath, string $extensionId = null): self
    {
        $this->fileSourceOverrides[] = compact('file', 'newFilePath', 'extensionId');

        return $this;
    }

    /**
     * This method allows you to add custom Less functions.
     *
     * **Example usage:**
     * ```php
     * use
     *
     * (new Extend\Theme)
     *     ->addLessFunction('is-flarum', function (Less_Tree_Quoted $text) {
     *         return new Less_Tree_Quoted('', strtolower($text) === 'flarum' ? 'true' : 'false')
     *     }),
     * ```
     *
     * @see https://leafo.net/lessphp/docs/#custom_functions
     *
     * @param string $functionName Name of the function identifier.
     * @param callable $callable The PHP function to run when the Less function is called.
     * @return self
     */
    public function addCustomLessFunction(string $functionName, callable $callable): self
    {
        $this->customFunctions[$functionName] = $callable;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('flarum.assets.factory', function (callable $factory) {
            return function (...$args) use ($factory) {
                /** @var Assets $assets */
                $assets = $factory(...$args);

                $assets->addLessImportOverrides($this->lessImportOverrides);
                $assets->addFileSourceOverrides($this->fileSourceOverrides);
                $assets->addCustomLessFunctions($this->customFunctions);

                return $assets;
            };
        });
    }
}
