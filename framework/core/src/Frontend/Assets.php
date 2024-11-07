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
use Flarum\Frontend\Compiler\JsDirectoryCompiler;
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
    public array $sources = [
        'js' => [],
        'css' => [],
        'localeJs' => [],
        'localeCss' => [],
        'jsDirectory' => [],
    ];

    protected array $lessImportOverrides = [];
    protected array $fileSourceOverrides = [];

    public function __construct(
        protected string $name,
        protected Cloud $assetsDir,
        protected ?string $cacheDir = null,
        protected ?array $lessImportDirs = null,
        protected array $customFunctions = []
    ) {
    }

    public function js(callable $callback): static
    {
        $this->addSources('js', $callback);

        return $this;
    }

    public function css(callable $callback): static
    {
        $this->addSources('css', $callback);

        return $this;
    }

    public function localeJs(callable $callback): static
    {
        $this->addSources('localeJs', $callback);

        return $this;
    }

    public function localeCss(callable $callback): static
    {
        $this->addSources('localeCss', $callback);

        return $this;
    }

    public function jsDirectory(callable $callback): static
    {
        $this->addSources('jsDirectory', $callback);

        return $this;
    }

    private function addSources(string $type, callable $callback): void
    {
        $this->sources[$type][] = $callback;
    }

    private function populate(CompilerInterface $compiler, string $type, ?string $locale = null): void
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

    public function makeJsDirectory(): JsDirectoryCompiler
    {
        $compiler = $this->makeJsDirectoryCompiler('js'.DIRECTORY_SEPARATOR.'{ext}'.DIRECTORY_SEPARATOR.$this->name);

        $this->populate($compiler, 'jsDirectory');

        return $compiler;
    }

    protected function makeJsCompiler(string $filename): JsCompiler
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

    protected function makeJsDirectoryCompiler(string $string): JsDirectoryCompiler
    {
        return resolve(JsDirectoryCompiler::class, [
            'assetsDir' => $this->assetsDir,
            'destinationPath' => $string
        ]);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getAssetsDir(): Cloud
    {
        return $this->assetsDir;
    }

    public function setAssetsDir(Cloud $assetsDir): void
    {
        $this->assetsDir = $assetsDir;
    }

    public function getCacheDir(): ?string
    {
        return $this->cacheDir;
    }

    public function setCacheDir(?string $cacheDir): void
    {
        $this->cacheDir = $cacheDir;
    }

    public function getLessImportDirs(): array
    {
        return $this->lessImportDirs;
    }

    public function setLessImportDirs(array $lessImportDirs): void
    {
        $this->lessImportDirs = $lessImportDirs;
    }

    public function addLessImportOverrides(array $lessImportOverrides): void
    {
        $this->lessImportOverrides = array_merge($this->lessImportOverrides, $lessImportOverrides);
    }

    public function addFileSourceOverrides(array $fileSourceOverrides): void
    {
        $this->fileSourceOverrides = array_merge($this->fileSourceOverrides, $fileSourceOverrides);
    }
}
