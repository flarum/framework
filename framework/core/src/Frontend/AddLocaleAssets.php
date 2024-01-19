<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend;

use Flarum\Frontend\Compiler\Source\SourceCollector;
use Flarum\Locale\LocaleManager;

/**
 * @internal
 */
class AddLocaleAssets
{
    public function __construct(
        protected LocaleManager $locales
    ) {
    }

    public function to(Assets $assets): void
    {
        $assets->localeJs(function (SourceCollector $sources, string $locale) {
            foreach ($this->locales->getJsFiles($locale) as $file) {
                $sources->addFile($file);
            }
        });

        $assets->localeCss(function (SourceCollector $sources, string $locale) {
            foreach ($this->locales->getCssFiles($locale) as $file) {
                $sources->addFile($file);
            }
        });
    }
}
