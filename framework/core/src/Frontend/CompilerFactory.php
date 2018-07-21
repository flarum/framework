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

use Flarum\Frontend\Asset\AssetInterface;
use Flarum\Frontend\Compiler\CompilerInterface;
use Flarum\Frontend\Compiler\JsCompiler;
use Flarum\Frontend\Compiler\LessCompiler;
use Flarum\Frontend\Compiler\Source\SourceCollector;
use Illuminate\Filesystem\FilesystemAdapter;

/**
 * A factory class for creating frontend asset compilers.
 */
class CompilerFactory
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
     * @var string
     */
    protected $cacheDir;

    /**
     * @var array
     */
    protected $lessImportDirs;

    /**
     * @var AssetInterface[]
     */
    protected $assets = [];

    /**
     * @var callable[]
     */
    protected $addCallbacks = [];

    /**
     * @param string $name
     * @param FilesystemAdapter $assetsDir
     * @param string $cacheDir
     * @param array|null $lessImportDirs
     */
    public function __construct(string $name, FilesystemAdapter $assetsDir, string $cacheDir = null, array $lessImportDirs = null)
    {
        $this->name = $name;
        $this->assetsDir = $assetsDir;
        $this->cacheDir = $cacheDir;
        $this->lessImportDirs = $lessImportDirs;
    }

    /**
     * @param callable $callback
     */
    public function add(callable $callback)
    {
        $this->addCallbacks[] = $callback;
    }

    /**
     * @return JsCompiler
     */
    public function makeJs(): JsCompiler
    {
        $compiler = new JsCompiler($this->assetsDir, $this->name.'.js');

        $this->addSources($compiler, function (AssetInterface $asset, SourceCollector $sources) {
            $asset->js($sources);
        });

        return $compiler;
    }

    /**
     * @return LessCompiler
     */
    public function makeCss(): LessCompiler
    {
        $compiler = $this->makeLessCompiler($this->name.'.css');

        $this->addSources($compiler, function (AssetInterface $asset, SourceCollector $sources) {
            $asset->css($sources);
        });

        return $compiler;
    }

    /**
     * @param string $locale
     * @return JsCompiler
     */
    public function makeLocaleJs(string $locale): JsCompiler
    {
        $compiler = new JsCompiler($this->assetsDir, $this->name.'-'.$locale.'.js');

        $this->addSources($compiler, function (AssetInterface $asset, SourceCollector $sources) use ($locale) {
            $asset->localeJs($sources, $locale);
        });

        return $compiler;
    }

    /**
     * @param string $locale
     * @return LessCompiler
     */
    public function makeLocaleCss(string $locale): LessCompiler
    {
        $compiler = $this->makeLessCompiler($this->name.'-'.$locale.'.css');

        $this->addSources($compiler, function (AssetInterface $asset, SourceCollector $sources) use ($locale) {
            $asset->localeCss($sources, $locale);
        });

        return $compiler;
    }

    /**
     * @param string $filename
     * @return LessCompiler
     */
    protected function makeLessCompiler(string $filename): LessCompiler
    {
        $compiler = new LessCompiler($this->assetsDir, $filename);

        if ($this->cacheDir) {
            $compiler->setCacheDir($this->cacheDir.'/less');
        }

        if ($this->lessImportDirs) {
            $compiler->setImportDirs($this->lessImportDirs);
        }

        return $compiler;
    }

    protected function fireAddCallbacks()
    {
        foreach ($this->addCallbacks as $callback) {
            $assets = $callback($this);
            $this->assets = array_merge($this->assets, is_array($assets) ? $assets : [$assets]);
        }

        $this->addCallbacks = [];
    }

    private function addSources(CompilerInterface $compiler, callable $callback)
    {
        $compiler->addSources(function ($sources) use ($callback) {
            $this->fireAddCallbacks();

            foreach ($this->assets as $asset) {
                $callback($asset, $sources);
            }
        });
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
     * @return FilesystemAdapter
     */
    public function getAssetsDir(): FilesystemAdapter
    {
        return $this->assetsDir;
    }

    /**
     * @param FilesystemAdapter $assetsDir
     */
    public function setAssetsDir(FilesystemAdapter $assetsDir)
    {
        $this->assetsDir = $assetsDir;
    }

    /**
     * @return string
     */
    public function getCacheDir(): ?string
    {
        return $this->cacheDir;
    }

    /**
     * @param string $cacheDir
     */
    public function setCacheDir(?string $cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    /**
     * @return array
     */
    public function getLessImportDirs(): array
    {
        return $this->lessImportDirs;
    }

    /**
     * @param array $lessImportDirs
     */
    public function setLessImportDirs(array $lessImportDirs)
    {
        $this->lessImportDirs = $lessImportDirs;
    }
}
