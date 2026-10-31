<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\ResourceServer;

use Flarum\Http\UrlGenerator;
use Illuminate\Contracts\Container\Container;

/**
 * Protected resources (RFC 8707) that tokens may be audience-restricted to.
 *
 * URLs are resolved on every call: the base URL is only known at runtime, and
 * the registry is a singleton, so nothing is memoized.
 */
class ResourceRegistry
{
    /**
     * Scopes that may always be requested alongside a resource.
     */
    public const array OIDC_SCOPES = ['openid', 'profile', 'email', 'offline_access'];

    /**
     * @var list<array{url: callable(UrlGenerator): string, scopes: list<string>}>
     */
    protected array $resources = [];

    public function __construct(protected Container $container)
    {
    }

    /**
     * @param callable(UrlGenerator): string $url
     * @param list<string> $scopes
     */
    public function register(callable $url, array $scopes): void
    {
        $this->resources[] = ['url' => $url, 'scopes' => array_values($scopes)];
    }

    /**
     * @return array<string, list<string>> Resource URL => scopes
     */
    public function all(): array
    {
        $url = $this->container->make(UrlGenerator::class);
        $resolved = [];

        foreach ($this->resources as $resource) {
            $resolved[(string) ($resource['url'])($url)] = $resource['scopes'];
        }

        return $resolved;
    }

    public function has(string $resource): bool
    {
        return array_key_exists($resource, $this->all());
    }

    /**
     * @return list<string>|null Null when the resource is not registered.
     */
    public function scopesFor(string $resource): ?array
    {
        return $this->all()[$resource] ?? null;
    }

    /**
     * Whether every scope belongs to the resource or is an OIDC scope.
     *
     * @param list<string> $scopes
     */
    public function allowsScopes(string $resource, array $scopes): bool
    {
        $allowed = [...($this->scopesFor($resource) ?? []), ...self::OIDC_SCOPES];

        return array_diff($scopes, $allowed) === [];
    }
}
