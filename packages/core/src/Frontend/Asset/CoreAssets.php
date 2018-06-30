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

class CoreAssets implements AssetInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function js(SourceCollector $sources)
    {
        $sources->addFile(__DIR__."/../../../js/dist/$this->name.js");
    }

    public function css(SourceCollector $sources)
    {
        $sources->addFile(__DIR__."/../../../less/$this->name.less");
    }

    public function localeJs(SourceCollector $sources, string $locale)
    {
    }

    public function localeCss(SourceCollector $sources, string $locale)
    {
    }
}
