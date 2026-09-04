<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Flarum\Foundation\Config;
use Laminas\Diactoros\Uri;

/**
 * Validates caller-supplied URLs before Flarum redirects a browser to them.
 *
 * Two contracts, because the two places core accepts one differ in shape: an
 * absolute URL checked against `redirectDomains` ({@see validate()}), and a
 * same-origin relative path ({@see validatePath()}).
 */
class ReturnUrlValidator
{
    public function __construct(
        protected Config $config
    ) {
    }

    /**
     * Hosts an absolute return URL is permitted to point at.
     *
     * @return list<string>
     */
    public function allowedHosts(): array
    {
        return array_values(array_merge(
            [$this->config->url()->getHost()],
            $this->config->offsetGet('redirectDomains') ?? []
        ));
    }

    /**
     * Validate an absolute return URL, or null if it is unusable.
     *
     * Relative paths are rejected here: they have no host to check, so they are
     * indistinguishable from a protocol-relative URL pointing off-site once a
     * browser resolves them. Use {@see validatePath()} for those.
     */
    public function validate(?string $url): ?Uri
    {
        if (empty($url)) {
            return null;
        }

        try {
            $uri = new Uri($url);
        } catch (\InvalidArgumentException) {
            return null;
        }

        if (! in_array($uri->getHost(), $this->allowedHosts(), true)) {
            return null;
        }

        return $uri;
    }

    /**
     * Validate an absolute return URL, falling back to the forum's base URL.
     */
    public function sanitize(?string $url, ?string $fallback = null): Uri
    {
        return $this->validate($url)
            ?? new Uri($fallback ?? (string) $this->config->url());
    }

    /**
     * Validate a same-origin relative path, falling back to `/`.
     *
     * Control characters are rejected alongside off-origin references, since the
     * path reaches the browser inside a `Location` header.
     */
    public function validatePath(?string $path, string $fallback = '/'): string
    {
        if (empty($path)) {
            return $fallback;
        }

        if (! str_starts_with($path, '/')
            || str_starts_with($path, '//')
            || str_contains($path, '://')
            || preg_match('/[\x00-\x1F\x7F]/', $path) === 1
        ) {
            return $fallback;
        }

        return $path;
    }
}
