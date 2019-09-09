<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Install;

use Psr\Http\Message\UriInterface;

final class BaseUrl
{
    /** @var UriInterface|string */
    private $baseUrl;

    /**
     * @param UriInterface|string $baseUrl
     */
    private function __construct($baseUrl)
    {
        $this->baseUrl = $this->normalise($baseUrl);
    }

    /**
     * @param string $baseUrl
     * @return \Flarum\Install\BaseUrl
     */
    public static function fromString(string $baseUrl): self
    {
        return new self($baseUrl);
    }

    /**
     * @param \Psr\Http\Message\UriInterface $baseUrl
     * @return \Flarum\Install\BaseUrl
     */
    public static function fromUri(UriInterface $baseUrl): self
    {
        return self::fromString((string) $baseUrl);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->baseUrl;
    }

    /**
     * @param UriInterface|string $baseUrl
     * @return string
     */
    private function normalise($baseUrl): string
    {
        // Empty base url is still valid
        if (empty($baseUrl)) {
            return '';
        }

        $normalisedBaseUrl = trim($baseUrl, '/');
        if (! preg_match('#^https?://#i', $normalisedBaseUrl)) {
            $normalisedBaseUrl = sprintf('http://%s', $normalisedBaseUrl);
        }

        $parseUrl = parse_url($normalisedBaseUrl);

        $path = $parseUrl['path'] ?? null;
        if (isset($parseUrl['path']) && strrpos($parseUrl['path'], '.php') !== false) {
            $path = substr($parseUrl['path'], 0, strrpos($parseUrl['path'], '/'));
        }

        return rtrim(
            sprintf('%s://%s%s', $parseUrl['scheme'], $parseUrl['host'], $path),
            '/'
        );
    }
}
