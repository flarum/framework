<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Http\Controller;

use Carbon\Carbon;
use Flarum\Http\Exception\RouteNotFoundException;
use Flarum\OAuthProvider\ClientRegistration\ClientMetadata;
use Flarum\OAuthProvider\ClientRegistration\ClientMetadataException;
use Flarum\OAuthProvider\ClientRegistration\DynamicRegistration;
use Flarum\OAuthProvider\ClientRegistration\RegistrationRateLimiter;
use Flarum\OAuthProvider\Models\Client;
use Flarum\OAuthProvider\Scope\ScopeRegistry;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * OAuth 2.0 Dynamic Client Registration (RFC 7591), public clients only.
 * No registration access token is issued, so registrations can't be managed
 * by the client afterwards.
 */
class RegisterClientController implements RequestHandlerInterface
{
    public const int RATE_LIMIT = 10;
    public const int RATE_WINDOW = 3600;

    protected const array GRANT_TYPES = ['authorization_code', 'refresh_token'];
    protected const array RESPONSE_TYPES = ['code'];

    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected RegistrationRateLimiter $limiter,
        protected ScopeRegistry $scopes,
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        if (! DynamicRegistration::enabled($this->settings)) {
            throw new RouteNotFoundException();
        }

        if ($this->limiter->tooManyAttempts('dcr', (string) $request->getAttribute('ipAddress'), self::RATE_LIMIT, self::RATE_WINDOW)) {
            return $this->error('too_many_requests', 'Too many client registrations from this address. Try again later.', 429);
        }

        $metadata = $request->getParsedBody();

        if (! is_array($metadata) || ($metadata !== [] && array_is_list($metadata))) {
            return $this->error('invalid_client_metadata', 'The request body must be a JSON object.');
        }

        try {
            $client = $this->register($metadata);
        } catch (ClientMetadataException $e) {
            return $this->error($e->error, $e->getMessage());
        }

        return new JsonResponse(array_filter([
            'client_id' => $client->id,
            'client_id_issued_at' => $client->created_at?->getTimestamp(),
            'client_name' => $client->name,
            'redirect_uris' => $client->redirect_uris,
            'token_endpoint_auth_method' => 'none',
            'grant_types' => self::GRANT_TYPES,
            'response_types' => self::RESPONSE_TYPES,
            'logo_uri' => $client->logo_uri,
            'client_uri' => $client->client_uri,
            'scope' => $client->scopes ? implode(' ', $client->scopes) : null,
        ], fn ($value) => $value !== null), 201, ['Cache-Control' => 'no-store']);
    }

    /**
     * @param array<string, mixed> $metadata
     * @throws ClientMetadataException
     */
    protected function register(array $metadata): Client
    {
        if (($metadata['token_endpoint_auth_method'] ?? 'none') !== 'none') {
            throw new ClientMetadataException('Only public clients (token_endpoint_auth_method "none") can register.');
        }

        $this->assertSubset($metadata, 'grant_types', self::GRANT_TYPES);
        $this->assertSubset($metadata, 'response_types', self::RESPONSE_TYPES);

        $redirectUris = ClientMetadata::redirectUris($metadata['redirect_uris'] ?? null);
        $clientUri = ClientMetadata::optionalUrl($metadata['client_uri'] ?? null);
        $scopes = ClientMetadata::scopes($metadata['scope'] ?? null, $this->scopes);

        $client = new Client();
        $client->forceFill([
            'id' => Str::random(32),
            'name' => ClientMetadata::name($metadata['client_name'] ?? null)
                ?? ClientMetadata::name(parse_url($clientUri ?? $redirectUris[0], PHP_URL_HOST))
                ?? 'Unnamed client',
            'secret' => null,
            'redirect_uris' => $redirectUris,
            'scopes' => $scopes,
            'confidential' => false,
            'revoked' => false,
            'source' => Client::SOURCE_DYNAMIC,
            'logo_uri' => ClientMetadata::optionalUrl($metadata['logo_uri'] ?? null),
            'client_uri' => $clientUri,
            'created_at' => Carbon::now(),
        ]);
        $client->save();

        return $client;
    }

    /**
     * @param array<string, mixed> $metadata
     * @param list<string> $supported
     * @throws ClientMetadataException
     */
    protected function assertSubset(array $metadata, string $key, array $supported): void
    {
        if (! array_key_exists($key, $metadata)) {
            return;
        }

        $value = $metadata[$key];

        if (! is_array($value) || array_diff(Arr::flatten($value), $supported) !== []) {
            throw new ClientMetadataException("Unsupported $key; supported values: ".implode(', ', $supported).'.');
        }
    }

    protected function error(string $error, string $description, int $status = 400): JsonResponse
    {
        return new JsonResponse(['error' => $error, 'error_description' => $description], $status, ['Cache-Control' => 'no-store']);
    }
}
