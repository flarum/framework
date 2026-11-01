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

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
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
            'redirect_uris_hash' => Consent::hashRedirectUris(['https://testapp.example.com/callback']),
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
            'redirect_uris_hash' => Consent::hashRedirectUris(['https://testapp.example.com/callback']),
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
            'redirect_uris_hash' => Consent::hashRedirectUris(['https://testapp.example.com/callback']),
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
            'redirect_uris_hash' => Consent::hashRedirectUris(['https://testapp.example.com/callback']),
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
    public function prompt_none_returns_login_required_without_redirecting_to_login(): void
    {
        $response = $this->send($this->authorizeRequest('GET', [...self::DEFAULT_QUERY, 'prompt' => 'none']));

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringStartsWith('https://testapp.example.com/callback', $response->getHeaderLine('Location'));
        $this->assertStringContainsString('error=login_required', $response->getHeaderLine('Location'));
        $this->assertStringNotContainsString('oauth_login=1', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function prompt_none_returns_interaction_required_when_consent_is_needed(): void
    {
        $loginResponse = $this->loginNormalUser();

        $response = $this->send($this->authorizeRequest('GET', [...self::DEFAULT_QUERY, 'prompt' => 'none'], [
            'cookiesFrom' => $loginResponse,
        ]));

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringStartsWith('https://testapp.example.com/callback', $response->getHeaderLine('Location'));
        $this->assertStringContainsString('error=interaction_required', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function second_approval_merges_scopes_into_existing_consent(): void
    {
        $loginResponse = $this->loginNormalUser();

        Consent::query()->create([
            'user_id' => 2,
            'client_id' => 'test-client',
            'redirect_uris_hash' => Consent::hashRedirectUris(['https://testapp.example.com/callback']),
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

    protected function loginNormalUserAgain(ResponseInterface $previous): ResponseInterface
    {
        $response = $this->send(
            $this->request('POST', '/login', [
                'json' => [
                    'identification' => 'normal',
                    'password' => 'too-obscure',
                ],
                'cookiesFrom' => $previous,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), 'Failed to log in again');

        return $response;
    }

    /**
     * The authorize query the forum returns to after signing in, as the browser
     * would follow it from the login redirect.
     *
     * @return array<string, string>
     */
    protected function resumeQuery(ResponseInterface $loginRedirect): array
    {
        parse_str((string) parse_url($loginRedirect->getHeaderLine('Location'), PHP_URL_QUERY), $params);
        parse_str((string) parse_url((string) ($params['return_to'] ?? ''), PHP_URL_QUERY), $query);

        $this->assertNotEmpty($query, 'The login redirect carries the authorize URL to return to');

        return $query;
    }

    #[Test]
    public function re_auth_redirect_tells_the_forum_to_show_the_login_form_to_a_signed_in_user(): void
    {
        $loginResponse = $this->loginNormalUser();

        $response = $this->send($this->authorizeRequest('GET', [...self::DEFAULT_QUERY, 'prompt' => 'login'], [
            'cookiesFrom' => $loginResponse,
        ]));

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('oauth_reauth=1', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function prompt_login_is_satisfied_once_the_user_signs_in_again(): void
    {
        $loginResponse = $this->loginNormalUser();

        $asked = $this->send($this->authorizeRequest('GET', [...self::DEFAULT_QUERY, 'prompt' => 'login'], ['cookiesFrom' => $loginResponse]));
        $this->assertEquals(302, $asked->getStatusCode());

        $reLogin = $this->loginNormalUserAgain($loginResponse);

        $response = $this->send($this->authorizeRequest('GET', $this->resumeQuery($asked), ['cookiesFrom' => $reLogin]));

        $this->assertEquals(200, $response->getStatusCode(), 'Signing in again must lead to the consent screen, not another login redirect');
    }

    #[Test]
    public function max_age_zero_is_satisfied_once_the_user_signs_in_again(): void
    {
        $loginResponse = $this->loginNormalUser();

        $asked = $this->send($this->authorizeRequest('GET', [...self::DEFAULT_QUERY, 'max_age' => '0'], ['cookiesFrom' => $loginResponse]));
        $this->assertEquals(302, $asked->getStatusCode());

        $reLogin = $this->loginNormalUserAgain($loginResponse);

        $response = $this->send($this->authorizeRequest('GET', $this->resumeQuery($asked), ['cookiesFrom' => $reLogin]));

        $this->assertEquals(200, $response->getStatusCode(), 'Signing in again must lead to the consent screen, not another login redirect');
    }

    #[Test]
    public function consent_after_re_auth_completes_the_authorization(): void
    {
        $loginResponse = $this->loginNormalUser();

        $asked = $this->send($this->authorizeRequest('GET', [...self::DEFAULT_QUERY, 'prompt' => 'login'], ['cookiesFrom' => $loginResponse]));
        Carbon::setTestNow(Carbon::now()->addSeconds(5));
        $reLogin = $this->loginNormalUserAgain($loginResponse);
        $resume = $this->resumeQuery($asked);

        $consent = $this->send($this->authorizeRequest('GET', $resume, ['cookiesFrom' => $reLogin]));
        $this->assertEquals(200, $consent->getStatusCode());

        // The consent form posts back to the same URL, prompt=login included.
        $approved = $this->send($this->authorizeRequest('POST', $resume, [
            'cookiesFrom' => $reLogin,
            'json' => ['oauth_consent_approved' => '1'],
        ]));

        $this->assertEquals(302, $approved->getStatusCode());
        $this->assertStringStartsWith('https://testapp.example.com/callback', $approved->getHeaderLine('Location'), 'Approving consent must complete the authorization, not ask to sign in again');
        $this->assertStringContainsString('code=', $approved->getHeaderLine('Location'));
    }

    #[Test]
    public function an_abandoned_re_auth_does_not_satisfy_a_later_request(): void
    {
        $loginResponse = $this->loginNormalUser();

        // Asked to sign in again, but the user walks away.
        $this->send($this->authorizeRequest('GET', [...self::DEFAULT_QUERY, 'prompt' => 'login', 'state' => 'first'], ['cookiesFrom' => $loginResponse]));

        // Later they sign in for some other reason.
        Carbon::setTestNow(Carbon::now()->addMinutes(5));
        $reLogin = $this->loginNormalUserAgain($loginResponse);

        // A new request that requires a fresh login must still ask for one.
        $later = $this->send($this->authorizeRequest('GET', [...self::DEFAULT_QUERY, 'prompt' => 'login', 'state' => 'second'], ['cookiesFrom' => $reLogin]));

        $this->assertEquals(302, $later->getStatusCode(), 'An unrelated earlier re-auth request must not satisfy this one');
        $this->assertStringContainsString('oauth_login=1', $later->getHeaderLine('Location'));
    }

    #[Test]
    public function a_login_in_the_same_second_as_the_request_counts(): void
    {
        $loginResponse = $this->loginNormalUser();

        $asked = $this->send($this->authorizeRequest('GET', [...self::DEFAULT_QUERY, 'prompt' => 'login'], ['cookiesFrom' => $loginResponse]));
        $reLogin = $this->loginNormalUserAgain($loginResponse);

        // Pin both logins and the request to one second.
        $now = Carbon::now()->startOfSecond();
        $this->database()->table('access_tokens')->update(['created_at' => $now]);
        $this->database()->table('access_tokens')->update(['last_activity_at' => $now]);

        $response = $this->send($this->authorizeRequest('GET', $this->resumeQuery($asked), ['cookiesFrom' => $reLogin]));

        $this->assertEquals(200, $response->getStatusCode(), 'A fresh login must count even within the same second');
    }

    #[Test]
    public function a_re_auth_ticket_expires(): void
    {
        $loginResponse = $this->loginNormalUser();

        $asked = $this->send($this->authorizeRequest('GET', [...self::DEFAULT_QUERY, 'prompt' => 'login'], ['cookiesFrom' => $loginResponse]));
        $reLogin = $this->loginNormalUserAgain($loginResponse);
        $resume = $this->resumeQuery($asked);

        // Satisfied, but the consent page is left open and abandoned.
        $this->assertEquals(200, $this->send($this->authorizeRequest('GET', $resume, ['cookiesFrom' => $reLogin]))->getStatusCode());

        Carbon::setTestNow(Carbon::now()->addMinutes(30));

        $replayed = $this->send($this->authorizeRequest('GET', $resume, ['cookiesFrom' => $reLogin]));

        $this->assertEquals(302, $replayed->getStatusCode(), 'An old re-auth must not satisfy prompt=login half an hour later');
        $this->assertStringContainsString('oauth_login=1', $replayed->getHeaderLine('Location'));
    }

    #[Test]
    public function a_forged_re_auth_ticket_is_not_accepted(): void
    {
        $loginResponse = $this->loginNormalUser();

        $response = $this->send($this->authorizeRequest('GET', [...self::DEFAULT_QUERY, 'prompt' => 'login', 'oauth_reauth_ticket' => str_repeat('a', 32)], [
            'cookiesFrom' => $loginResponse,
        ]));

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('oauth_login=1', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function a_completed_re_auth_cannot_be_replayed(): void
    {
        $loginResponse = $this->loginNormalUser();

        $asked = $this->send($this->authorizeRequest('GET', [...self::DEFAULT_QUERY, 'prompt' => 'login'], ['cookiesFrom' => $loginResponse]));
        $reLogin = $this->loginNormalUserAgain($loginResponse);
        $resume = $this->resumeQuery($asked);

        $this->send($this->authorizeRequest('POST', $resume, ['cookiesFrom' => $reLogin, 'json' => ['oauth_consent_approved' => '1']]));

        $replayed = $this->send($this->authorizeRequest('GET', $resume, ['cookiesFrom' => $reLogin]));

        $this->assertEquals(302, $replayed->getStatusCode());
        $this->assertStringContainsString('oauth_login=1', $replayed->getHeaderLine('Location'));
    }

    #[Test]
    public function the_resume_url_keeps_the_original_query_encoding(): void
    {
        $loginResponse = $this->loginNormalUser();

        $request = $this->authorizeRequest('GET', [...self::DEFAULT_QUERY, 'prompt' => 'login', 'state' => 'a b'], ['cookiesFrom' => $loginResponse]);
        $request = $request->withUri($request->getUri()->withQuery(str_replace('state=a+b', 'state=a%20b', $request->getUri()->getQuery())));

        $asked = $this->send($request);
        parse_str((string) parse_url($asked->getHeaderLine('Location'), PHP_URL_QUERY), $params);

        $this->assertStringContainsString('state=a%20b', (string) $params['return_to']);
    }

    #[Test]
    public function prompt_login_keeps_asking_until_the_user_signs_in_again(): void
    {
        $query = [...self::DEFAULT_QUERY, 'prompt' => 'login'];
        $loginResponse = $this->loginNormalUser();

        $this->send($this->authorizeRequest('GET', $query, ['cookiesFrom' => $loginResponse]));
        $again = $this->send($this->authorizeRequest('GET', $query, ['cookiesFrom' => $loginResponse]));

        $this->assertEquals(302, $again->getStatusCode());
        $this->assertStringContainsString('oauth_login=1', $again->getHeaderLine('Location'));
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
