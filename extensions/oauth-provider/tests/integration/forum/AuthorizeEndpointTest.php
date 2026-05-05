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
use Flarum\OAuthProvider\Models\Client;
use Flarum\OAuthProvider\Models\Consent;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthorizeEndpointTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected const DEFAULT_QUERY = [
        'client_id' => 'test-client',
        'response_type' => 'code',
        'redirect_uri' => 'https://testapp.example.com/callback',
        'scope' => 'openid profile',
        'state' => 'xyz',
    ];

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
     * Build an authorize endpoint request with query params applied to both
     * the URI and the PSR-7 getQueryParams() bag (which Diactoros doesn't
     * populate from the URI string on its own).
     */
    protected function authorizeRequest(string $method, array $query, array $options = []): ServerRequestInterface
    {
        $path = '/oauth/authorize'.($query ? '?'.http_build_query($query) : '');

        return $this->request($method, $path, $options)
            ->withQueryParams($query)
            ->withAttribute('bypassCsrfToken', true);
    }

    #[Test]
    public function missing_client_id_returns_error(): void
    {
        $response = $this->send($this->authorizeRequest('GET', [
            'response_type' => 'code',
            'redirect_uri' => 'https://testapp.example.com/callback',
        ]));

        $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
        $this->assertLessThan(500, $response->getStatusCode());
    }

    #[Test]
    public function unknown_client_id_returns_error(): void
    {
        $response = $this->send($this->authorizeRequest('GET', [
            'client_id' => 'unknown',
            'response_type' => 'code',
            'redirect_uri' => 'https://testapp.example.com/callback',
        ]));

        $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
        $this->assertLessThan(500, $response->getStatusCode());
    }

    #[Test]
    public function guest_is_redirected_to_login(): void
    {
        $response = $this->send($this->authorizeRequest('GET', self::DEFAULT_QUERY));

        $this->assertEquals(302, $response->getStatusCode());
        $location = $response->getHeaderLine('Location');
        $this->assertStringContainsString('oauth_login=1', $location);
        $this->assertStringContainsString('return_to=', $location);
    }

    #[Test]
    public function logged_in_user_sees_consent_screen(): void
    {
        $loginResponse = $this->loginNormalUser();

        $response = $this->send($this->authorizeRequest('GET', self::DEFAULT_QUERY, [
            'cookiesFrom' => $loginResponse,
        ]));

        $this->assertEquals(200, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringContainsString('Test Application', $body);
        $this->assertStringContainsString('oauth_consent_approved', $body);
    }

    #[Test]
    public function approving_consent_redirects_with_auth_code(): void
    {
        $loginResponse = $this->loginNormalUser();

        $response = $this->send($this->authorizeRequest('POST', self::DEFAULT_QUERY, [
            'cookiesFrom' => $loginResponse,
            'json' => ['oauth_consent_approved' => '1'],
        ]));

        $this->assertEquals(302, $response->getStatusCode());
        $location = $response->getHeaderLine('Location');
        $this->assertStringStartsWith('https://testapp.example.com/callback', $location);
        $this->assertStringContainsString('code=', $location);
        $this->assertStringContainsString('state=xyz', $location);
    }

    #[Test]
    public function denying_consent_redirects_with_error(): void
    {
        $loginResponse = $this->loginNormalUser();

        $response = $this->send($this->authorizeRequest('POST', self::DEFAULT_QUERY, [
            'cookiesFrom' => $loginResponse,
            'json' => ['oauth_consent_approved' => '0'],
        ]));

        $this->assertEquals(302, $response->getStatusCode());
        $location = $response->getHeaderLine('Location');
        $this->assertStringStartsWith('https://testapp.example.com/callback', $location);
        $this->assertStringContainsString('error=access_denied', $location);
    }

    #[Test]
    public function approval_persists_consent_record(): void
    {
        $loginResponse = $this->loginNormalUser();

        $this->send($this->authorizeRequest('POST', self::DEFAULT_QUERY, [
            'cookiesFrom' => $loginResponse,
            'json' => ['oauth_consent_approved' => '1'],
        ]));

        /** @var Consent|null $consent */
        $consent = Consent::query()->where('user_id', 2)->where('client_id', 'test-client')->first();
        $this->assertNotNull($consent);
        $this->assertFalse($consent->revoked);
        $this->assertEqualsCanonicalizing(['openid', 'profile'], $consent->scopes);
    }

    #[Test]
    public function denial_does_not_persist_consent_record(): void
    {
        $loginResponse = $this->loginNormalUser();

        $this->send($this->authorizeRequest('POST', self::DEFAULT_QUERY, [
            'cookiesFrom' => $loginResponse,
            'json' => ['oauth_consent_approved' => '0'],
        ]));

        $this->assertEquals(0, Consent::query()->count());
    }

    #[Test]
    public function existing_consent_skips_consent_screen(): void
    {
        $loginResponse = $this->loginNormalUser();

        Consent::query()->create([
            'user_id' => 2,
            'client_id' => 'test-client',
            'scopes' => ['openid', 'profile'],
            'revoked' => false,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $response = $this->send($this->authorizeRequest('GET', self::DEFAULT_QUERY, [
            'cookiesFrom' => $loginResponse,
        ]));

        $this->assertEquals(302, $response->getStatusCode());
        $location = $response->getHeaderLine('Location');
        $this->assertStringStartsWith('https://testapp.example.com/callback', $location);
        $this->assertStringContainsString('code=', $location);
    }

    #[Test]
    public function existing_consent_not_covering_requested_scopes_still_shows_consent_screen(): void
    {
        $loginResponse = $this->loginNormalUser();

        Consent::query()->create([
            'user_id' => 2,
            'client_id' => 'test-client',
            'scopes' => ['openid'],
            'revoked' => false,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $response = $this->send($this->authorizeRequest('GET', [...self::DEFAULT_QUERY, 'scope' => 'openid profile email'], [
            'cookiesFrom' => $loginResponse,
        ]));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Test Application', (string) $response->getBody());
    }

    #[Test]
    public function revoked_consent_still_shows_consent_screen(): void
    {
        $loginResponse = $this->loginNormalUser();

        Consent::query()->create([
            'user_id' => 2,
            'client_id' => 'test-client',
            'scopes' => ['openid', 'profile'],
            'revoked' => true,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $response = $this->send($this->authorizeRequest('GET', self::DEFAULT_QUERY, [
            'cookiesFrom' => $loginResponse,
        ]));

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function prompt_consent_forces_re_prompt_even_when_consent_exists(): void
    {
        $loginResponse = $this->loginNormalUser();

        Consent::query()->create([
            'user_id' => 2,
            'client_id' => 'test-client',
            'scopes' => ['openid', 'profile'],
            'revoked' => false,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $response = $this->send($this->authorizeRequest('GET', [...self::DEFAULT_QUERY, 'prompt' => 'consent'], [
            'cookiesFrom' => $loginResponse,
        ]));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Test Application', (string) $response->getBody());
    }

    #[Test]
    public function second_approval_merges_scopes_into_existing_consent(): void
    {
        $loginResponse = $this->loginNormalUser();

        Consent::query()->create([
            'user_id' => 2,
            'client_id' => 'test-client',
            'scopes' => ['openid'],
            'revoked' => false,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $this->send($this->authorizeRequest('POST', [...self::DEFAULT_QUERY, 'scope' => 'profile email'], [
            'cookiesFrom' => $loginResponse,
            'json' => ['oauth_consent_approved' => '1'],
        ]));

        /** @var Consent $consent */
        $consent = Consent::query()->where('user_id', 2)->where('client_id', 'test-client')->first();
        $this->assertNotNull($consent);
        $this->assertEqualsCanonicalizing(['openid', 'profile', 'email'], $consent->scopes);
        $this->assertEquals(1, Consent::query()->count());
    }

    #[Test]
    public function max_age_zero_forces_re_auth_even_for_logged_in_user(): void
    {
        $loginResponse = $this->loginNormalUser();

        $response = $this->send($this->authorizeRequest('GET', [...self::DEFAULT_QUERY, 'max_age' => '0'], [
            'cookiesFrom' => $loginResponse,
        ]));

        $this->assertEquals(302, $response->getStatusCode());
        $location = $response->getHeaderLine('Location');
        $this->assertStringContainsString('oauth_login=1', $location);
    }

    #[Test]
    public function max_age_larger_than_session_age_is_satisfied(): void
    {
        $loginResponse = $this->loginNormalUser();

        // Session was just created; any generous max_age lets us through.
        $response = $this->send($this->authorizeRequest('GET', [...self::DEFAULT_QUERY, 'max_age' => '3600'], [
            'cookiesFrom' => $loginResponse,
        ]));

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function prompt_login_forces_re_auth_even_for_logged_in_user(): void
    {
        $loginResponse = $this->loginNormalUser();

        $response = $this->send($this->authorizeRequest('GET', [...self::DEFAULT_QUERY, 'prompt' => 'login'], [
            'cookiesFrom' => $loginResponse,
        ]));

        $this->assertEquals(302, $response->getStatusCode());
        $location = $response->getHeaderLine('Location');
        $this->assertStringContainsString('oauth_login=1', $location);
    }

    #[Test]
    public function revoked_client_is_rejected(): void
    {
        $loginResponse = $this->loginNormalUser();

        Client::query()->where('id', 'test-client')->update(['revoked' => true]);

        $response = $this->send($this->authorizeRequest('GET', self::DEFAULT_QUERY, [
            'cookiesFrom' => $loginResponse,
        ]));

        $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
        $this->assertLessThan(500, $response->getStatusCode());
    }
}
