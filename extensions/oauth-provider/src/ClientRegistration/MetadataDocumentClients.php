<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\ClientRegistration;

use Carbon\Carbon;
use Flarum\Foundation\Config;
use Flarum\OAuthProvider\Models\Client;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\UniqueConstraintViolationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Client ID Metadata Documents (draft-ietf-oauth-client-id-metadata-document):
 * a client_id that is an https URL is fetched, validated and cached as a
 * public client in oauth_provider_clients.
 */
class MetadataDocumentClients
{
    /**
     * Container binding of the Guzzle client used for fetches. It must use the
     * cURL handler: the DNS pin is a cURL option (see the service provider).
     */
    public const string HTTP_CLIENT = 'flarum-oauth-provider.metadata-document.http';

    /**
     * Wall-clock budget in seconds for one resolution, across every redirect hop.
     */
    public const int TIMEOUT = 5;
    public const int MAX_REDIRECTS = 5;
    public const int MAX_BODY_BYTES = 65536;
    public const int DEFAULT_TTL = 3600;
    public const int MIN_TTL = 300;
    public const int MAX_TTL = 86400;
    public const int MAX_CLIENT_ID_LENGTH = 255;

    /**
     * Fetches (each of which may create a client) allowed per address and window.
     */
    public const int RATE_LIMIT = 20;
    public const int RATE_WINDOW = 3600;

    /**
     * Seconds a failed client id is refused without fetching it again.
     */
    public const int FAILURE_TTL = 60;

    /**
     * What callers are told whatever went wrong; the reason is only logged.
     */
    public const string GENERIC_ERROR = 'The client metadata document could not be retrieved or is invalid.';

    /**
     * Prefixes that embed an IPv4 address: NAT64 (RFC 6052, RFC 8215), which a
     * gateway reaches on the caller's behalf, and the deprecated IPv4-compatible
     * form ::/96 (RFC 4291). PHP's global-range filter lets all of them through.
     */
    protected const array NAT64_PREFIXES = ['64:ff9b::/96', '64:ff9b:1::/48', '::/96'];

    public function __construct(
        protected Container $container,
        protected HostResolver $resolver,
        protected Config $config,
        protected Cache $cache,
        protected RegistrationRateLimiter $limiter,
        protected LoggerInterface $logger,
    ) {
    }

    public static function isMetadataDocumentUrl(string $clientId): bool
    {
        if (! str_starts_with($clientId, 'https://') || strlen($clientId) > self::MAX_CLIENT_ID_LENGTH) {
            return false;
        }

        $parts = parse_url($clientId);

        return $parts !== false
            && ! empty($parts['host'])
            && isset($parts['path']) && $parts['path'] !== '/'
            && ! isset($parts['fragment'])
            && ! isset($parts['user']);
    }

    /**
     * Makes sure a usable row exists for a metadata-document client id,
     * fetching the document when there is none or the cached one expired.
     * Other client ids and revoked clients are left alone.
     *
     * Every failure surfaces as the same generic exception; the reason is logged.
     *
     * @param string $ipAddress The requesting address, for rate limiting.
     * @throws ClientMetadataException
     */
    public function resolve(string $clientId, string $ipAddress = ''): void
    {
        if (! self::isMetadataDocumentUrl($clientId)) {
            return;
        }

        /** @var Client|null $client */
        $client = Client::query()->find($clientId);

        if ($client !== null && ($client->source !== Client::SOURCE_METADATA_DOCUMENT || $client->revoked)) {
            return;
        }

        if ($client?->metadata_expires_at?->isFuture()) {
            return;
        }

        $failureKey = 'flarum-oauth-provider.cimd.failed.'.sha1($clientId);

        if ($this->cache->has($failureKey)) {
            throw new ClientMetadataException(self::GENERIC_ERROR, 'invalid_client');
        }

        if ($this->limiter->tooManyAttempts('cimd', $ipAddress, self::RATE_LIMIT, self::RATE_WINDOW)) {
            $this->logger->info('[flarum/oauth-provider] Client metadata document fetch rate limit reached.', ['client_id' => $clientId, 'ip' => $ipAddress]);

            throw new ClientMetadataException(self::GENERIC_ERROR, 'invalid_client');
        }

        try {
            [$document, $ttl] = $this->fetch($clientId);
            $attributes = $this->validate($clientId, $document);
        } catch (ClientMetadataException $e) {
            $this->cache->put($failureKey, true, self::FAILURE_TTL);
            $this->logger->info('[flarum/oauth-provider] Client metadata document refused: '.$e->getMessage(), ['client_id' => $clientId]);

            throw new ClientMetadataException(self::GENERIC_ERROR, 'invalid_client');
        }

        $attributes = [
            ...$attributes,
            'source' => Client::SOURCE_METADATA_DOCUMENT,
            'confidential' => false,
            'secret' => null,
            'metadata_expires_at' => Carbon::now()->addSeconds($ttl),
            'updated_at' => Carbon::now(),
        ];

        if ($client !== null) {
            $client->forceFill($attributes)->save();

            return;
        }

        try {
            (new Client())->forceFill([
                ...$attributes,
                'id' => $clientId,
                'revoked' => false,
                'created_at' => Carbon::now(),
            ])->save();
        } catch (UniqueConstraintViolationException) {
            // A concurrent first request created the row; theirs is as good as ours.
            Client::query()->findOrFail($clientId);
        }
    }

