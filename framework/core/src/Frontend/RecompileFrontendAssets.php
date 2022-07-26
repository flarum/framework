<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend;

use Flarum\Frontend\Asset\Type;
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
     * @param Assets $assets
     * @param LocaleManager $locales
     */
    public function __construct(Assets $assets, LocaleManager $locales)
    {
        $this->assets = $assets;
        $this->locales = $locales;
    }

    public function whenSettingsSaved(Saved $event)
    {
        // @deprecated 'theme_' check, to be removed in 2.0
        if (preg_grep('/^theme_/i', array_keys($event->settings))) {
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
        $this->assets->getAssets()->ofType('css')->each(function (Type $asset) {
            $asset->getCompiler()->commit();
        });
    }

    protected function flushJs()
    {
        $this->assets->getAssets()->ofType('js')->each(function (Type $asset) {
            $asset->getCompiler()->commit();
        });
    }
}
