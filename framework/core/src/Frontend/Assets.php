<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend;

use Flarum\Frontend\Asset\AssetCollection;
use Flarum\Frontend\Asset\Type;
use Flarum\Frontend\Compiler\JsCompiler;
use Flarum\Frontend\Compiler\LessCompiler;
use Illuminate\Contracts\Filesystem\Filesystem;

/**
 * A factory class for creating frontend asset compilers.
 *
 * @internal
 */
class Assets
{
    /**
     * @var array
     */
    public $sources = [
        'js' => [],
        'css' => [],
        'localeJs' => [],
        'localeCss' => []
    ];

    /**
     * @var string
     */
    protected $name;

    /**
     * @var Filesystem
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
     * @var array
     */
    protected $lessImportOverrides = [];

    /**
     * @var array
     */
    protected $fileSourceOverrides = [];

    /**
     * @var array
     */
    protected $customFunctions = [];
    /**
     * @var array|Type[]
     */
    protected array $assets = [];

    public function __construct(string $name, Filesystem $assetsDir, string $cacheDir = null, array $lessImportDirs = null, array $customFunctions = [])
    {
        $this->name = $name;
        $this->assetsDir = $assetsDir;
        $this->cacheDir = $cacheDir;
        $this->lessImportDirs = $lessImportDirs;
        $this->customFunctions = $customFunctions;
    }

    public function addAsset(Type $asset): static
    {
        $this->assets[$asset->getFilename()] = $asset;

        return $this;
    }

    public function getAssets(): AssetCollection
    {
        return AssetCollection::make($this->assets)
            ->each(function (Type $asset) {
                $asset->setCompiler(
                    $asset->getCompilerClass() === JsCompiler::class
                        ? $this->makeJsCompiler($asset->getFilename())
                        : $this->makeLessCompiler($asset->getFilename())
                );
            });
    }

    protected function makeJsCompiler(string $filename): JsCompiler
    {
        return new JsCompiler($this->assetsDir, $filename);
    }

    protected function makeLessCompiler(string $filename): LessCompiler
    {
        $compiler = new LessCompiler($this->assetsDir, $filename);

        if ($this->cacheDir) {
            $compiler->setCacheDir($this->cacheDir.'/less');
        }

        if ($this->lessImportDirs) {
            $compiler->setImportDirs($this->lessImportDirs);
        }

        if ($this->lessImportOverrides) {
            $compiler->setLessImportOverrides($this->lessImportOverrides);
        }

        if ($this->fileSourceOverrides) {
            $compiler->setFileSourceOverrides($this->fileSourceOverrides);
        }

        $compiler->setCustomFunctions($this->customFunctions);

        return $compiler;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getAssetsDir(): Filesystem
    {
        return $this->assetsDir;
    }

    public function setAssetsDir(Filesystem $assetsDir)
    {
        $this->assetsDir = $assetsDir;
    }

    public function getCacheDir(): ?string
    {
        return $this->cacheDir;
    }

    public function setCacheDir(?string $cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    public function getLessImportDirs(): array
    {
        return $this->lessImportDirs;
    }

    public function setLessImportDirs(array $lessImportDirs)
    {
        $this->lessImportDirs = $lessImportDirs;
    }

    public function addLessImportOverrides(array $lessImportOverrides)
    {
        $this->lessImportOverrides = array_merge($this->lessImportOverrides, $lessImportOverrides);
    }

    public function addFileSourceOverrides(array $fileSourceOverrides)
    {
        $this->fileSourceOverrides = array_merge($this->fileSourceOverrides, $fileSourceOverrides);
    }
}