    /**
     * @return array<string, mixed> Client attributes taken from the document.
     * @throws ClientMetadataException
     */
    public function validate(string $clientId, mixed $document): array
    {
        if (! is_array($document) || array_is_list($document)) {
            throw new ClientMetadataException('The client metadata document is not a JSON object.');
        }

        if (($document['client_id'] ?? null) !== $clientId) {
            throw new ClientMetadataException('The client_id in the metadata document does not match its URL.');
        }

        $name = ClientMetadata::name($document['client_name'] ?? null)
            ?? throw new ClientMetadataException('The client metadata document has no client_name.');

        if (array_key_exists('client_secret', $document) || array_key_exists('client_secret_expires_at', $document)) {
            throw new ClientMetadataException('A client metadata document must not contain a client secret.');
        }

        $authMethod = $document['token_endpoint_auth_method'] ?? 'none';

        if ($authMethod !== 'none') {
            throw new ClientMetadataException('Only public clients (token_endpoint_auth_method "none") may use a metadata document.');
        }

        return [
            'name' => $name,
            'redirect_uris' => ClientMetadata::redirectUris($document['redirect_uris'] ?? null),
            'logo_uri' => ClientMetadata::optionalUrl($document['logo_uri'] ?? null),
            'client_uri' => ClientMetadata::optionalUrl($document['client_uri'] ?? null),
        ];
    }

    /**
     * @return array{0: mixed, 1: int} The decoded document and its cache lifetime in seconds.
     * @throws ClientMetadataException
     */
    protected function fetch(string $url): array
    {
        if (! defined('CURLOPT_RESOLVE')) {
            throw new ClientMetadataException('Fetching client metadata documents requires the PHP cURL extension.');
        }

        /** @var HttpClient $http */
        $http = $this->container->make(self::HTTP_CLIENT);
        $deadline = microtime(true) + self::TIMEOUT;

        // Redirects are followed by hand so every hop goes through the SSRF guard.
        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            $pin = $this->guard($url);
            $remaining = $deadline - microtime(true);

            if ($remaining <= 0) {
                throw new ClientMetadataException('Fetching the client metadata document took too long.');
            }

            $body = new CappedBuffer(self::MAX_BODY_BYTES);

            $options = [
                'allow_redirects' => false,
                'http_errors' => false,
                // With the cURL handler, timeout bounds the whole transfer.
                'timeout' => $remaining,
                'connect_timeout' => $remaining,
                // Talk to the vetted address directly, never through a proxy.
                'proxy' => '',
                'headers' => ['Accept' => 'application/json'],
                // Connect to the address that passed the guard (DNS rebinding).
                'curl' => [CURLOPT_RESOLVE => [$pin]],
                'sink' => $body,
                'on_headers' => function (ResponseInterface $response) {
                    $length = $response->getHeaderLine('Content-Length');

                    if ($length !== '' && (int) $length > self::MAX_BODY_BYTES) {
                        throw new ClientMetadataException('The client metadata document is larger than 64 KiB.');
                    }
                },
            ];

            try {
                $response = $http->request('GET', $url, $options);
            } catch (\Throwable $e) {
                $reason = match (true) {
                    $body->exceeded() => 'it is larger than 64 KiB',
                    $e->getPrevious() instanceof ClientMetadataException => $e->getPrevious()->getMessage(),
                    default => $e->getMessage(),
                };

                throw new ClientMetadataException("The client metadata document could not be fetched: $reason");
            }

            if ($body->exceeded()) {
                throw new ClientMetadataException('The client metadata document is larger than 64 KiB.');
            }

            $status = $response->getStatusCode();

            if ($status >= 300 && $status < 400 && $response->hasHeader('Location')) {
                $url = (string) UriResolver::resolve(new Uri($url), new Uri($response->getHeaderLine('Location')));

                continue;
            }

            if ($status !== 200) {
                throw new ClientMetadataException("Fetching the client metadata document returned HTTP $status.");
            }

            return [$this->decode($response, (string) $body), $this->ttl($response)];
        }

