<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Frontend\Asset;

use Flarum\Frontend\Compiler\Source\SourceCollector;
use Flarum\Locale\LocaleManager;

class LocaleAssets implements AssetInterface
{
    /**
     * @var LocaleManager
     */
    protected $locales;

    /**
     * @param LocaleManager $locales
     */
    public function __construct(LocaleManager $locales)
    {
        $this->locales = $locales;
    }

    public function localeJs(SourceCollector $sources, string $locale)
    {
        foreach ($this->locales->getJsFiles($locale) as $file) {
            $sources->addFile($file);
        }
    }

    public function localeCss(SourceCollector $sources, string $locale)
    {
        foreach ($this->locales->getCssFiles($locale) as $file) {
            $sources->addFile($file);
        }
    }

    public function js(SourceCollector $sources)
    {
    }

    public function css(SourceCollector $sources)
    {
    }
}
