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
use Less_Exception_Compiler;
use Less_FileManager;
use Less_Parser;
use Less_Tree_Import;

/**
 * @internal
 */
class LessCompiler extends RevisionCompiler
{
    protected string $cacheDir;
    protected array $importDirs = [];
    protected array $customFunctions = [];
    protected ?Collection $lessImportOverrides = null;
    protected ?Collection $fileSourceOverrides = null;

    public function getCacheDir(): string
    {
        return $this->cacheDir;
    }

    public function setCacheDir(string $cacheDir): void
    {
        $this->cacheDir = $cacheDir;
    }

    public function getImportDirs(): array
    {
        return $this->importDirs;
    }

    public function setImportDirs(array $importDirs): void
    {
        $this->importDirs = $importDirs;
    }

    public function setLessImportOverrides(array $lessImportOverrides): void
    {
        $this->lessImportOverrides = new Collection($lessImportOverrides);
    }

    public function setFileSourceOverrides(array $fileSourceOverrides): void
    {
        $this->fileSourceOverrides = new Collection($fileSourceOverrides);
    }

    public function setCustomFunctions(array $customFunctions): void
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

        if (! empty($this->settings->get('custom_less_error'))) {
            unset($sources['custom_less']);
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

        try {
            $compiled = $this->finalize($parser->getCss());

            if (isset($sources['custom_less'])) {
                $this->settings->delete('custom_less_error');
            }

            return $compiled;
        } catch (Less_Exception_Compiler $e) {
            if (isset($sources['custom_less'])) {
                unset($sources['custom_less']);

                $compiled = $this->compile($sources);

                $this->settings->set('custom_less_error', $e->getMessage());

                return $compiled;
            }

            throw $e;
        }
    }

    protected function finalize(string $parsedCss): string
    {
        return str_replace('url("../webfonts/', 'url("./fonts/', $parsedCss);
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

        return function (Less_Tree_Import $evald) use ($baseSources): ?array {
            $pathAndUri = Less_FileManager::getFilePath($evald->getPath(), $evald->currentFileInfo);

            $relativeImportPath = Str::of($pathAndUri[0])->split('/\/less\//');
            $extensionId = $baseSources->where('path', $relativeImportPath->first())->pluck('extensionId')->first();

            $overrideImport = $this->lessImportOverrides
                ->where('file', $relativeImportPath->last())
                ->firstWhere('extensionId', $extensionId);

            if (! $overrideImport) {
                return null;
            }

            return [$overrideImport['newFilePath'], $pathAndUri[1]];
        };
    }

    protected function getCacheDifferentiator(): ?array
    {
        return [
            'import_dirs' => $this->importDirs
        ];
    }
}
