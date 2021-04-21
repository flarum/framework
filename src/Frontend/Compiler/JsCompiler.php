<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Compiler;

use axy\sourcemap\SourceMap;
use Flarum\Frontend\Compiler\Source\FileSource;
use Flarum\Frontend\Compiler\Source\FolderSource;

class JsCompiler extends RevisionCompiler
{
    /**
     * {@inheritdoc}
     */
    protected function save(string $file, array $sources): bool
    {
        if (empty($sources)) {
            return false;
        }

        $directory = $this->name.'/';
        $mapFile = $file.'.map';

        $map = new SourceMap();
        $map->file = $mapFile;
        $output = [];

        if (! $this->type) {
            $output[] = 'var module={};';
        }

        $line = 0;

        // For each of the sources, get their content and add it to the
        // output. For folder sources, add all the files contained in that folder.
        foreach ($sources as $source) {
            $content = $source->getContent();

            if ($source instanceof FolderSource) {
                foreach ($content as $file) {
                    $filepath = $source->getPath().'/'.$file;
                    if (is_dir($filepath)) {
                        $this->levelN($filepath, $source, $file);
                    } else {
                        if (pathinfo($filepath, PATHINFO_EXTENSION) !== 'js') {
                            $this->assetsDir->put($source->getDirectoryName().'/'.$file, file_get_contents($filepath));
                        } else {
                            $content = file_get_contents($filepath);
                            $cacheDifferentiator = hash('crc32b', serialize(array_merge($source->getCacheDifferentiator(), [$content])));
                            $this->putFile($source->getDirectoryName().'/'.$file, $cacheDifferentiator, $content);
                        }
                    }
                }
            } else {
                if ($source instanceof FileSource) {
                    $sourceMap = $source->getPath().'.map';

                    if (file_exists($sourceMap)) {
                        $map->concat($sourceMap, $line);
                    }
                }

                $content = $this->format($content);
                $output[] = $content;
                $line += substr_count($content, "\n") + 1;
            }
        }

        $cacheDifferentiator = hash('crc32b', serialize($output));

        $this->putFile($this->name.'/'.$this->name.'-'.$this->type.'.js', $cacheDifferentiator, $output);

        // Add a comment to the end of our file to point to the sourcemap
        // we just constructed. We will then store the JS file and the map
        // in our asset directory.
        $output[] = '//# sourceMappingURL='.$this->assetsDir->url($mapFile);

        $this->assetsDir->put($directory.$mapFile, json_encode($map, JSON_UNESCAPED_SLASHES));

        return true;
    }

    private function levelN($filepath, $source, $file)
    {
        $this->assetsDir->makeDirectory($file);
        foreach (array_diff(scandir($filepath), ['.', '..']) as $levelNFile) {
            $filepath2 = $filepath.'/'.$levelNFile;
            if (is_dir($filepath2)) {
                $this->levelN($filepath2, $source, $file.'/'.$levelNFile);
            } else {
                $this->assetsDir->put($source->getDirectoryName().'/'.$file.'/'.$levelNFile, file_get_contents($filepath2));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function format(string $string): string
    {
        return preg_replace('~//# sourceMappingURL.*$~m', '', $string)."\n";
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
