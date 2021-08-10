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
    protected $lessImportOverrides = [];

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
        $this->lessImportOverrides = $lessImportOverrides;
    }

    /**
     * @throws \Less_Exception_Parser
     */
    protected function compile(array $sources): string
    {
        if (! count($sources)) {
            return '';
        }

        ini_set('xdebug.max_nesting_level', 200);

        $parser = new Less_Parser([
            'compress' => true,
            'cache_dir' => $this->cacheDir,
            'import_dirs' => $this->importDirs,
            'import_callback' => $this->overrideImports($sources),
        ]);

        foreach ($sources as $source) {
            if ($source instanceof FileSource) {
                $parser->parseFile($source->getPath());
            } else {
                $parser->parse($source->getContent());
            }
        }

        return $parser->getCss();
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

        $lessImportOverrides = new Collection($this->lessImportOverrides);

        return function ($evald) use ($baseSources, $lessImportOverrides): ?array {
            $relativeImportPath = Str::of($evald->PathAndUri()[0])->split('/\/less\//');
            $extensionId = $baseSources->where('path', $relativeImportPath->first())->pluck('extensionId')->first();

            $overrideImport = $lessImportOverrides
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
