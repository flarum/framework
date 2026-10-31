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
use Illuminate\Contracts\Container\Container;
use Psr\Http\Message\ResponseInterface;

/**
 * Client ID Metadata Documents (draft-ietf-oauth-client-id-metadata-document):
 * a client_id that is an https URL is fetched, validated and cached as a
 * public client in oauth_provider_clients.
 */
class MetadataDocumentClients
{
    public const int TIMEOUT = 5;
    public const int MAX_REDIRECTS = 5;
    public const int MAX_BODY_BYTES = 65536;
    public const int DEFAULT_TTL = 3600;
    public const int MIN_TTL = 300;
    public const int MAX_TTL = 86400;
    public const int MAX_CLIENT_ID_LENGTH = 255;

    public function __construct(
        protected Container $container,
        protected HostResolver $resolver,
        protected Config $config,
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
     * @throws ClientMetadataException
     */
    public function resolve(string $clientId): void
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

        [$document, $ttl] = $this->fetch($clientId);
        $attributes = $this->validate($clientId, $document);

        $client ??= new Client(['id' => $clientId, 'revoked' => false, 'created_at' => Carbon::now()]);
        $client->forceFill([
            ...$attributes,
            'source' => Client::SOURCE_METADATA_DOCUMENT,
            'confidential' => false,
            'secret' => null,
            'metadata_expires_at' => Carbon::now()->addSeconds($ttl),
            'updated_at' => Carbon::now(),
        ]);
        $client->save();
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
        $http = $this->container->make(HttpClient::class);

        // Redirects are followed by hand so every hop goes through the SSRF guard.
        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            $options = [
                'allow_redirects' => false,
                'http_errors' => false,
                'timeout' => self::TIMEOUT,
                'connect_timeout' => self::TIMEOUT,
                'stream' => true,
                'headers' => ['Accept' => 'application/json'],
            ];

            $pin = $this->guard($url);

            // Pin the connection to the address that passed the guard (DNS rebinding).
            if (defined('CURLOPT_RESOLVE')) {
                $options['curl'] = [CURLOPT_RESOLVE => [$pin]];
            }

            try {
                $response = $http->request('GET', $url, $options);
            } catch (\Throwable) {
                throw new ClientMetadataException('The client metadata document could not be fetched.');
            }

            $status = $response->getStatusCode();

            if ($status >= 300 && $status < 400 && $response->hasHeader('Location')) {
                $url = (string) UriResolver::resolve(new Uri($url), new Uri($response->getHeaderLine('Location')));

                continue;
            }

            if ($status !== 200) {
                throw new ClientMetadataException("Fetching the client metadata document returned HTTP $status.");
            }

            return [$this->decode($response), $this->ttl($response)];
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

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE) !== false) {
            return true;
        }

        // Debug mode lets a developer serve a document from their own machine.
        return $this->config->inDebugMode()
            && ($ip === '::1' || str_starts_with($ip, '127.'));
    }

    /**
     * @throws ClientMetadataException
     */
    protected function decode(ResponseInterface $response): mixed
    {
        $contentType = strtolower(trim(explode(';', $response->getHeaderLine('Content-Type'))[0]));

        if ($contentType !== 'application/json') {
            throw new ClientMetadataException('The client metadata document must be served as application/json.');
        }

        $body = $response->getBody();
        $contents = '';

        while (! $body->eof() && strlen($contents) <= self::MAX_BODY_BYTES) {
            $contents .= $body->read(8192);
        }

        if (strlen($contents) > self::MAX_BODY_BYTES) {
            throw new ClientMetadataException('The client metadata document is larger than 64 KiB.');
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
