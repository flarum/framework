<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Flarum\Frontend\Asset\AbstractAsset;
use Flarum\Frontend\Asset\AssetInterface;
use Flarum\Frontend\Asset\ExtensionAssets;
use Flarum\Frontend\Compiler\CompilerInterface;
use Flarum\Frontend\Event\Rendering;
use Flarum\Frontend\FrontendCompilerFactory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Events\Dispatcher;

class Assets implements Extender
{
    protected $frontend;

    protected $css = [];
    protected $js;

    public function __construct($frontend)
    {
        $this->frontend = $frontend;
    }

    public function css($path)
    {
        $this->css[] = $path;

        return $this;
    }

    /**
     * @deprecated
     */
    public function asset($path)
    {
        return $this->css($path);
    }

    public function js($path)
    {
        $this->js = $path;

        return $this;
    }

    public function __invoke(Container $container, Extension $extension = null)
    {
        $container->resolving(
            "flarum.$this->frontend.assets",
            function (FrontendCompilerFactory $assets) use ($extension) {
                $assets->add(function () use ($extension) {
                    return new ExtensionAssets($extension, $this->css, $this->js);
                });
            }
        );
    }
}
