<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Frontend;

use Flarum\Extension\Event\Disabled;
use Flarum\Extension\Event\Enabled;
use Flarum\Foundation\ValidationException;
use Flarum\Locale\LocaleManager;
use Flarum\Settings\Event\Saved;
use Flarum\Settings\Event\Saving;
use Flarum\Settings\OverrideSettingsRepository;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Adapter\NullAdapter;
use League\Flysystem\Filesystem;
use Less_Exception_Parser;

class RecompileFrontendAssets
{
    /**
     * @var FrontendCompilerFactory
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
     * @param FrontendCompilerFactory $assets
     * @param LocaleManager $locales
     * @param Container $container
     */
    public function __construct(FrontendCompilerFactory $assets, LocaleManager $locales, Container $container)
    {
        $this->assets = $assets;
        $this->locales = $locales;
        $this->container = $container;
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(Saved::class, [$this, 'whenSettingsSaved']);
        $events->listen(Enabled::class, [$this, 'whenExtensionEnabledOrDisabled']);
        $events->listen(Disabled::class, [$this, 'whenExtensionEnabledOrDisabled']);
    }

    public function whenSettingsSaved(Saved $event)
    {
        if (preg_grep('/^theme_/i', array_keys($event->settings))) {
            $this->flushCss();
        }
    }

    public function whenExtensionEnabledOrDisabled()
    {
        $this->flushCss();
        $this->flushJs();
    }

    protected function flushCss()
    {
        $this->assets->makeCss()->flush();

        foreach ($this->locales->getLocales() as $locale => $name) {
            $this->assets->makeLocaleCss($locale)->flush();
        }
    }

    protected function flushJs()
    {
        $this->assets->makeJs()->flush();

        foreach ($this->locales->getLocales() as $locale => $name) {
            $this->assets->makeLocaleJs($locale)->flush();
        }
    }
}
