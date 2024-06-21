<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Compiler\Concerns;

use Flarum\Frontend\Compiler\Source\SourceCollector;
use Flarum\Frontend\Compiler\Source\SourceInterface;

/**
 * @template T of SourceInterface
 */
trait HasSources
{
    /**
     * @var callable[]
     */
    protected $sourcesCallbacks = [];

    public function addSources(callable $callback): void
    {
        $this->sourcesCallbacks[] = $callback;
    }

    /**
     * @return T[]
     */
    protected function getSources(): array
    {
        $sources = new SourceCollector($this->allowedSourceTypes());

        foreach ($this->sourcesCallbacks as $callback) {
            $callback($sources);
        }

        return $sources->getSources();
    }

    abstract protected function allowedSourceTypes(): array;
}
