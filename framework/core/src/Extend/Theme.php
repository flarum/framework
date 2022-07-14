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
use RuntimeException;

class Theme implements ExtenderInterface
{
    private $lessImportOverrides = [];
    private $fileSourceOverrides = [];
    private $customFunctions = [];
    private $lessVariables = [];

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
     * All custom Less functions may only return numbers, strings or booleans.
     *
     * **Example usage:**
     * ```php
     * (new Extend\Theme)
     *     ->addCustomLessFunction('is-flarum', function (mixed $text) {
     *         return strtolower($text) === 'flarum'
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
        $this->customFunctions[$functionName] = function (...$args) use ($callable, $functionName) {
            $argVals = array_map(function ($arg) {
                return $arg->value;
            }, $args);

            $return = $callable(...$argVals);

            if (is_bool($return)) {
                return new \Less_Tree_Quoted('', $return ? 'true' : 'false');
            }

            if (is_string($return)) {
                return new \Less_Tree_Quoted('', $return);
            }

            if (is_numeric($return)) {
                return new \Less_Tree_Dimension($return);
            }

            throw new RuntimeException('Custom Less function `'.$functionName.'` must only return a string, number or boolean.');
        };

        return $this;
    }

    /**
     * Defines a new Less variable to be accessible in all Less files.
     *
     * If you want to expose a setting from your database to Less, you should use
     * the `registerLessConfigVar` extender from `Extend\Settings` instead.
     *
     * Please note the value returned from the callable will be inserted directly
     * into the Less source. If it is unsafe in some way (e.g., contains a
     * semi-colon), this will result in potential security issues with your
     * stylesheet.
     *
     * Likewise, if you need your variable to be a string, you should surround it
     * with quotes yourself.
     *
     * ```php
     * (new Extend\Theme())
     *   ->addCustomLessVariable('my-extension__asset_path', function () {
     *     $url = resolve(UrlGenerator::class);
     *     $assetUrl = $url->to('forum')->base().'/assets/extensions/my-extension/my-asset.jpg';
     *     return "\"$assetUrl\"";
     *   })
     * ```
     *
     * @param string $variableName Name of the variable identifier.
     * @param callable $value The PHP function to run, which returns the value for the variable.
     *
     * @return self
     */
    public function addCustomLessVariable(string $variableName, callable $value): self
    {
        $this->lessVariables[$variableName] = $value;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('flarum.frontend.custom_less_functions', function (array $customFunctions) {
            return array_merge($customFunctions, $this->customFunctions);
        });

        $container->extend('flarum.less.custom_variables', function (array $lessVariables) {
            return array_merge($this->lessVariables, $lessVariables);
        });

        $container->extend('flarum.assets.factory', function (callable $factory) {
            return function (...$args) use ($factory) {
                /** @var Assets $assets */
                $assets = $factory(...$args);

                $assets->addLessImportOverrides($this->lessImportOverrides);
                $assets->addFileSourceOverrides($this->fileSourceOverrides);

                return $assets;
            };
        });
    }
}
