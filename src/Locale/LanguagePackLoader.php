<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Locale;

use Flarum\Extend\LanguagePack;
use Flarum\Extension\Extension;
use Flarum\Extension\ExtensionFinder;
use Flarum\Foundation\Application;
use Illuminate\Filesystem\Filesystem;

/**
 * This class provides a basic loader for the
 * language packs. It should only used when
 * it's not possible to load extensions normally.
 */
class LanguagePackLoader
{
    /**
     * @var ExtensionFinder
     */
    protected $finder;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Application
     */
    protected $app;

    /**
     * LanguagePackLoader constructor.
     * @param ExtensionFinder $finder
     * @param Filesystem $filesystem
     * @param Application $app
     */
    public function __construct(ExtensionFinder $finder, Filesystem $filesystem, Application $app)
    {
        $this->finder = $finder;
        $this->filesystem = $filesystem;
        $this->app = $app;
    }

    /**
     * Loads all available languages packs.
     */
    public function load()
    {
        $packs = $this->finder->getLanguagePacks();

        foreach ($packs as $pack) {
            // We load the language packs like normal extensions
            $extensionDir = $this->app->basePath().'/vendor/'.array_get($pack, 'name');
            $requirePath = $extensionDir.'/extend.php';
            $composerPath = $extensionDir.'/composer.json';

            if (! $this->filesystem->exists($requirePath) || ! $this->filesystem->extension($composerPath)) {
                continue;
            }

            $extenders = require $requirePath;

            if (! is_array($extenders)) {
                $extenders = [$extenders];
            }

            $foundLanguagePack = false;

            foreach ($extenders as $extender) {
                if ($extender instanceof LanguagePack) {
                    $foundLanguagePack = true;
                }
            }

            if (! $foundLanguagePack) {
                continue;
            }

            $composerJson = $this->filesystem->get($composerPath);
            $extension = new Extension($extensionDir, json_decode($composerJson, true));
            $extension->extend($this->app);
            // $extension->enable($this->app);
        }
    }
}
