<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend;

use Flarum\Locale\LocaleManager;

/**
 * @internal
 */
class RecompileFrontendAssets
{
    public function __construct(
        protected Assets $assets,
        protected LocaleManager $locales
    ) {
    }

    public function flush(): void
    {
        $this->flushCss();
        $this->flushJs();
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
