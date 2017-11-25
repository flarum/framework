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

use Flarum\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;

class ExtensionFinder
{
    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Collection
     */
    protected $extensions;

    public function __construct(Application $app, Filesystem $filesystem)
    {
        $this->basePath = $app->basePath();
        $this->filesystem = $filesystem;
        $this->extensions = new Collection();
        $this->scanForExtensions();
    }

    private function scanForExtensions()
    {
        $installedPath = $this->basePath.'/vendor/composer/installed.json';

        // Check whether there's a installed.json file.
        if (! $this->filesystem->exists($installedPath)) {
            return;
        }

        // Load all packages installed by composer.
        $installed = json_decode($this->filesystem->get($installedPath), true);

        foreach ($installed as $package) {
            // Ignore all non flarum extension packages
            if (array_get($package, 'type') != 'flarum-extension' || empty(array_get($package, 'name'))) {
                continue;
            }

            $this->extensions->push($package);
        }
    }

    /**
     * Gets a collections of available extensions.
     *
     * @return Collection
     */
    public function getExtensions()
    {
        return $this->extensions;
    }

    /**
     * Gets a collections of available language packs.
     *
     * @return Collection
     */
    public function getLanguagePacks()
    {
        return $this->extensions->filter(function ($value) {
            return array_get($value, 'extra.flarum-locale.code') != null;
        });
    }
}
