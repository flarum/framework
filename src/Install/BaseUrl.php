<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install;

use Psr\Http\Message\UriInterface;

final class BaseUrl
{
    /** @var string */
    private $normalized;

    private function __construct(string $baseUrl)
    {
        $this->normalized = $this->normalize($baseUrl);
    }

    public static function fromString(string $baseUrl): self
    {
        return new self($baseUrl);
    }

    public static function fromUri(UriInterface $baseUrl): self
    {
        return new self((string) $baseUrl);
    }

    public function __toString(): string
    {
        return $this->normalized;
    }

    /**
     * Generate a valid e-mail address for this base URL's domain.
     *
     * This uses the given mailbox name and our already normalized host name to
     * construct an email address.
     *
     * @param string $mailbox
     * @return string
     */
    public function toEmail(string $mailbox): string
    {
        $host = preg_replace('/^www\./i', '', parse_url($this->normalized, PHP_URL_HOST));

        return "$mailbox@$host";
    }

    private function normalize(string $baseUrl): string
    {
        // Empty base url is still valid
        if (empty($baseUrl)) {
            return '';
        }

        $normalizedBaseUrl = trim($baseUrl, '/');
        if (! preg_match('#^https?://#i', $normalizedBaseUrl)) {
            $normalizedBaseUrl = sprintf('http://%s', $normalizedBaseUrl);
        }

        $parseUrl = parse_url($normalizedBaseUrl);

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
