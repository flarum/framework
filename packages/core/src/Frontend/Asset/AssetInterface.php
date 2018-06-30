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

interface AssetInterface
{
    /**
     * @param SourceCollector $sources
     */
    public function js(SourceCollector $sources);

    /**
     * @param SourceCollector $sources
     */
    public function css(SourceCollector $sources);

    /**
     * @param SourceCollector $sources
     * @param string $locale
     */
    public function localeJs(SourceCollector $sources, string $locale);

    /**
     * @param SourceCollector $sources
     * @param string $locale
     */
    public function localeCss(SourceCollector $sources, string $locale);
}
