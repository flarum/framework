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

use Flarum\Extension\Extension;
use Flarum\Frontend\Compiler\Source\SourceCollector;

class ExtensionAssets implements AssetInterface
{
    /**
     * @var Extension
     */
    protected $extension;

    /**
     * @var array
     */
    protected $css;

    /**
     * @var string|callable|null
     */
    protected $js;

    /**
     * @param Extension $extension
     * @param array $css
     * @param string|callable|null $js
     */
    public function __construct(Extension $extension, array $css, $js = null)
    {
        $this->extension = $extension;
        $this->css = $css;
        $this->js = $js;
    }

    public function js(SourceCollector $sources)
    {
        if ($this->js) {
            $sources->addString(function () {
                return 'var module={}';
            });

            if (is_callable($this->js)) {
                $sources->addString($this->js);
            } else {
                $sources->addFile($this->js);
            }

            $sources->addString(function () {
                $name = $this->extension->getId();
                return "flarum.extensions['$name']=module.exports";
            });
        }
    }

    public function css(SourceCollector $sources)
    {
        foreach ($this->css as $asset) {
            if (is_callable($asset)) {
                $sources->addString($asset);
            } else {
                $sources->addFile($asset);
            }
        }
    }

    public function localeJs(SourceCollector $sources, string $locale)
    {
    }

    public function localeCss(SourceCollector $sources, string $locale)
    {
    }
}
