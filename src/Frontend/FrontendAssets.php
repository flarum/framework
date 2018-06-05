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
use Flarum\Frontend\Asset\JsCompiler;
use Flarum\Frontend\Asset\LessCompiler;
use Flarum\Frontend\Asset\LocaleJsCompiler;
use Flarum\Locale\LocaleManager;
use Illuminate\Filesystem\FilesystemAdapter;

class FrontendAssets
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var FilesystemAdapter
     */
    protected $assetsDir;

    /**
     * @var LocaleManager
     */
    protected $locales;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @param string $name
     * @param FilesystemAdapter $assetsDir
     * @param LocaleManager $locales
     * @param Application $app
     */
    public function __construct(string $name, FilesystemAdapter $assetsDir, LocaleManager $locales, Application $app)
    {
        $this->name = $name;
        $this->assetsDir = $assetsDir;
        $this->locales = $locales;
        $this->app = $app;
    }

    /**
     * @return JsCompiler
     */
    public function getJs(): JsCompiler
    {
        return new JsCompiler(
            $this->assetsDir,
            "$this->name.js",
            $this->app->inDebugMode()
        );
    }

    /**
     * @return LessCompiler
     */
    public function getCss(): LessCompiler
    {
        return $this->getLessCompiler("$this->name.css");
    }

    /**
     * @param string $locale
     * @return LocaleJsCompiler
     */
    public function getLocaleJs(string $locale): LocaleJsCompiler
    {
        return new LocaleJsCompiler(
            $this->assetsDir,
            "$this->name-$locale.js",
            $this->app->inDebugMode()
        );
    }

    /**
     * @param string $locale
     * @return LessCompiler
     */
    public function getLocaleCss(string $locale): LessCompiler
    {
        return $this->getLessCompiler("$this->name-$locale.css");
    }

    public function flush()
    {
        $this->flushJs();
        $this->flushCss();
    }

    public function flushJs()
    {
        $this->getJs()->flush();
        $this->flushLocaleJs();
    }

    public function flushLocaleJs()
    {
        foreach ($this->locales->getLocales() as $locale => $info) {
            $this->getLocaleJs($locale)->flush();
        }
    }

    public function flushCss()
    {
        $this->getCss()->flush();
        $this->flushLocaleCss();
    }

    public function flushLocaleCss()
    {
        foreach ($this->locales->getLocales() as $locale => $info) {
            $this->getLocaleCss($locale)->flush();
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @param string $filename
     * @return LessCompiler
     */
    protected function getLessCompiler(string $filename): LessCompiler
    {
        $compiler = new LessCompiler(
            $this->assetsDir,
            $filename,
            $this->app->inDebugMode()
        );

        $compiler->setCacheDir($this->app->storagePath().'/less');

        $compiler->setImportDirs([
            $this->app->basePath().'/vendor/components/font-awesome/less' => '',
        ]);

        return $compiler;
    }
}
