<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Closure;
use Flarum\Extension\Extension;
use Flarum\Frontend\Assets;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use RuntimeException;

class Theme implements ExtenderInterface
{
    private array $lessImportOverrides = [];
    private array $fileSourceOverrides = [];
    private array $customFunctions = [];
    private array $lessVariables = [];
    private array $logoSize = [];
    private array $images = [];
    private array $manifestModifiers = [];

    /**
     * This can be used to override `LESS` files that are imported within the code.
     * For example, core's `forum.less` file imports a `forum/DiscussionListItem.less` file.
     * The contents of this file can be overridden with this method.
     *
     * @param string $file : Relative path of the file to override, for example: `forum/Hero.less`
     * @param string $newFilePath : Absolute path of the new file.
     * @param string|null $extensionId : If overriding an extension file, specify its ID, for example: `flarum-tags`.
     */
    public function overrideLessImport(string $file, string $newFilePath, ?string $extensionId = null): self
    {
        $this->lessImportOverrides[] = compact('file', 'newFilePath', 'extensionId');

        return $this;
    }

    /**
     * This method allows overriding LESS file sources.
     * For example `forum.less`, `admin.less`, `mixins.less` and `variables.less` are file sources,
     * and can therefore be overridden using this method.
     *
     * @param string $file : Name of the file to override, for example: `admin.less`
     * @param string $newFilePath : Absolute path of the new file.
     * @param string|null $extensionId : If overriding an extension file, specify its ID, for example: `flarum-tags`.
     */
    public function overrideFileSource(string $file, string $newFilePath, ?string $extensionId = null): self
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
     */
    public function addCustomLessFunction(string $functionName, callable $callable): self
    {
        $this->customFunctions[$functionName] = function (...$args) use ($callable, $functionName) {
            $argVals = array_map(function ($arg) {
                return $arg->value;
            }, $args);

            $return = $callable(...$argVals);

            if (is_bool($return)) {
                return new \Less_Tree_Keyword($return ? 'true' : 'false');
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
     */
    public function addCustomLessVariable(string $variableName, callable $value): self
    {
        $this->lessVariables[$variableName] = $value;

        return $this;
    }

    /**
     * Sets how the forum logo and the dark mode logo are stored.
     *
     * The header shows the logo at most 30px tall. By default it is stored 90px
     * tall, so it stays sharp on screens with three times the pixel density, as
     * WebP at quality 90. A theme that shows the logo larger can store it taller.
     * An animated logo is stored as GIF, which has no quality setting, and an SVG
     * logo set with logo() is used as it is.
     *
     * A logo that has already been uploaded keeps its size and quality until it
     * is uploaded again.
     *
     * ```php
     * (new Extend\Theme())
     *   ->logoSize(height: 120, quality: 95)
     * ```
     *
     * @param int|null $height The height to store the logo at, in pixels.
     * @param int|null $quality The WebP quality, from 1 to 100.
     */
    public function logoSize(?int $height = null, ?int $quality = null): self
    {
        $this->logoSize = array_merge($this->logoSize, array_filter(compact('height', 'quality'), fn (?int $value) => $value !== null));

        return $this;
    }

    /**
     * Sets the forum logo. Admins see it on the Appearance page but can't change it.
     *
     * An SVG is used as it is. Any other image is stored like an uploaded logo.
     *
     * Instead of a path, any image can be given a callable that returns one,
     * to choose it on each request. Its parameters are resolved from the
     * container. While it returns null, the image is the admin's to set.
     * Returning a different file draws the image again, so it should return
     * the same file for every visitor.
     *
     * ```php
     * (new Extend\Theme())
     *   ->logo(fn (SettingsRepositoryInterface $settings) => __DIR__."/logos/{$settings->get('acme.season')}.svg")
     * ```
     *
     * @param string|callable $file Absolute path of a PNG, JPEG, WebP, GIF or SVG file.
     */
    public function logo(string|callable $file): self
    {
        $this->images['logo'] = ['file' => $this->imageFile($file, svg: true)];

        return $this;
    }

    /**
     * Sets the logo shown in dark mode. Admins can't change it.
     *
     * @param string|callable $file Absolute path of a PNG, JPEG, WebP, GIF or SVG file.
     */
    public function logoDarkMode(string|callable $file): self
    {
        $this->images['logo_dark_mode'] = ['file' => $this->imageFile($file, svg: true)];

        return $this;
    }

    /**
     * Sets the logo shown in emails. Admins can't change it.
     *
     * Mail clients can't be relied on to show SVG, so this must be another format.
     * Without it, emails use a copy of a logo set with logo(), if that isn't an SVG.
     *
     * @param string|callable $file Absolute path of a PNG, JPEG, WebP or GIF file.
     */
    public function emailLogo(string|callable $file): self
    {
        $this->images['email_logo'] = ['file' => $this->imageFile($file, svg: false)];

        return $this;
    }

    /**
     * Sets the favicon. Admins can't change it.
     *
     * The .ico and PNG versions browsers need are drawn from the image, which
     * should be at least 48px square. Browsers that support SVG favicons use
     * the SVG, if one is given, instead.
     *
     * @param string|callable $file Absolute path of a PNG, JPEG, WebP or GIF file.
     * @param string|callable|null $svg Absolute path of an SVG version.
     */
    public function favicon(string|callable $file, string|callable|null $svg = null): self
    {
        $this->images['favicon'] = [
            'file' => $this->imageFile($file, svg: false),
            'svg' => $svg === null ? null : $this->svgFile($svg),
        ];

        return $this;
    }

    /**
     * Sets the app icon that phones show for the forum on their home screen.
     * Admins can't change it.
     *
     * The icon should be square and at least 512px. Apple's touch icon and
     * Android's maskable icon are drawn on the background colour.
     *
     * @param string|callable $file Absolute path of a PNG, JPEG, WebP or GIF file.
     * @param string|callable|null $background A CSS hex colour. Defaults to the one set on the Appearance page.
     */
    public function appIcon(string|callable $file, string|callable|null $background = null): self
    {
        $this->images['app_icon'] = [
            'file' => $this->imageFile($file, svg: false),
            'background' => is_string($background) || $background === null ? $background : Closure::fromCallable($background),
        ];

        return $this;
    }

    /**
     * Changes the forum's web app manifest, which phones read when the forum
     * is added to a home screen.
     *
     * ```php
     * (new Extend\Theme())
     *   ->manifest(fn (array $manifest) => ['short_name' => 'Acme'] + $manifest)
     * ```
     *
     * @param callable(array): array $callback Receives the manifest and returns it changed.
     */
    public function manifest(callable $callback): self
    {
        $this->manifestModifiers[] = $callback;

        return $this;
    }

    private function imageFile(string|callable $file, bool $svg): string|Closure
    {
        if (! is_string($file)) {
            return Closure::fromCallable($file);
        }

        if (! is_file($file)) {
            throw new InvalidArgumentException("The image $file does not exist.");
        }

        if (! $svg && $this->isSvg($file)) {
            throw new InvalidArgumentException("The image $file must be a PNG, JPEG, WebP or GIF file, not an SVG.");
        }

        return $file;
    }

    private function svgFile(string|callable $file): string|Closure
    {
        if (! is_string($file)) {
            return Closure::fromCallable($file);
        }

        if (! is_file($file) || ! $this->isSvg($file)) {
            throw new InvalidArgumentException("The image $file must be an SVG file.");
        }

        return $file;
    }

    private function isSvg(string $file): bool
    {
        return strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'svg';
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
        $container->extend('flarum.forum.logo', function (array $logo) {
            return array_merge($logo, $this->logoSize);
        });

        $container->extend('flarum.forum.manifest', function (array $modifiers) {
            return array_merge($modifiers, $this->manifestModifiers);
        });

        $owner = $extension?->getTitle();

        $container->extend('flarum.theme.images', function (array $images) use ($owner) {
            return array_merge($images, array_map(fn (array $image) => $image + ['owner' => $owner], $this->images));
        });

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
