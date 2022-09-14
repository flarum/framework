<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend;

use Flarum\Frontend\Compiler\CompilerInterface;
use Flarum\Frontend\Compiler\JsCompiler;
use Flarum\Frontend\Compiler\LessCompiler;
use Flarum\Frontend\Compiler\Source\SourceCollector;
use Illuminate\Contracts\Filesystem\Cloud;

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
     * @var Cloud
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

    public function __construct(string $name, Cloud $assetsDir, string $cacheDir = null, array $lessImportDirs = null, array $customFunctions = [])
    {
        $this->name = $name;
        $this->assetsDir = $assetsDir;
        $this->cacheDir = $cacheDir;
        $this->lessImportDirs = $lessImportDirs;
        $this->customFunctions = $customFunctions;
    }

    public function js($sources)
    {
        $this->addSources('js', $sources);

        return $this;
    }

    public function css($callback)
    {
        $this->addSources('css', $callback);

        return $this;
    }

    public function localeJs($callback)
    {
        $this->addSources('localeJs', $callback);

        return $this;
    }

    public function localeCss($callback)
    {
        $this->addSources('localeCss', $callback);

        return $this;
    }

    private function addSources($type, $callback)
    {
        $this->sources[$type][] = $callback;
    }

    private function populate(CompilerInterface $compiler, string $type, string $locale = null)
    {
        $compiler->addSources(function (SourceCollector $sources) use ($type, $locale) {
            foreach ($this->sources[$type] as $callback) {
                $callback($sources, $locale);
            }
        });
    }

    public function makeJs(): JsCompiler
    {
        $compiler = $this->makeJsCompiler($this->name.'.js');

        $this->populate($compiler, 'js');

        return $compiler;
    }

    public function makeCss(): LessCompiler
    {
        $compiler = $this->makeLessCompiler($this->name.'.css');

        $this->populate($compiler, 'css');

        return $compiler;
    }

    public function makeLocaleJs(string $locale): JsCompiler
    {
        $compiler = $this->makeJsCompiler($this->name.'-'.$locale.'.js');

        $this->populate($compiler, 'localeJs', $locale);

        return $compiler;
    }

    public function makeLocaleCss(string $locale): LessCompiler
    {
        $compiler = $this->makeLessCompiler($this->name.'-'.$locale.'.css');

        $this->populate($compiler, 'localeCss', $locale);

        return $compiler;
    }

    protected function makeJsCompiler(string $filename)
    {
        return resolve(JsCompiler::class, [
            'assetsDir' => $this->assetsDir,
            'filename' => $filename
        ]);
    }

    protected function makeLessCompiler(string $filename): LessCompiler
    {
        $compiler = resolve(LessCompiler::class, [
            'assetsDir' => $this->assetsDir,
            'filename' => $filename
        ]);

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

    public function getAssetsDir(): Cloud
    {
        return $this->assetsDir;
    }

    public function setAssetsDir(Cloud $assetsDir)
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
