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
use Flarum\Frontend\Compiler\Source\SourceInterface;

/**
 * @internal
 */
class JsCompiler extends RevisionCompiler
{
    /**
     * Assemble the JS bundle and its sourcemap. The `.map` sidecar is written
     * here as a side effect; the returned string is the JS file's content, which
     * the parent commits and hashes for the revision. Hashing this — rather than
     * the raw concatenated source — means the revision reflects the sourcemap
     * comment and `format()` rewrite that the written file actually carries.
     *
     * @param SourceInterface[] $sources
     */
    protected function renderOutput(array $sources): ?string
    {
        if (empty($sources)) {
            return null;
        }

        $mapFile = $this->filename.'.map';

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

        // Add a comment to the end of our file to point to the sourcemap we
        // just constructed, and queue the map so the parent writes it only if
        // the JS itself is written — identical output rewrites neither file.
        $output[] = '//# sourceMappingURL='.$this->assetsDir->url($mapFile);

        $this->pendingSidecars[$mapFile] = json_encode($map, JSON_UNESCAPED_SLASHES);

        return implode("\n", $output);
    }

    protected function format(string $string): string
    {
        return preg_replace('~//# sourceMappingURL.*$~m', '', $string)."\n";
    }

    protected function delete(string $file): void
    {
        parent::delete($file);

        if ($this->assetsDir->exists($mapFile = $file.'.map')) {
            $this->assetsDir->delete($mapFile);
        }
    }
}
