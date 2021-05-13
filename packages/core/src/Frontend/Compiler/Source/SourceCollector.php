<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Compiler\Source;

/**
 * @internal
 */
class SourceCollector
{
    /**
     * @var SourceInterface[]
     */
    protected $sources = [];

    /**
     * @param string $file
     * @return $this
     */
    public function addFile(string $file)
    {
        $this->sources[] = new FileSource($file);

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
