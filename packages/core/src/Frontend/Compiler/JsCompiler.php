<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Frontend\Compiler;

use axy\sourcemap\SourceMap;
use Flarum\Frontend\Compiler\Source\FileSource;

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

        $mapFile = $file.'.map';

        $map = new SourceMap();
        $map->file = $mapFile;
        $output = [];
        $line = 0;

        // For each of the sources, get their content and add it to the
        // output. For file sources, if a sourcemap is present, add it to
        // the output sourcemap.
        foreach ($sources as $source) {
            $content = $source->getContent();

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

        // Add a comment to the end of our file to point to the sourcemap
        // we just constructed. We will then write the JS file, save the
        // map to a temporary location, and then move it to the asset dir.
        $output[] = '//# sourceMappingURL='.$this->assetsDir->url($mapFile);

        $this->assetsDir->put($file, implode("\n", $output));

        $mapTemp = tempnam(sys_get_temp_dir(), $mapFile);
        $map->save($mapTemp);
        $this->assetsDir->put($mapFile, file_get_contents($mapTemp));
        @unlink($mapTemp);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function format(string $string): string
    {
        return preg_replace('~//# sourceMappingURL.*$~m', '', $string).";\n";
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
