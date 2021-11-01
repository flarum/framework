<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum;

use Flarum\Foundation\ValidationException;
use Flarum\Frontend\Assets;
use Flarum\Locale\LocaleManager;
use Flarum\Settings\Event\Saved;
use Flarum\Settings\Event\Saving;
use Flarum\Settings\OverrideSettingsRepository;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Adapter\NullAdapter;
use League\Flysystem\Filesystem;
use Less_Exception_Parser;

/**
 * @internal
 */
class ValidateCustomLess
{
    /**
     * @var Assets
     */
    protected $assets;

    /**
     * @var LocaleManager
     */
    protected $locales;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var array
     */
    protected $customLessSettings;

    public function __construct(Assets $assets, LocaleManager $locales, Container $container, array $customLessSettings = [])
    {
        $this->assets = $assets;
        $this->locales = $locales;
        $this->container = $container;
        $this->customLessSettings = $customLessSettings;
    }

    public function whenSettingsSaving(Saving $event)
    {
        if (! isset($event->settings['custom_less']) && ! $this->hasDirtyCustomLessSettings($event)) {
            return;
        }

        // We haven't saved the settings yet, but we want to trial a full
        // recompile of the CSS to see if this custom LESS will break
        // anything. In order to do that, we will temporarily override the
        // settings repository with the new settings so that the recompile
        // is effective. We will also use a dummy filesystem so that nothing
        // is actually written yet.

        $settings = $this->container->make(SettingsRepositoryInterface::class);

        $this->container->extend(
            SettingsRepositoryInterface::class,
            function ($settings) use ($event) {
                return new OverrideSettingsRepository($settings, $event->settings);
            }
        );

        $assetsDir = $this->assets->getAssetsDir();
        $this->assets->setAssetsDir(new FilesystemAdapter(new Filesystem(new NullAdapter)));

        try {
            $this->assets->makeCss()->commit();

            foreach ($this->locales->getLocales() as $locale => $name) {
                $this->assets->makeLocaleCss($locale)->commit();
            }
        } catch (Less_Exception_Parser $e) {
            throw new ValidationException(['custom_less' => $e->getMessage()]);
        }

        $this->assets->setAssetsDir($assetsDir);
        $this->container->instance(SettingsRepositoryInterface::class, $settings);
    }

    public function whenSettingsSaved(Saved $event)
    {
        if (! isset($event->settings['custom_less']) && ! $this->hasDirtyCustomLessSettings($event)) {
            return;
        }

        $this->assets->makeCss()->flush();

        foreach ($this->locales->getLocales() as $locale => $name) {
            $this->assets->makeLocaleCss($locale)->flush();
        }
    }

    /**
     * @param Saved|Saving $event
     * @return bool
     */
    protected function hasDirtyCustomLessSettings($event): bool
    {
        if (empty($this->customLessSettings)) {
            return false;
        }

        $dirtySettings = array_intersect(
            array_keys($event->settings),
            array_map(function ($setting) {
                return $setting['key'];
            }, $this->customLessSettings)
        );

        return ! empty($dirtySettings);
    }
}
