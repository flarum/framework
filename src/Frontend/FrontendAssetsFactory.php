<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Frontend;

use Flarum\Foundation\Application;
use Flarum\Locale\LocaleManager;
use Illuminate\Filesystem\FilesystemAdapter;

class FrontendAssetsFactory
{
    /**
     * @var FilesystemAdapter
     */
    protected $assetsDir;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var LocaleManager
     */
    protected $locales;

    /**
     * @param FilesystemAdapter $assetsDir
     * @param Application $app
     * @param LocaleManager $locales
     */
    public function __construct(FilesystemAdapter $assetsDir, Application $app, LocaleManager $locales)
    {
        $this->assetsDir = $assetsDir;
        $this->app = $app;
        $this->locales = $locales;
    }

    /**
     * @param string $name
     * @return FrontendAssets
     */
    public function make(string $name): FrontendAssets
    {
        return new FrontendAssets($name, $this->assetsDir, $this->locales, $this->app);
    }
}
