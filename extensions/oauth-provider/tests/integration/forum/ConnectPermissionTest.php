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
use Flarum\Group\Group;
use Flarum\Http\UrlGenerator;
use Flarum\OAuthProvider\Access\MemberAccess;
use Flarum\OAuthProvider\Extend\Resource;
use Flarum\OAuthProvider\Extend\Scope;
use Flarum\OAuthProvider\ResourceServer\TokenValidator;
use Flarum\OAuthProvider\Tests\integration\InteractsWithOAuth;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

/**
 * Connecting an application to an account takes the `oauthProvider.connect`
 * permission, which members have by default. Suspended members, and
 * anonymised ones (their email is no longer confirmed), hold only guest
 * permissions: they can't connect, and what they connected stops working
 * until they may again.
 */
class ConnectPermissionTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use InteractsWithOAuth;

    protected const string RESOURCE = 'http://localhost/mcp';

    public function setUp(): void
    {
        parent::setUp();

        $this->extend(
            (new Extend\Csrf())
                ->exemptRoute('login')
                ->exemptRoute('oauthProvider.authorize')
                ->exemptRoute('oauthProvider.authorize.post'),
            new Scope('mcp:read', 'Read the forum'),
            new Resource(fn (UrlGenerator $url) => $url->to('forum')->base().'/mcp', ['mcp:read']),
        );

        $this->extension('flarum-suspend', 'flarum-oauth-provider');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            'oauth_provider_clients' => [$this->clientRow('public-client', false)],
        ]);
    }

    protected function authorizeClient(): ResponseInterface
    {
        return $this->authorize([
            'client_id' => 'public-client',
            'response_type' => 'code',
            'redirect_uri' => 'https://testapp.example.com/callback',
            'scope' => 'openid',
            'state' => 'xyz',
            'code_challenge' => $this->challenge(),
            'code_challenge_method' => 'S256',
        ]);
    }

    protected function suspend(bool $suspended = true): void
    {
        User::query()->whereKey(2)->update(['suspended_until' => $suspended ? Carbon::now()->addDay() : null]);
    }

    protected function userinfo(string $accessToken): ResponseInterface
    {
        return $this->send($this->request('GET', '/oauth/userinfo')->withHeader('Authorization', 'Bearer '.$accessToken));
    }

    protected function validator(): TokenValidator
    {
        return $this->app()->getContainer()->make(TokenValidator::class);
    }

    #[Test]
    public function members_may_connect_applications(): void
    {
        $this->assertTrue(
            $this->database()->table('group_permission')->where('permission', MemberAccess::PERMISSION)->where('group_id', Group::MEMBER_ID)->exists()
        );

        $this->assertNotEmpty($this->publicClientCode('public-client'));
    }

    #[Test]
    public function a_member_without_the_permission_cannot_authorize(): void
    {
        $this->database()->table('group_permission')->where('permission', MemberAccess::PERMISSION)->delete();

        $this->assertSame('access_denied', $this->redirectParams($this->authorizeClient())['error'] ?? null);
    }

    #[Test]
    public function a_suspended_member_cannot_authorize(): void
    {
        $login = $this->loginNormalUser();

        $this->suspend();

        $this->assertSame('access_denied', $this->redirectParams($this->authorize([
            'client_id' => 'public-client',
            'response_type' => 'code',
            'redirect_uri' => 'https://testapp.example.com/callback',
            'scope' => 'openid',
            'state' => 'xyz',
            'code_challenge' => $this->challenge(),
            'code_challenge_method' => 'S256',
        ], $login))['error'] ?? null);
    }

    #[Test]
    public function a_code_cannot_be_exchanged_once_its_member_is_suspended(): void
    {
        $code = $this->publicClientCode('public-client');

        $this->suspend();

        $response = $this->exchangeCode('public-client', $code);
        $this->assertSame(400, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function a_suspended_members_tokens_stop_working_until_the_suspension_ends(): void
    {
        $tokens = $this->publicClientTokens('public-client');

        $this->suspend();

        $this->assertSame(401, $this->userinfo($tokens['access_token'])->getStatusCode());
        $refreshed = $this->refresh('public-client', $tokens['refresh_token']);
        // As for any revoked refresh token.
        $this->assertSame(401, $refreshed->getStatusCode(), (string) $refreshed->getBody());

        $this->suspend(false);

        $this->assertSame(200, $this->userinfo($tokens['access_token'])->getStatusCode());
        $this->assertSame(200, $this->refresh('public-client', $tokens['refresh_token'])->getStatusCode());
    }

    #[Test]
    public function a_suspended_members_tokens_are_refused_by_resource_servers(): void
    {
        $jwt = $this->publicClientTokens('public-client', ['scope' => 'openid mcp:read', 'resource' => self::RESOURCE])['access_token'];

        $this->suspend();

        $this->assertNull($this->validator()->validate($jwt, self::RESOURCE));

        $this->suspend(false);

        $this->assertNotNull($this->validator()->validate($jwt, self::RESOURCE));
    }

    #[Test]
    public function tokens_stop_working_for_a_member_who_is_no_longer_confirmed_as_anonymised_members_are(): void
    {
        $tokens = $this->publicClientTokens('public-client');

        User::query()->whereKey(2)->update(['is_email_confirmed' => false]);

        $this->assertSame(401, $this->userinfo($tokens['access_token'])->getStatusCode());
        $this->assertSame(401, $this->refresh('public-client', $tokens['refresh_token'])->getStatusCode());
    }
}
