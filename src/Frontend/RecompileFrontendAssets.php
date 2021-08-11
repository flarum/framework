<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend;

use Flarum\Locale\LocaleManager;
use Flarum\Settings\Event\Saved;

/**
 * @internal
 */
class RecompileFrontendAssets
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
     * @var array
     */
    protected $cssRefreshingSettings;

    /**
     * @param Assets $assets
     * @param LocaleManager $locales
     */
    public function __construct(Assets $assets, LocaleManager $locales, array $cssRefreshingSettings = [])
    {
        $this->assets = $assets;
        $this->locales = $locales;
        $this->cssRefreshingSettings = $cssRefreshingSettings;
    }

    public function whenSettingsSaved(Saved $event)
    {
        if (! empty($this->cssRefreshingSettings)) {
            $refreshingSettings = array_intersect(
                array_keys($event->settings),
                array_map(function ($v) {
                    return $v['key'];
                }, $this->cssRefreshingSettings)
            );
        }

        // @deprecated 'theme_' check, to be removed in 2.0
        if (preg_grep('/^theme_/i', array_keys($event->settings)) || ! empty($refreshingSettings)) {
            $this->flushCss();
        }
    }

    public function flush()
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
