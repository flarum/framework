<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Tests\integration\forum;

use Flarum\Extend;
use Flarum\OAuthProvider\Models\Client;
use Flarum\OAuthProvider\Tests\integration\InteractsWithOAuth;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

class DynamicRegistrationTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use InteractsWithOAuth;

    public function setUp(): void
    {
        parent::setUp();

        $this->extend(
            (new Extend\Csrf())
                ->exemptRoute('login')
                ->exemptRoute('oauthProvider.authorize')
                ->exemptRoute('oauthProvider.authorize.post')
        );

        $this->extension('flarum-oauth-provider');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
        ]);
    }

    /**
     * Sent without CSRF bypass on purpose: the route is exempt in production.
     *
     * @param array<string, mixed> $metadata
     */
    protected function register(array $metadata, ?string $ip = null): ResponseInterface
    {
        $request = $this->request('POST', '/oauth/register', ['json' => $metadata]);

        if ($ip !== null) {
            $request = new ServerRequest(
                ['REMOTE_ADDR' => $ip],
                [],
                $request->getUri(),
                $request->getMethod(),
                $request->getBody(),
                $request->getHeaders(),
                $request->getCookieParams(),
                $request->getQueryParams(),
                $request->getParsedBody(),
            );
        }

        return $this->send($request);
    }

    /**
     * @param array<string, mixed> $metadata
     */
    protected function registeredClientId(array $metadata): string
    {
        $response = $this->register($metadata);
        $this->assertSame(201, $response->getStatusCode(), (string) $response->getBody());

        return $this->json($response)['client_id'];
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

    #[Test]
    public function ipv6_addresses_are_limited_per_64(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->assertSame(201, $this->register(['redirect_uris' => ['https://app.example.com/cb']], "2001:db8:1:2::$i")->getStatusCode());
        }

        $this->assertSame(429, $this->register(['redirect_uris' => ['https://app.example.com/cb']], '2001:db8:1:2:ffff::1')->getStatusCode());
        $this->assertSame(201, $this->register(['redirect_uris' => ['https://app.example.com/cb']], '2001:db8:1:3::1')->getStatusCode());
    }

    #[Test]
    public function too_many_or_too_long_redirect_uris_are_refused(): void
    {
        $tooMany = array_map(fn ($i) => "https://app.example.com/cb$i", range(1, 11));
        $tooLong = ['https://app.example.com/'.str_repeat('a', 2000)];

        foreach ([$tooMany, $tooLong] as $uris) {
            $response = $this->register(['redirect_uris' => $uris]);

            $this->assertSame(400, $response->getStatusCode());
            $this->assertSame('invalid_redirect_uri', $this->json($response)['error']);
        }

        $this->assertSame(0, Client::query()->count());
    }

    #[Test]
    public function unknown_scopes_are_refused(): void
    {
        foreach (['openid admin', ['openid']] as $scope) {
            $response = $this->register(['redirect_uris' => ['https://app.example.com/cb'], 'scope' => $scope]);

            $this->assertSame(400, $response->getStatusCode());
            $this->assertSame('invalid_client_metadata', $this->json($response)['error']);
        }

        $this->assertSame(0, Client::query()->count());

        $response = $this->register(['redirect_uris' => ['https://app.example.com/cb'], 'scope' => 'openid  profile']);
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('openid profile', $this->json($response)['scope']);
    }

    #[Test]
    public function client_name_loses_control_and_bidi_characters(): void
    {
        $response = $this->register(['client_name' => "Safe\u{202E}evil\u{2066}\x07", 'redirect_uris' => ['https://app.example.com/cb']]);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('Safeevil', $this->json($response)['client_name']);
    }

    #[Test]
    public function tokens_only_carry_the_registered_scopes(): void
    {
        $clientId = $this->registeredClientId([
            'client_name' => 'Scoped',
            'redirect_uris' => ['https://testapp.example.com/callback'],
            'scope' => 'openid',
        ]);

        $tokens = $this->publicClientTokens($clientId, ['scope' => 'openid email']);

        $this->assertSame(['openid'], $this->jwtClaims($tokens['access_token'])['scopes']);
    }

    #[Test]
    public function consent_screen_marks_self_registered_clients_and_shows_the_redirect_host(): void
    {
        $clientId = $this->registeredClientId([
            'client_name' => 'Totally Official App',
            'redirect_uris' => ['https://collector.example.net/callback'],
        ]);

        $response = $this->authorize([
            'client_id' => $clientId,
            'response_type' => 'code',
            'redirect_uri' => 'https://collector.example.net/callback',
            'scope' => 'openid',
            'code_challenge' => $this->challenge(),
            'code_challenge_method' => 'S256',
        ], approve: null);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame('DENY', $response->getHeaderLine('X-Frame-Options'));
        $this->assertSame("frame-ancestors 'none'", $response->getHeaderLine('Content-Security-Policy'));
        $body = (string) $response->getBody();
        $this->assertStringContainsString('unverified, self-registered', $body);
        $this->assertStringContainsString('you will be sent to collector.example.net', $body);
    }
    #[Test]
    public function consent_screen_names_the_redirect_host_not_the_self_chosen_client_uri(): void
    {
        $clientId = $this->registeredClientId([
            'client_name' => 'Trusted App',
            'client_uri' => 'https://trusted.example',
            'redirect_uris' => ['https://collector.example.net/callback', 'https://second.example.org/callback'],
        ]);

        $response = $this->authorize([
            'client_id' => $clientId,
            'response_type' => 'code',
            'redirect_uri' => 'https://second.example.org/callback',
            'scope' => 'openid',
            'code_challenge' => $this->challenge(),
            'code_challenge_method' => 'S256',
        ], approve: null);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $body = (string) $response->getBody();
        $this->assertStringNotContainsString('trusted.example', $body);
        $this->assertStringContainsString('from second.example.org', $body);
        $this->assertStringNotContainsString('collector.example.net', $body);
    }
}
