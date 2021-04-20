<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Compiler\Source;

class SourceCollector
{
    /**
     * @var SourceInterface[]
     */
    protected $sources = [];

    /**
     * @param string $file
     * @param string|null $moduleName
     * @return $this
     */
    public function addFile(string $file, string $moduleName = null)
    {
        $this->sources[] = new FileSource($file, $moduleName);

        return $this;
    }

    public function addDirectory(string $directory, string $moduleName = null)
    {
        $this->sources[] = new FolderSource($directory, $moduleName);

        return $this;
    }

    /**
     * @param callable $callback
     * @return $this
     */
    public function addString(callable $callback)
    {
        $this->sources[] = new StringSource($callback);

        return $this;
    }

    /**
     * @return SourceInterface[]
     */
    public function getSources()
    {
        return $this->sources;
    }
}
