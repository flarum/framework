<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\forum\Auth;

use Flarum\Forum\Auth\ResponseFactory;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\LoginProvider;
use Flarum\User\RegistrationToken;
use Flarum\User\User;
use Laminas\Diactoros\Response\RedirectResponse;
use PHPUnit\Framework\Attributes\Test;

class ResponseFactoryTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            'login_providers' => [
                [
                    'id' => 1,
                    'user_id' => 2,
                    'provider' => 'github',
                    'identifier' => 'gh-user-existing',
                    'created_at' => '2024-01-01 00:00:00',
                ],
            ],
        ]);
    }

    private function factory(): ResponseFactory
    {
        return $this->app()->getContainer()->make(ResponseFactory::class);
    }

    // -------------------------------------------------------------------------
    // Existing provider link → logged-in redirect
    // -------------------------------------------------------------------------

    #[Test]
    public function existing_provider_link_returns_redirect_with_remember_cookie(): void
    {
        $response = $this->factory()->make(
            'github',
            'gh-user-existing',
            fn ($reg) => null,
            '/d/42-some-discussion'
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/d/42-some-discussion', $response->getHeaderLine('Location'));
        $this->assertNotEmpty($response->getHeaderLine('Set-Cookie'));
        $this->assertStringContainsString('remember=', $response->getHeaderLine('Set-Cookie'));
    }

    #[Test]
    public function existing_provider_link_redirects_to_slash_when_no_returnTo(): void
    {
        $response = $this->factory()->make(
            'github',
            'gh-user-existing',
            fn ($reg) => null
        );

        $this->assertEquals('/', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function existing_provider_link_does_not_append_flarum_linked(): void
    {
        // A returning user who already had the provider linked should get a clean
        // redirect with no _flarum_linked param — the modal is only for first-time links.
        $response = $this->factory()->make(
            'github',
            'gh-user-existing',
            fn ($reg) => null,
            '/settings'
        );

        $this->assertStringNotContainsString('_flarum_linked', $response->getHeaderLine('Location'));
    }

    // -------------------------------------------------------------------------
    // Email match → auto-link + logged-in redirect
    // -------------------------------------------------------------------------

    #[Test]
    public function email_match_auto_links_provider_and_redirects(): void
    {
        $response = $this->factory()->make(
            'google',
            'google-new-identifier',
            function ($registration) {
                $registration->provideTrustedEmail('normal@machine.local');
            },
            '/settings'
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/settings?_flarum_linked=google', $response->getHeaderLine('Location'));
        $this->assertStringContainsString('remember=', $response->getHeaderLine('Set-Cookie'));

        // LoginProvider record should have been created
        $this->assertTrue(
            LoginProvider::where('provider', 'google')
                ->where('identifier', 'google-new-identifier')
                ->where('user_id', 2)
                ->exists()
        );
    }

    // -------------------------------------------------------------------------
    // New user → registration redirect with _flarum_auth token
    // -------------------------------------------------------------------------

    #[Test]
    public function new_user_returns_redirect_with_flarum_auth_param(): void
    {
        $response = $this->factory()->make(
            'discord',
            'discord-brand-new',
            function ($registration) {
                $registration->provideTrustedEmail('newuser@example.com');
                $registration->suggestUsername('newuser');
            },
            '/d/42-some-discussion'
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);

        $location = $response->getHeaderLine('Location');
        $this->assertStringStartsWith('/d/42-some-discussion', $location);
        $this->assertStringContainsString('_flarum_auth=', $location);
        $this->assertEmpty($response->getHeaderLine('Set-Cookie'));
    }

    #[Test]
    public function new_user_registration_token_is_persisted(): void
    {
        $this->factory()->make(
            'discord',
            'discord-brand-new-2',
            function ($registration) {
                $registration->provideTrustedEmail('another@example.com');
            },
            '/'
        );

        $location = $this->factory()->make(
            'discord',
            'discord-brand-new-2',
            function ($registration) {
                $registration->provideTrustedEmail('another@example.com');
            },
            '/'
        );

        // Each call generates a fresh token — verify it is saved to the DB
        preg_match('/_flarum_auth=([^&]+)/', $location->getHeaderLine('Location'), $m);
        $token = urldecode($m[1]);

        $this->assertNotEmpty(
            RegistrationToken::find($token),
            'RegistrationToken should be persisted in the database'
        );
    }

    #[Test]
    public function new_user_flarum_auth_param_appended_correctly_when_returnTo_has_query(): void
    {
        $response = $this->factory()->make(
            'discord',
            'discord-brand-new-3',
            fn ($reg) => null,
            '/page?foo=bar'
        );

        $location = $response->getHeaderLine('Location');
        $this->assertStringContainsString('foo=bar', $location);
        $this->assertStringContainsString('&_flarum_auth=', $location);
    }

    #[Test]
    public function suggested_fields_are_stored_in_token_payload(): void
    {
        $response = $this->factory()->make(
            'discord',
            'discord-suggestions',
            function ($registration) {
                $registration->provideTrustedEmail('frank@example.com');
                $registration->suggestUsername('frank99');
            },
            '/'
        );

        preg_match('/_flarum_auth=([^&]+)/', $response->getHeaderLine('Location'), $m);
        $token = RegistrationToken::find(urldecode($m[1]));

        $this->assertNotNull($token);
        // Provided values land in user_attributes
        $this->assertEquals('frank@example.com', $token->user_attributes['email']);
        // Suggested username stored in payload under 'suggested' key
        $this->assertEquals('frank99', $token->payload['suggested']['username']);
    }
}
