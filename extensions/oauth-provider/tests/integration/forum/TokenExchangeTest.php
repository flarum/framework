<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Tests\integration\forum;

use Carbon\Carbon;
use Flarum\Extend;
use Flarum\OAuthProvider\Models\AccessToken;
use Flarum\OAuthProvider\Models\RefreshToken;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

class TokenExchangeTest extends TestCase
{
    use RetrievesAuthorizedUsers;

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
            User::class => [
                $this->normalUser(),
            ],
            'oauth_provider_clients' => [
                [
                    'id' => 'test-client',
                    'name' => 'Test Application',
                    'secret' => hash('sha256', 'test-secret'),
                    'redirect_uris' => json_encode(['https://testapp.example.com/callback']),
                    'scopes' => json_encode(['openid', 'profile', 'email']),
                    'confidential' => 1,
                    'revoked' => 0,
                    'created_at' => Carbon::now()->toDateTimeString(),
                ],
            ],
        ]);
    }

    protected function loginNormalUser(): ResponseInterface
    {
        $response = $this->send(
            $this->request('POST', '/login', [
                'json' => [
                    'identification' => 'normal',
                    'password' => 'too-obscure',
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), 'Failed to log in');

        return $response;
    }

    /**
     * Drive the authorize endpoint to produce an auth code, then return it.
     */
    protected function getAuthorizationCode(array $overrides = []): string
    {
        $loginResponse = $this->loginNormalUser();

        $query = array_merge([
            'client_id' => 'test-client',
            'response_type' => 'code',
            'redirect_uri' => 'https://testapp.example.com/callback',
            'scope' => 'openid profile',
            'state' => 'xyz',
        ], $overrides);

        $response = $this->send(
            $this->request('POST', '/oauth/authorize?'.http_build_query($query), [
                'cookiesFrom' => $loginResponse,
                'json' => [
                    'oauth_consent_approved' => '1',
                ],
            ])
                ->withQueryParams($query)
                ->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(302, $response->getStatusCode());

        $location = $response->getHeaderLine('Location');
        parse_str(parse_url($location, PHP_URL_QUERY) ?? '', $params);

        $this->assertArrayHasKey('code', $params);

        return $params['code'];
    }

    #[Test]
    public function valid_auth_code_exchanges_for_access_token(): void
    {
        $code = $this->getAuthorizationCode();

        $response = $this->send(
            $this->request('POST', '/oauth/token', [
                'json' => [
                    'grant_type' => 'authorization_code',
                    'client_id' => 'test-client',
                    'client_secret' => 'test-secret',
                    'redirect_uri' => 'https://testapp.example.com/callback',
                    'code' => $code,
                ],
            ])->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($body);
        $this->assertArrayHasKey('access_token', $body);
        $this->assertArrayHasKey('token_type', $body);
        $this->assertEquals('Bearer', $body['token_type']);
        $this->assertArrayHasKey('expires_in', $body);
        $this->assertArrayHasKey('refresh_token', $body);
        $this->assertIsString($body['access_token']);
        $this->assertNotEmpty($body['access_token']);

        $this->assertGreaterThan(0, AccessToken::query()->count());
        $this->assertGreaterThan(0, RefreshToken::query()->count());
    }

    #[Test]
    public function wrong_client_secret_is_rejected(): void
    {
        $code = $this->getAuthorizationCode();

        $response = $this->send(
            $this->request('POST', '/oauth/token', [
                'json' => [
                    'grant_type' => 'authorization_code',
                    'client_id' => 'test-client',
                    'client_secret' => 'wrong-secret',
                    'redirect_uri' => 'https://testapp.example.com/callback',
                    'code' => $code,
                ],
            ])->withAttribute('bypassCsrfToken', true)
        );

        $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
        $this->assertLessThan(500, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($body);
        $this->assertArrayHasKey('error', $body);
    }

    #[Test]
    public function auth_code_cannot_be_reused(): void
    {
        $code = $this->getAuthorizationCode();

        $first = $this->send(
            $this->request('POST', '/oauth/token', [
                'json' => [
                    'grant_type' => 'authorization_code',
                    'client_id' => 'test-client',
                    'client_secret' => 'test-secret',
                    'redirect_uri' => 'https://testapp.example.com/callback',
                    'code' => $code,
                ],
            ])->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(200, $first->getStatusCode());

        $second = $this->send(
            $this->request('POST', '/oauth/token', [
                'json' => [
                    'grant_type' => 'authorization_code',
                    'client_id' => 'test-client',
                    'client_secret' => 'test-secret',
                    'redirect_uri' => 'https://testapp.example.com/callback',
                    'code' => $code,
                ],
            ])->withAttribute('bypassCsrfToken', true)
        );

        $this->assertGreaterThanOrEqual(400, $second->getStatusCode());
    }

    #[Test]
    public function refresh_token_returns_new_access_token(): void
    {
        $code = $this->getAuthorizationCode();

        $first = $this->send(
            $this->request('POST', '/oauth/token', [
                'json' => [
                    'grant_type' => 'authorization_code',
                    'client_id' => 'test-client',
                    'client_secret' => 'test-secret',
                    'redirect_uri' => 'https://testapp.example.com/callback',
                    'code' => $code,
                ],
            ])->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(200, $first->getStatusCode());
        $tokens = json_decode((string) $first->getBody(), true);
        $this->assertIsArray($tokens);
        $this->assertArrayHasKey('refresh_token', $tokens);

        $refreshResponse = $this->send(
            $this->request('POST', '/oauth/token', [
                'json' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $tokens['refresh_token'],
                    'client_id' => 'test-client',
                    'client_secret' => 'test-secret',
                    'scope' => 'openid profile',
                ],
            ])->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(200, $refreshResponse->getStatusCode());
        $refreshed = json_decode((string) $refreshResponse->getBody(), true);
        $this->assertIsArray($refreshed);

        $this->assertArrayHasKey('access_token', $refreshed);
        $this->assertArrayHasKey('refresh_token', $refreshed);
        $this->assertNotEquals($tokens['access_token'], $refreshed['access_token']);
    }

    #[Test]
    public function userinfo_returns_profile_for_valid_access_token(): void
    {
        $code = $this->getAuthorizationCode(['scope' => 'openid profile email']);

        $first = $this->send(
            $this->request('POST', '/oauth/token', [
                'json' => [
                    'grant_type' => 'authorization_code',
                    'client_id' => 'test-client',
                    'client_secret' => 'test-secret',
                    'redirect_uri' => 'https://testapp.example.com/callback',
                    'code' => $code,
                ],
            ])->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(200, $first->getStatusCode());
        $tokens = json_decode((string) $first->getBody(), true);
        $this->assertIsArray($tokens);
        $this->assertArrayHasKey('access_token', $tokens);

        $userinfo = $this->send(
            $this->request('GET', '/oauth/userinfo')
                ->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
                ->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(200, $userinfo->getStatusCode());

        $payload = json_decode($userinfo->getBody()->getContents(), true);

        $this->assertEquals('2', $payload['sub']);
        $this->assertEquals('normal', $payload['name']);
        $this->assertArrayHasKey('email', $payload);
        $this->assertEquals('normal@machine.local', $payload['email']);
    }

    #[Test]
    public function userinfo_without_token_is_rejected(): void
    {
        $response = $this->send(
            $this->request('GET', '/oauth/userinfo')
                ->withAttribute('bypassCsrfToken', true)
        );

        $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
        $this->assertLessThan(500, $response->getStatusCode());
    }
}
