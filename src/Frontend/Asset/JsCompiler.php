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

use axy\sourcemap\SourceMap;

class JsCompiler extends RevisionCompiler
{
    /**
     * {@inheritdoc}
     */
    protected function save(string $file): bool
    {
        $mapFile = $file.'.map';

        $map = new SourceMap();
        $map->file = $mapFile;
        $output = [];
        $line = 0;

        // For each of the sources, get their content and add it to the output.
        // For file sources, if a sourcemap is present, add it to the output sourcemap.
        foreach ($this->content as $source) {
            if (is_callable($source)) {
                $content = $source();
            } else {
                $content = file_get_contents($source);

                if (file_exists($sourceMap = $source.'.map')) {
                    $map->concat($sourceMap, $line);
                }
            }

            $content = $this->format($content);
            $output[] = $content;
            $line += substr_count($content, "\n") + 1;
        }

        // Add a comment to the end of our file to point to the sourcemap we just constructed.
        $output[] = '//# sourceMappingURL='.$this->assetsDir->url($mapFile);

        $this->assetsDir->put($file, implode("\n", $output));

        $map->save($mapFile);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function format(string $string): string
    {
        return preg_replace('~//# sourceMappingURL.*$~s', '', $string).";\n";
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