        throw new ClientMetadataException('Too many redirects while fetching the client metadata document.');
    }

    /**
     * Refuses URLs that aren't https or resolve to a non-public address, and
     * returns a CURLOPT_RESOLVE entry pinning the host to a checked address.
     *
     * @throws ClientMetadataException
     */
    protected function guard(string $url): string
    {
        $parts = parse_url($url);

        if ($parts === false || strtolower($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
            throw new ClientMetadataException('Client metadata documents must be served over https.');
        }

        $ips = $this->resolver->resolve($parts['host']);

        if ($ips === []) {
            throw new ClientMetadataException('The client metadata document host could not be resolved.');
        }

        foreach ($ips as $ip) {
            if (! $this->isPublicAddress($ip)) {
                throw new ClientMetadataException('The client metadata document host resolves to a non-public address.');
            }
        }

        $ip = str_contains($ips[0], ':') ? "[{$ips[0]}]" : $ips[0];

        return trim($parts['host'], '[]').':'.($parts['port'] ?? 443).':'.$ip;
    }

    protected function isPublicAddress(string $ip): bool
    {
        // IPv4-mapped IPv6 (::ffff:10.0.0.1) is judged by its IPv4 part.
        if (preg_match('/^::ffff:(\d+\.\d+\.\d+\.\d+)$/i', $ip, $matches)) {
            $ip = $matches[1];
        }

        // Debug mode lets a developer serve a document from their own machine.
        if ($this->config->inDebugMode() && ($ip === '::1' || str_starts_with($ip, '127.'))) {
            return true;
        }

        foreach (self::NAT64_PREFIXES as $prefix) {
            if (self::inPrefix($ip, $prefix)) {
                return false;
            }
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE) !== false) {
            return true;
        }

        return false;
    }

    protected static function inPrefix(string $ip, string $cidr): bool
    {
        [$network, $bits] = explode('/', $cidr);
        $ip = @inet_pton($ip);
        $network = @inet_pton($network);

        if ($ip === false || $network === false || strlen($ip) !== strlen($network)) {
            return false;
        }

        $bits = (int) $bits;
        $bytes = intdiv($bits, 8);

        if (substr($ip, 0, $bytes) !== substr($network, 0, $bytes)) {
            return false;
        }

        $rest = $bits % 8;

        if ($rest === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $rest)) & 0xFF;

        return (ord($ip[$bytes]) & $mask) === (ord($network[$bytes]) & $mask);
    }

    /**
     * @throws ClientMetadataException
     */
    protected function decode(ResponseInterface $response, string $contents): mixed
    {
        $contentType = strtolower(trim(explode(';', $response->getHeaderLine('Content-Type'))[0]));

        if ($contentType !== 'application/json') {
            throw new ClientMetadataException('The client metadata document must be served as application/json.');
        }

        try {
            return json_decode($contents, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new ClientMetadataException('The client metadata document is not valid JSON.');
        }
    }

    protected function ttl(ResponseInterface $response): int
    {
        $maxAge = preg_match('/(?:^|,)\s*max-age\s*=\s*"?(\d+)"?/i', $response->getHeaderLine('Cache-Control'), $matches)
            ? (int) $matches[1]
            : self::DEFAULT_TTL;

        return max(self::MIN_TTL, min(self::MAX_TTL, $maxAge));
    }
}
