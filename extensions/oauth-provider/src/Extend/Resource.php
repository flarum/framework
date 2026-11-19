<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Extend;

use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use Flarum\Http\UrlGenerator;
use Flarum\OAuthProvider\ResourceServer\ResourceRegistry;
use Illuminate\Contracts\Container\Container;

/**
 * Registers a protected resource (RFC 8707) that clients may request tokens for.
 *
 *   new Resource(fn (UrlGenerator $url) => $url->to('forum')->route('mcp'), scopes: ['mcp:read', 'mcp:write'])
 *
 * The URL is a callable because the forum's base URL is only known at runtime.
 */
class Resource implements ExtenderInterface
{
    /**
     * @var callable(UrlGenerator): string
     */
    protected $url;

    /**
     * @param callable(UrlGenerator): string $url Returns the absolute resource URL.
     * @param list<string> $scopes Scopes that belong to the resource.
     */
    public function __construct(callable $url, protected array $scopes)
    {
        $this->url = $url;
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
        $container->extend(ResourceRegistry::class, function (ResourceRegistry $registry) {
            $registry->register($this->url, $this->scopes);

            return $registry;
        });
    }
}
