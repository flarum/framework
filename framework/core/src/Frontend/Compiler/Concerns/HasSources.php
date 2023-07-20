<?php

namespace Flarum\Frontend\Compiler\Concerns;

use Flarum\Frontend\Compiler\Source\SourceCollector;
use Flarum\Frontend\Compiler\Source\SourceInterface;

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
     * @return SourceInterface[]
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