<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\External;

use Closure;
use Illuminate\Contracts\Cache\Repository;
use Laminas\Diactoros\Request;

/**
 * @mixin Request
 */
class RequestWrapper
{
    protected Request $request;
    protected array $queryParams = [];

    protected static int $ttl = 300; // 5 minutes

    public function __construct(
        protected Repository $cache,
        string $uri,
        string $method,
        array $headers = [],
    ) {
        $this->request = new Request($uri, $method, 'php://temp', $headers);
    }

    public function withQueryParams(array $queryParams): static
    {
        $this->queryParams = array_merge_recursive($this->queryParams, $queryParams);

        $newUri = $this->request->getUri()->withQuery(http_build_query($this->queryParams));
        $new = $this->request->withUri($newUri);
        $this->request = $new;

        return $this;
    }

    public function __call(string $name, array $arguments): static
    {
        $new = $this->request->$name(...$arguments);
        $this->request = $new;

        return $this;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    protected function cacheKey(): string
    {
        return md5($this->request->getUri()->__toString());
    }

    public function cache(Closure $callback): array
    {
        // We will not cache if there is a search query (filter[q]) in the request.
        if (isset($this->queryParams['filter']['q'])) {
            return $callback($this);
        }

        return $this->cache->remember($this->cacheKey(), static::$ttl, fn () => $callback($this));
    }
}
