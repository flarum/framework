<?php

namespace Flarum\Api\Endpoint\Concerns;

trait HasCustomRoute
{
    protected string $path;

    public function path(string $path): self
    {
        $this->path = $path;

        return $this;
    }
}
