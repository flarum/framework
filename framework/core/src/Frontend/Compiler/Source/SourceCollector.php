<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Compiler\Source;

use Closure;

/**
 * @internal
 */
class SourceCollector
{
    /**
     * @var SourceInterface[]
     */
    protected array $sources = [];

    public function addFile(string $file, string $extensionId = null): static
    {
        $this->sources[] = new FileSource($file, $extensionId);

        return $this;
    }

    public function addString(Closure $callback): static
    {
        $this->sources[] = new StringSource($callback);

        return $this;
    }

    /**
     * @return SourceInterface[]
     */
    public function getSources(): array
    {
        return $this->sources;
    }
}
