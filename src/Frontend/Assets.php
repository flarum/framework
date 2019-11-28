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
use Illuminate\Contracts\Filesystem\Filesystem;

/**
 * A factory class for creating frontend asset compilers.
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

    public function __construct(string $name, Filesystem $assetsDir, string $cacheDir = null, array $lessImportDirs = null)
    {
        $this->name = $name;
        $this->assetsDir = $assetsDir;
        $this->cacheDir = $cacheDir;
        $this->lessImportDirs = $lessImportDirs;
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
        $compiler = new JsCompiler($this->assetsDir, $this->name.'.js');

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
        $compiler = new JsCompiler($this->assetsDir, $this->name.'-'.$locale.'.js');

        $this->populate($compiler, 'localeJs', $locale);

        return $compiler;
    }

    public function makeLocaleCss(string $locale): LessCompiler
    {
        $compiler = $this->makeLessCompiler($this->name.'-'.$locale.'.css');

        $this->populate($compiler, 'localeCss', $locale);

        return $compiler;
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
}
