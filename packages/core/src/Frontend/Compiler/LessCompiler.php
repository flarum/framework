<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Compiler;

use Flarum\Frontend\Compiler\Source\FileSource;
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
            'import_dirs' => $this->importDirs
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

    protected function getCacheDifferentiator(): ?array
    {
        return [
            'import_dirs' => $this->importDirs
        ];
    }
}
