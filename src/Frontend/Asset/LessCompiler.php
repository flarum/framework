<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Frontend\Asset;

use Less_Parser;

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
     * @return string
     */
    public function getCacheDir(): string
    {
        return $this->cacheDir;
    }

    /**
     * @param string $cacheDir
     */
    public function setCacheDir(string $cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    /**
     * @return array
     */
    public function getImportDirs(): array
    {
        return $this->importDirs;
    }

    /**
     * @param array $importDirs
     */
    public function setImportDirs(array $importDirs)
    {
        $this->importDirs = $importDirs;
    }

    /**
     * {@inheritdoc}
     */
    protected function save(string $file): bool
    {
        if (! count($this->content)) {
            return false;
        }

        ini_set('xdebug.max_nesting_level', 200);

        $mapFile = $file.'.map';

        $parser = new Less_Parser([
            'compress' => true,
            'cache_dir' => $this->cacheDir,
            'import_dirs' => $this->importDirs,
            'sourceMap' => true,
            'sourceMapWriteTo' => $mapTemp = tempnam(sys_get_temp_dir(), $mapFile),
            'sourceMapURL' => $this->assetsDir->url($mapFile),
            'outputSourceFiles' => true
        ]);

        foreach ($this->content as $source) {
            if (is_callable($source)) {
                $parser->parse($source());
            } else {
                $parser->parseFile($source);
            }
        }

        $content = $parser->getCss();

        // The Less parser will append a sourceMappingURL comment to the end of the output.
        // Only if there is actual CSS before this will we write the file and move the sourcemap
        // from its temporary location.
        if (strpos($content, '/*# sourceMappingURL=') === 0) {
            return false;
        }

        $this->assetsDir->put($file, $content);
        $this->assetsDir->put($mapFile, file_get_contents($mapTemp));

        return true;
    }

    /**
     * @return mixed
     */
    protected function getCacheDifferentiator()
    {
        return time();
    }

    /**
     * {@inheritdoc}
     */
    protected function delete(string $file)
    {
        parent::delete($file);

        if ($this->assetsDir->has($mapFile = $file.'.map')) {
            $this->assetsDir->delete($mapFile);
        }
    }
}
