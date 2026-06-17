<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend;

use Flarum\Frontend\Event\AssetsRecompiled;
use Flarum\Locale\LocaleManager;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * @internal
 */
class RecompileFrontendAssets
{
    public function __construct(
        protected Assets $assets,
        protected LocaleManager $locales,
        protected ?Dispatcher $events = null
    ) {
    }

    public function flush(): void
    {
        $this->flushCss();
        $this->flushJs();

        $this->events?->dispatch(new AssetsRecompiled());
    }

    protected function flushCss(): void
    {
        $this->assets->makeCss()->flush();

        foreach ($this->locales->getLocales() as $locale => $name) {
            $this->assets->makeLocaleCss($locale)->flush();
        }
    }

    protected function flushJs(): void
    {
        $this->assets->makeJs()->flush();

        foreach ($this->locales->getLocales() as $locale => $name) {
            $this->assets->makeLocaleJs($locale)->flush();
        }

        $this->assets->makeJsDirectory()->flush();
    }
}
