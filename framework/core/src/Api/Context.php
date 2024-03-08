<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api;

use Flarum\Http\RequestUtil;
use Flarum\Search\SearchResults;
use Flarum\User\User;
use Tobyz\JsonApiServer\Context as BaseContext;

class Context extends BaseContext
{
    protected ?SearchResults $search = null;

    /**
     * Data passed internally when reusing resource endpoint logic.
     */
    protected array $internal = [];

    /**
     * Parameters mutated on the current instance.
     * Useful for passing information between different field callbacks.
     */
    protected array $parameters = [];

    public function withSearchResults(SearchResults $search): static
    {
        $new = clone $this;
        $new->search = $search;

        return $new;
    }

    public function withInternal(string $key, mixed $value): static
    {
        $new = clone $this;
        $new->internal[$key] = $value;

        return $new;
    }

    public function getSearchResults(): ?SearchResults
    {
        return $this->search;
    }

    public function internal(string $key, mixed $default = null): mixed
    {
        return $this->internal[$key] ?? $default;
    }

    public function getActor(): User
    {
        return RequestUtil::getActor($this->request);
    }

    public function setParam(string $key, mixed $default = null): static
    {
        $this->parameters[$key] = $default;

        return $this;
    }

    public function getParam(string $key, mixed $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }

    public function creating(string|null $resource = null): bool
    {
        return $this->endpoint instanceof Endpoint\Create && (! $resource || is_a($this->collection, $resource));
    }

    public function updating(string|null $resource = null): bool
    {
        return $this->endpoint instanceof Endpoint\Update && (! $resource || is_a($this->collection, $resource));
    }

    public function deleting(string|null $resource = null): bool
    {
        return $this->endpoint instanceof Endpoint\Delete && (! $resource || is_a($this->collection, $resource));
    }

    public function showing(string|null $resource = null): bool
    {
        return $this->endpoint instanceof Endpoint\Show && (! $resource || is_a($this->collection, $resource));
    }

    public function listing(string|null $resource = null): bool
    {
        return $this->endpoint instanceof Endpoint\Index && (! $resource || is_a($this->collection, $resource));
    }
}
