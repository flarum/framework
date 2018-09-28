<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Extension;

use Flarum\Extend\Compat;
use Flarum\Extend\LifecycleInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @property string $name
 * @property string $description
 * @property string $type
 * @property array  $keywords
 * @property string $homepage
 * @property string $time
 * @property string $license
 * @property array  $authors
 * @property array  $support
 * @property array  $require
 * @property array  $requireDev
 * @property array  $autoload
 * @property array  $autoloadDev
 * @property array  $conflict
 * @property array  $replace
 * @property array  $provide
 * @property array  $suggest
 * @property array  $extra
 */
class Extension implements Arrayable
{
    const LOGO_MIMETYPES = [
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
    ];

    /**
     * Unique Id of the extension.
     *
     * @info    Identical to the directory in the extensions directory.
     * @example flarum_suspend
     *
     * @var string
     */
    protected $id;
    /**
     * The directory of this extension.
     *
     * @var string
     */
    protected $path;

    /**
     * Composer json of the package.
     *
     * @var array
     */
    protected $composerJson;

    /**
     * Whether the extension is installed.
     *
     * @var bool
     */
    protected $installed = true;

    /**
     * The installed version of the extension.
     *
     * @var string
     */
    protected $version;

    /**
     * @param       $path
     * @param array $composerJson
     */
    public function __construct($path, $composerJson)
    {
        $this->path = $path;
        $this->composerJson = $composerJson;
        $this->assignId();
    }

    /**
     * Assigns the id for the extension used globally.
     */
    protected function assignId()
    {
        list($vendor, $package) = explode('/', $this->name);
        $package = str_replace(['flarum-ext-', 'flarum-'], '', $package);
        $this->id = "$vendor-$package";
    }

    public function extend(Container $app)
    {
        foreach ($this->getExtenders() as $extender) {
            // If an extension has not yet switched to the new extend.php
            // format, it might return a function (or more of them). We wrap
            // these in a Compat extender to enjoy an unique interface.
            if ($extender instanceof \Closure || is_string($extender)) {
                $extender = new Compat($extender);
            }

            $extender->extend($app, $this);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function __get($name)
    {
        return $this->composerJsonAttribute(Str::snake($name, '-'));
    }

    /**
     * {@inheritdoc}
     */
    public function __isset($name)
    {
        return isset($this->{$name}) || $this->composerJsonAttribute(Str::snake($name, '-'));
    }

    /**
     * Dot notation getter for composer.json attributes.
     *
     * @see https://laravel.com/docs/5.1/helpers#arrays
     *
     * @param $name
     * @return mixed
     */
    public function composerJsonAttribute($name)
    {
        return Arr::get($this->composerJson, $name);
    }

    /**
     * @param bool $installed
     * @return Extension
     */
    public function setInstalled($installed)
    {
        $this->installed = $installed;

        return $this;
    }

    /**
     * @return bool
     */
    public function isInstalled()
    {
        return $this->installed;
    }

    /**
     * @param string $version
     * @return Extension
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Loads the icon information from the composer.json.
     *
     * @return array|null
     */
    public function getIcon()
    {
        $icon = $this->composerJsonAttribute('extra.flarum-extension.icon');
        $file = Arr::get($icon, 'image');

        if (is_null($icon) || is_null($file)) {
            return $icon;
        }

        $file = $this->path.'/'.$file;

        if (file_exists($file)) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            if (! array_key_exists($extension, self::LOGO_MIMETYPES)) {
                throw new \RuntimeException('Invalid image type');
            }

            $mimetype = self::LOGO_MIMETYPES[$extension];
            $data = base64_encode(file_get_contents($file));

            $icon['backgroundImage'] = "url('data:$mimetype;base64,$data')";
        }

        return $icon;
    }

    public function enable(Container $container)
    {
        foreach ($this->getLifecycleExtenders() as $extender) {
            $extender->onEnable($container, $this);
        }
    }

    public function disable(Container $container)
    {
        foreach ($this->getLifecycleExtenders() as $extender) {
            $extender->onDisable($container, $this);
        }
    }

    /**
     * The raw path of the directory under extensions.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    private function getExtenders(): array
    {
        $extenderFile = $this->getExtenderFile();

        if (! $extenderFile) {
            return [];
        }

        $extenders = require $extenderFile;

        if (! is_array($extenders)) {
            $extenders = [$extenders];
        }

        return array_flatten($extenders);
    }

    /**
     * @return LifecycleInterface[]
     */
    private function getLifecycleExtenders(): array
    {
        return array_filter(
            $this->getExtenders(),
            function ($extender) {
                return $extender instanceof LifecycleInterface;
            }
        );
    }

    private function getExtenderFile(): ?string
    {
        $filename = "{$this->path}/extend.php";

        if (file_exists($filename)) {
            return $filename;
        }

        // To give extension authors some time to migrate to the new extension
        // format, we will also fallback to the old bootstrap.php name. Consider
        // this feature deprecated.
        $deprecatedFilename = "{$this->path}/bootstrap.php";

        if (file_exists($deprecatedFilename)) {
            return $deprecatedFilename;
        }
    }

    /**
     * Tests whether the extension has assets.
     *
     * @return bool
     */
    public function hasAssets()
    {
        return realpath($this->path.'/assets/') !== false;
    }

    /**
     * Tests whether the extension has migrations.
     *
     * @return bool
     */
    public function hasMigrations()
    {
        return realpath($this->path.'/migrations/') !== false;
    }

    /**
     * Generates an array result for the object.
     *
     * @return array
     */
    public function toArray()
    {
        return (array) array_merge([
            'id'            => $this->getId(),
            'version'       => $this->getVersion(),
            'path'          => $this->path,
            'icon'          => $this->getIcon(),
            'hasAssets'     => $this->hasAssets(),
            'hasMigrations' => $this->hasMigrations(),
        ], $this->composerJson);
    }
}
