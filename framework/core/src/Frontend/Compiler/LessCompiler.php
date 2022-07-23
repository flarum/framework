<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Compiler;

use Flarum\Frontend\Compiler\Source\FileSource;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Less_Parser;

/**
 * @internal
 */
class LessCompiler extends RevisionCompiler
{
    /**
     * @var string
     */
    protected $cacheDir;

    /**
     * @var array
     */
    protected $importDirs = [];

    /**
     * @var array
     */
    protected $customFunctions = [];

    /**
     * @var Collection|null
     */
    protected $lessImportOverrides;

    /**
     * @var Collection|null
     */
    protected $fileSourceOverrides;

    public function getCacheDir(): string
    {
        return $this->cacheDir;
    }

    public function setCacheDir(string $cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    public function getImportDirs(): array
    {
        return $this->importDirs;
    }

    public function setImportDirs(array $importDirs)
    {
        $this->importDirs = $importDirs;
    }

    public function setLessImportOverrides(array $lessImportOverrides)
    {
        $this->lessImportOverrides = new Collection($lessImportOverrides);
    }

    public function setFileSourceOverrides(array $fileSourceOverrides)
    {
        $this->fileSourceOverrides = new Collection($fileSourceOverrides);
    }

    public function setCustomFunctions(array $customFunctions)
    {
        $this->customFunctions = $customFunctions;
    }

    /**
     * @throws \Less_Exception_Parser
     */
    protected function compile(array $sources): string
    {
        if (! count($sources)) {
            return '';
        }

        ini_set('xdebug.max_nesting_level', '200');

        $parser = new Less_Parser([
            'compress' => true,
            'cache_dir' => $this->cacheDir,
            'import_dirs' => $this->importDirs,
            'import_callback' => $this->lessImportOverrides ? $this->overrideImports($sources) : null,
        ]);

        if ($this->fileSourceOverrides) {
            $sources = $this->overrideSources($sources);
        }

        foreach ($sources as $source) {
            if ($source instanceof FileSource) {
                $parser->parseFile($source->getPath());
            } else {
                $parser->parse($source->getContent());
            }
        }

        foreach ($this->customFunctions as $name => $callback) {
            $parser->registerFunction($name, $callback);
        }

        return $parser->getCss();
    }

    protected function overrideSources(array $sources): array
    {
        foreach ($sources as $source) {
            if ($source instanceof FileSource) {
                $basename = basename($source->getPath());
                $override = $this->fileSourceOverrides
                    ->where('file', $basename)
                    ->firstWhere('extensionId', $source->getExtensionId());

                if ($override) {
                    $source->setPath($override['newFilePath']);
                }
            }
        }

        return $sources;
    }

    protected function overrideImports(array $sources): callable
    {
        $baseSources = (new Collection($sources))->filter(function ($source) {
            return $source instanceof Source\FileSource;
        })->map(function (FileSource $source) {
            $path = realpath($source->getPath());
            $path = Str::beforeLast($path, '/less/');

            return [
                'path' => $path,
                'extensionId' => $source->getExtensionId(),
            ];
        })->unique('path');

        return function ($evald) use ($baseSources): ?array {
            $relativeImportPath = Str::of($evald->PathAndUri()[0])->split('/\/less\//');
            $extensionId = $baseSources->where('path', $relativeImportPath->first())->pluck('extensionId')->first();

            $overrideImport = $this->lessImportOverrides
                ->where('file', $relativeImportPath->last())
                ->firstWhere('extensionId', $extensionId);

            if (! $overrideImport) {
                return null;
            }

            return [$overrideImport['newFilePath'], $evald->PathAndUri()[1]];
        };
    }

    protected function getCacheDifferentiator(): ?array
    {
        return [
            'import_dirs' => $this->importDirs
        ];
    }
}
