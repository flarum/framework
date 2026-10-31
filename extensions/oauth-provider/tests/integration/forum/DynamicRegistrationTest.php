<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Tests\integration\forum;

use Flarum\OAuthProvider\Models\Client;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

class DynamicRegistrationTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-oauth-provider');
    }

    /**
     * Sent without CSRF bypass on purpose: the route is exempt in production.
     *
     * @param array<string, mixed> $metadata
     */
    protected function register(array $metadata): ResponseInterface
    {
        return $this->send($this->request('POST', '/oauth/register', ['json' => $metadata]));
    }

    #[Test]
    public function public_client_registers(): void
    {
        $response = $this->register([
            'client_name' => 'Claude',
            'redirect_uris' => ['https://claude.ai/api/mcp/auth_callback', 'http://127.0.0.1:33418/'],
            'token_endpoint_auth_method' => 'none',
            'grant_types' => ['authorization_code', 'refresh_token'],
            'response_types' => ['code'],
            'logo_uri' => 'https://claude.ai/logo.png',
            'client_uri' => 'https://claude.ai',
        ]);

        $this->assertSame(201, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame('no-store', $response->getHeaderLine('Cache-Control'));

        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame('Claude', $body['client_name']);
        $this->assertSame('none', $body['token_endpoint_auth_method']);
        $this->assertArrayNotHasKey('client_secret', $body);
        $this->assertArrayNotHasKey('registration_access_token', $body);
        $this->assertIsInt($body['client_id_issued_at']);

        /** @var Client $client */
        $client = Client::query()->find($body['client_id']);
        $this->assertSame(Client::SOURCE_DYNAMIC, $client->source);
        $this->assertFalse($client->confidential);
        $this->assertNull($client->secret);
        $this->assertSame(['https://claude.ai/api/mcp/auth_callback', 'http://127.0.0.1:33418/'], $client->redirect_uris);
        $this->assertSame('https://claude.ai/logo.png', $client->logo_uri);
    }

    #[Test]
    public function registration_returns_404_when_disabled(): void
    {
        $this->setting('flarum-oauth-provider.allow_dynamic_registration', '0');

        $response = $this->register(['redirect_uris' => ['https://app.example.com/cb']]);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(0, Client::query()->count());
    }

    #[Test]
    public function non_https_redirect_uri_is_refused(): void
    {
        foreach (['http://app.example.com/cb', 'myapp://callback', 'https://app.example.com/cb#fragment'] as $uri) {
            $response = $this->register(['client_name' => 'App', 'redirect_uris' => [$uri]]);

            $this->assertSame(400, $response->getStatusCode(), $uri);
            $this->assertSame('invalid_redirect_uri', json_decode((string) $response->getBody(), true)['error']);
        }

        $this->assertSame(0, Client::query()->count());
    }

    #[Test]
    public function confidential_client_is_refused(): void
    {
        $response = $this->register([
            'redirect_uris' => ['https://app.example.com/cb'],
            'token_endpoint_auth_method' => 'client_secret_basic',
        ]);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('invalid_client_metadata', json_decode((string) $response->getBody(), true)['error']);
    }

    #[Test]
    public function unsupported_grant_type_is_refused(): void
    {
        $response = $this->register([
            'redirect_uris' => ['https://app.example.com/cb'],
            'grant_types' => ['client_credentials'],
        ]);

        $this->assertSame(400, $response->getStatusCode());
    }

    #[Test]
    public function registrations_are_rate_limited_per_ip(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->assertSame(201, $this->register(['redirect_uris' => ['https://app.example.com/cb']])->getStatusCode());
        }

        $response = $this->register(['redirect_uris' => ['https://app.example.com/cb']]);

        $this->assertSame(429, $response->getStatusCode());
        $this->assertSame(10, Client::query()->count());
    }
}
