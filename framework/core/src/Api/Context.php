<?php

namespace Flarum\Api;

use Flarum\Http\RequestUtil;
use Flarum\Search\SearchResults;
use Flarum\User\User;
use Tobyz\JsonApiServer\Context as BaseContext;

class Context extends BaseContext
{
    protected ?SearchResults $search = null;
    protected int|string|null $modelId = null;
    protected array $internal = [];
    protected array $parameters = [];

    public function withModelId(int|string|null $id): static
    {
        $new = clone $this;
        $new->modelId = $id;
        return $new;
    }

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

    public function getModelId(): int|string|null
    {
        return $this->modelId;
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
}
