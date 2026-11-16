<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Tests\integration\forum;

use Flarum\Extend;
use Flarum\OAuthProvider\Models\Consent;
use Flarum\OAuthProvider\Tests\integration\InteractsWithOAuth;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\Event\LoggedOut;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\Attributes\Test;

/**
 * Logging out everywhere is what a member does when they fear for their
 * account: it disconnects the applications they connected, as it revokes
 * their developer tokens. Logging out of one browser leaves them be.
 */
class GlobalLogoutTest extends TestCase
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
                ->exemptRoute('oauthProvider.authorize.post'),
        );

        $this->extension('flarum-oauth-provider');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            'oauth_provider_clients' => [$this->clientRow('public-client', false)],
        ]);
    }

    #[Test]
    public function logging_out_everywhere_disconnects_applications(): void
    {
        $tokens = $this->publicClientTokens('public-client');

        $response = $this->send(
            $this->request('POST', '/global-logout', ['authenticatedAs' => 2])->withAttribute('bypassCsrfToken', true)
        );
        $this->assertSame(204, $response->getStatusCode(), (string) $response->getBody());

        $this->assertSame(0, Consent::query()->where('user_id', 2)->count());
        $this->assertSame(401, $this->send($this->request('GET', '/oauth/userinfo')->withHeader('Authorization', 'Bearer '.$tokens['access_token']))->getStatusCode());
        // A revoked refresh token presented again is refused as a reuse.
        $refreshed = $this->refresh('public-client', $tokens['refresh_token']);
        $this->assertSame(400, $refreshed->getStatusCode(), (string) $refreshed->getBody());
        $this->assertSame('invalid_grant', $this->json($refreshed)['error']);
    }

    #[Test]
    public function logging_out_of_one_browser_leaves_applications_connected(): void
    {
        $tokens = $this->publicClientTokens('public-client');

        $this->app()->getContainer()->make(Dispatcher::class)->dispatch(new LoggedOut(User::query()->findOrFail(2), false));

        $this->assertSame(1, Consent::query()->where('user_id', 2)->count());
        $this->assertSame(200, $this->send($this->request('GET', '/oauth/userinfo')->withHeader('Authorization', 'Bearer '.$tokens['access_token']))->getStatusCode());
    }
}
