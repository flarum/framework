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
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

class IdTokenTest extends TestCase
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

        $this->assertEquals(200, $response->getStatusCode());

        return $response;
    }

    protected function authorizeRequest(array $query, array $options = []): \Psr\Http\Message\ServerRequestInterface
    {
        $path = '/oauth/authorize?'.http_build_query($query);

        return $this->request('POST', $path, $options)
            ->withQueryParams($query)
            ->withAttribute('bypassCsrfToken', true);
    }

    protected function getAuthCode(string $scope = 'openid profile', ?string $nonce = null): string
    {
        $login = $this->loginNormalUser();

        $query = [
            'client_id' => 'test-client',
            'response_type' => 'code',
            'redirect_uri' => 'https://testapp.example.com/callback',
            'scope' => $scope,
            'state' => 'xyz',
        ];

        if ($nonce !== null) {
            $query['nonce'] = $nonce;
        }

        $resp = $this->send($this->authorizeRequest($query, [
            'cookiesFrom' => $login,
            'json' => ['oauth_consent_approved' => '1'],
        ]));

        $this->assertEquals(302, $resp->getStatusCode());

        parse_str(parse_url($resp->getHeaderLine('Location'), PHP_URL_QUERY) ?? '', $params);

        return $params['code'];
    }

    protected function exchange(string $code): array
    {
        $resp = $this->send(
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

        $this->assertEquals(200, $resp->getStatusCode());
        $body = json_decode((string) $resp->getBody(), true);
        $this->assertIsArray($body);

        return $body;
    }

    protected function decodeJwtPayload(string $jwt): array
    {
        [$_, $payload] = explode('.', $jwt);
        $decoded = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
        $this->assertIsArray($decoded);

        return $decoded;
    }

    #[Test]
    public function id_token_issued_when_openid_scope_granted(): void
    {
        $code = $this->getAuthCode('openid profile email');
        $tokens = $this->exchange($code);

        $this->assertArrayHasKey('id_token', $tokens);
        $this->assertIsString($tokens['id_token']);
        $this->assertEquals(2, substr_count($tokens['id_token'], '.'));
    }

    #[Test]
    public function id_token_contains_required_claims(): void
    {
        $code = $this->getAuthCode('openid');
        $tokens = $this->exchange($code);

        $claims = $this->decodeJwtPayload($tokens['id_token']);

        $this->assertArrayHasKey('iss', $claims);
        $this->assertArrayHasKey('sub', $claims);
        $this->assertArrayHasKey('aud', $claims);
        $this->assertArrayHasKey('exp', $claims);
        $this->assertArrayHasKey('iat', $claims);
        $this->assertArrayHasKey('auth_time', $claims);

        $this->assertEquals('2', $claims['sub']);
        $this->assertEquals('test-client', $claims['aud']);
    }

    #[Test]
    public function id_token_echoes_nonce_from_authorize_request(): void
    {
        $nonce = 'n-0S6_WzA2Mj';
        $code = $this->getAuthCode('openid', $nonce);
        $tokens = $this->exchange($code);

        $claims = $this->decodeJwtPayload($tokens['id_token']);

        $this->assertArrayHasKey('nonce', $claims);
        $this->assertEquals($nonce, $claims['nonce']);
    }

    #[Test]
    public function id_token_omits_nonce_when_not_requested(): void
    {
        $code = $this->getAuthCode('openid');
        $tokens = $this->exchange($code);

        $claims = $this->decodeJwtPayload($tokens['id_token']);

        $this->assertArrayNotHasKey('nonce', $claims);
    }

    #[Test]
    public function id_token_includes_profile_claims_when_profile_scope_granted(): void
    {
        $code = $this->getAuthCode('openid profile');
        $tokens = $this->exchange($code);

        $claims = $this->decodeJwtPayload($tokens['id_token']);

        $this->assertArrayHasKey('name', $claims);
        $this->assertEquals('normal', $claims['name']);
    }

    #[Test]
    public function id_token_includes_email_claims_when_email_scope_granted(): void
    {
        $code = $this->getAuthCode('openid email');
        $tokens = $this->exchange($code);

        $claims = $this->decodeJwtPayload($tokens['id_token']);

        $this->assertArrayHasKey('email', $claims);
        $this->assertEquals('normal@machine.local', $claims['email']);
        $this->assertArrayHasKey('email_verified', $claims);
    }

    #[Test]
    public function no_id_token_when_openid_scope_not_granted(): void
    {
        // Grant only profile — no openid.
        // Most OAuth providers would still accept; we test the non-OIDC path.
        $code = $this->getAuthCode('profile');
        $tokens = $this->exchange($code);

        $this->assertArrayNotHasKey('id_token', $tokens);
    }

    #[Test]
    public function auth_time_reflects_real_session_start_not_now(): void
    {
        // Time window that encloses the login. auth_time in the ID token
        // should fall inside it, not near the authorize/token time.
        $beforeLogin = time();
        $code = $this->getAuthCode('openid');
        $afterLogin = time();

        $tokens = $this->exchange($code);
        $claims = $this->decodeJwtPayload($tokens['id_token']);

        $this->assertArrayHasKey('auth_time', $claims);
        $this->assertGreaterThanOrEqual($beforeLogin - 2, $claims['auth_time']);
        $this->assertLessThanOrEqual($afterLogin + 2, $claims['auth_time']);

        // iat reflects ID token issuance (close to now).
        $this->assertArrayHasKey('iat', $claims);
        $this->assertGreaterThanOrEqual($beforeLogin - 2, $claims['iat']);
    }
}
