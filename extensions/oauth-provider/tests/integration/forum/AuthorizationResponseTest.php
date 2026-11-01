<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Tests\integration\forum;

use Flarum\Extend;
use Flarum\OAuthProvider\Tests\integration\InteractsWithOAuth;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * RFC 9207 `iss`, S256-only PKCE (RFC 7636) and loopback redirect URIs (RFC 8252 §7.3).
 */
class AuthorizationResponseTest extends TestCase
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
            'oauth_provider_clients' => [
                $this->clientRow('public-client', false),
                $this->clientRow('confidential-client', true),
                $this->clientRow('loopback-public', false, ['http://127.0.0.1/callback', 'http://localhost:8080/cb', 'http://[::1]/cb']),
                $this->clientRow('loopback-confidential', true, ['http://127.0.0.1/callback']),
            ],
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function query(string $clientId, array $overrides = []): array
    {
        return [
            'client_id' => $clientId,
            'response_type' => 'code',
            'redirect_uri' => 'https://testapp.example.com/callback',
            'scope' => 'openid',
            'state' => 'xyz',
            'code_challenge' => $this->challenge(),
            'code_challenge_method' => 'S256',
            ...$overrides,
        ];
    }

    #[Test]
    public function approval_redirect_carries_the_issuer(): void
    {
        $params = $this->redirectParams($this->authorize($this->query('public-client')));

        $this->assertArrayHasKey('code', $params);
        $this->assertSame('xyz', $params['state']);
        $this->assertSame('http://localhost', $params['iss']);
    }

    #[Test]
    public function denial_redirect_carries_the_issuer(): void
    {
        $params = $this->redirectParams($this->authorize($this->query('public-client'), approve: false));

        $this->assertSame('access_denied', $params['error']);
        $this->assertSame('http://localhost', $params['iss']);
    }

    #[Test]
    public function plain_pkce_is_refused(): void
    {
        $params = $this->redirectParams($this->authorize($this->query('public-client', [
            'code_challenge' => self::VERIFIER,
            'code_challenge_method' => 'plain',
        ])));

        $this->assertSame('invalid_request', $params['error']);
        $this->assertSame('xyz', $params['state']);
        $this->assertSame('http://localhost', $params['iss']);
        $this->assertArrayNotHasKey('code', $params);
    }

    #[Test]
    public function pkce_without_method_defaults_to_plain_and_is_refused(): void
    {
        $query = $this->query('confidential-client', ['code_challenge' => self::VERIFIER]);
        unset($query['code_challenge_method']);

        $this->assertSame('invalid_request', $this->redirectParams($this->authorize($query))['error']);
    }

    #[Test]
    public function public_client_without_pkce_is_refused(): void
    {
        $query = $this->query('public-client');
        unset($query['code_challenge'], $query['code_challenge_method']);

        $response = $this->authorize($query);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('invalid_request', $this->json($response)['error']);
    }

    #[Test]
    public function confidential_client_may_omit_pkce(): void
    {
        $query = $this->query('confidential-client');
        unset($query['code_challenge'], $query['code_challenge_method']);

        $this->assertArrayHasKey('code', $this->redirectParams($this->authorize($query)));
    }

    #[Test]
    public function public_client_s256_round_trip_issues_tokens(): void
    {
        $tokens = $this->publicClientTokens('public-client');

        $this->assertArrayHasKey('access_token', $tokens);
        $this->assertArrayHasKey('refresh_token', $tokens);
    }

    #[Test]
    public function public_client_loopback_redirect_matches_any_port(): void
    {
        foreach (['http://127.0.0.1:49152/callback', 'http://localhost:3000/cb', 'http://[::1]:5000/cb'] as $uri) {
            $response = $this->authorize($this->query('loopback-public', ['redirect_uri' => $uri]));

            $this->assertStringStartsWith($uri.'?code=', $response->getHeaderLine('Location'), $uri);
        }

        $tokens = $this->publicClientTokens('loopback-public', ['redirect_uri' => 'http://127.0.0.1:49152/callback']);
        $this->assertArrayHasKey('access_token', $tokens);
    }

    #[Test]
    public function loopback_match_still_requires_the_same_path_and_scheme(): void
    {
        foreach (['http://127.0.0.1:49152/other', 'https://127.0.0.1:49152/callback', 'http://127.0.0.2:49152/callback'] as $uri) {
            $response = $this->authorize($this->query('loopback-public', ['redirect_uri' => $uri]));

            $this->assertSame(401, $response->getStatusCode(), $uri);
            $this->assertSame('invalid_client', $this->json($response)['error']);
        }
    }

    #[Test]
    public function confidential_client_loopback_redirect_must_match_exactly(): void
    {
        $query = $this->query('loopback-confidential', ['redirect_uri' => 'http://127.0.0.1:49152/callback']);
        unset($query['code_challenge'], $query['code_challenge_method']);

        $this->assertSame(401, $this->authorize($query)->getStatusCode());
    }
}
