<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\forum;

use Carbon\Carbon;
use Flarum\Extend;
use Flarum\Http\AccessToken;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

class LogoutTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        $this->extend(
            (new Extend\Csrf)
                ->exemptRoute('logout')
                ->exemptRoute('logoutPage')
                ->exemptRoute('login')
        );

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    /**
     * Log in as the normal user and return the response.
     * The response cookies carry the PHP session, and X-CSRF-Token carries
     * the session's CSRF token — both usable in follow-up requests.
     */
    private function loginAsNormalUser(): ResponseInterface
    {
        $response = $this->send(
            $this->request('POST', '/login', [
                'json' => [
                    'identification' => 'normal',
                    'password' => 'too-obscure',
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), 'Login must succeed before logout test can run');

        return $response;
    }

    // -------------------------------------------------------------------------
    // POST /logout — actual logout action
    // -------------------------------------------------------------------------

    #[Test]
    public function guest_post_redirects(): void
    {
        $response = $this->send(
            $this->request('POST', '/logout')
        );

        $this->assertEquals(302, $response->getStatusCode());
    }

    #[Test]
    public function post_without_token_redirects_to_confirmation_page(): void
    {
        $loginResponse = $this->loginAsNormalUser();

        $response = $this->send(
            $this->request('POST', '/logout', [
                'cookiesFrom' => $loginResponse,
            ])
        );

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('/logout', $response->getHeaderLine('location'));
    }

    #[Test]
    public function post_with_wrong_token_redirects_to_confirmation_page(): void
    {
        $loginResponse = $this->loginAsNormalUser();

        $response = $this->send(
            $this->request('POST', '/logout', [
                'cookiesFrom' => $loginResponse,
                'json' => ['token' => 'not-the-right-token'],
            ])
        );

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('/logout', $response->getHeaderLine('location'));
    }

    #[Test]
    public function post_with_valid_token_logs_out_and_redirects(): void
    {
        $loginResponse = $this->loginAsNormalUser();
        $csrfToken = $loginResponse->getHeaderLine('X-CSRF-Token');

        $loginData = json_decode((string) $loginResponse->getBody(), true);
        $sessionAccessToken = $loginData['token'];

        $response = $this->send(
            $this->request('POST', '/logout', [
                'cookiesFrom' => $loginResponse,
                'json' => ['token' => $csrfToken],
            ])
        );

        $this->assertEquals(302, $response->getStatusCode());

        // The session access token must have been deleted.
        $this->assertNull(AccessToken::whereToken($sessionAccessToken)->first());
    }

    #[Test]
    public function post_with_valid_token_and_safe_return_url_redirects_there(): void
    {
        $loginResponse = $this->loginAsNormalUser();
        $csrfToken = $loginResponse->getHeaderLine('X-CSRF-Token');

        // Note: the test framework does not parse query strings from the URL path
        // into getQueryParams(), so we set them explicitly here.
        $response = $this->send(
            $this->request('POST', '/logout', [
                'cookiesFrom' => $loginResponse,
                'json' => ['token' => $csrfToken],
            ])->withQueryParams(['return' => 'http://localhost/some-page'])
        );

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('/some-page', $response->getHeaderLine('location'));
    }

    #[Test]
    public function post_with_valid_token_and_external_return_url_redirects_to_base(): void
    {
        $loginResponse = $this->loginAsNormalUser();
        $csrfToken = $loginResponse->getHeaderLine('X-CSRF-Token');

        $response = $this->send(
            $this->request('POST', '/logout', [
                'cookiesFrom' => $loginResponse,
                'json' => ['token' => $csrfToken],
            ])->withQueryParams(['return' => 'https://evil.example.com/phish'])
        );

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringNotContainsString('evil.example.com', $response->getHeaderLine('location'));
    }

    // -------------------------------------------------------------------------
    // GET /logout — confirmation page
    // -------------------------------------------------------------------------

    #[Test]
    public function get_logout_as_guest_redirects(): void
    {
        $response = $this->send(
            $this->request('GET', '/logout')
        );

        $this->assertEquals(302, $response->getStatusCode());
    }

    #[Test]
    public function get_logout_as_user_shows_confirmation_page_with_post_form(): void
    {
        $response = $this->send(
            $this->request('GET', '/logout', ['authenticatedAs' => 2])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = (string) $response->getBody();
        $this->assertStringContainsString('method="POST"', $body);
        $this->assertStringContainsString('name="token"', $body);
    }

    #[Test]
    public function get_logout_does_not_destroy_tokens(): void
    {
        $loginResponse = $this->loginAsNormalUser();
        $loginData = json_decode((string) $loginResponse->getBody(), true);
        $sessionAccessToken = $loginData['token'];

        $this->send(
            $this->request('GET', '/logout', [
                'cookiesFrom' => $loginResponse,
            ])
        );

        // Token must still exist — GET must not log the user out.
        $this->assertNotNull(AccessToken::whereToken($sessionAccessToken)->first());
    }
}
