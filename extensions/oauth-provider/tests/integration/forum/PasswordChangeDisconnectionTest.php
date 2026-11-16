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
use Flarum\Http\AccessToken as SessionToken;
use Flarum\OAuthProvider\Access\MemberAccess;
use Flarum\OAuthProvider\Models\AccessToken;
use Flarum\OAuthProvider\Models\AuthCode;
use Flarum\OAuthProvider\Models\Consent;
use Flarum\OAuthProvider\Models\RefreshToken;
use Flarum\OAuthProvider\Server\Repository\RefreshTokenRepository;
use Flarum\OAuthProvider\Tests\integration\InteractsWithOAuth;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\Event\PasswordChanged;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Changing a password is the other thing a member does when they fear for
 * their account: like logging out everywhere, it must disconnect the
 * applications they connected, so a token obtained while the old password
 * leaked stops working.
 */
class PasswordChangeDisconnectionTest extends TestCase
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
            User::class => [
                $this->normalUser(),
                [
                    'id' => 3,
                    'username' => 'bystander',
                    'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', // "too-obscure"
                    'email' => 'bystander@machine.local',
                    'is_email_confirmed' => 1,
                ],
            ],
            'oauth_provider_clients' => [$this->clientRow('public-client', false)],
            // A different member's connection to the same client, which must survive.
            'oauth_provider_user_consents' => [
                ['id' => 1, 'user_id' => 3, 'client_id' => 'public-client', 'revoked' => 0, 'created_at' => Carbon::now()->toDateTimeString()],
            ],
            'oauth_provider_access_tokens' => [
                ['id' => 'at-bystander', 'client_id' => 'public-client', 'user_id' => 3, 'grant_id' => 'grant-bystander', 'revoked' => 0, 'expires_at' => Carbon::now()->addDay()->toDateTimeString(), 'created_at' => Carbon::now()->toDateTimeString()],
            ],
            'oauth_provider_refresh_tokens' => [
                ['id' => 'rt-bystander', 'access_token_id' => 'at-bystander', 'grant_id' => 'grant-bystander', 'revoked' => 0, 'expires_at' => Carbon::now()->addDay()->toDateTimeString(), 'created_at' => Carbon::now()->toDateTimeString()],
            ],
            'oauth_provider_auth_codes' => [
                ['id' => 'grant-bystander', 'client_id' => 'public-client', 'user_id' => 3, 'revoked' => 0, 'family_revoked' => 0, 'expires_at' => Carbon::now()->addDay()->toDateTimeString(), 'created_at' => Carbon::now()->toDateTimeString()],
            ],
        ]);
    }

    #[Test]
    public function changing_password_disconnects_the_members_applications(): void
    {
        $tokens = $this->publicClientTokens('public-client');

        // The application works while the password stands.
        $this->assertSame(200, $this->send($this->request('GET', '/oauth/userinfo')->withHeader('Authorization', 'Bearer '.$tokens['access_token']))->getStatusCode());

        // An admin changes the member's password through the API, which is one
        // of the two paths core raises PasswordChanged from (the other being
        // the forum's password-reset flow).
        $response = $this->send(
            $this->request('PATCH', '/api/users/2', ['authenticatedAs' => 1])
                ->withParsedBody(['data' => ['type' => 'users', 'id' => '2', 'attributes' => ['password' => 'a-brand-new-password']]])
        );
        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());

        $this->assertSame(0, Consent::query()->where('user_id', 2)->count());
        $this->assertSame(401, $this->send($this->request('GET', '/oauth/userinfo')->withHeader('Authorization', 'Bearer '.$tokens['access_token']))->getStatusCode());

        // The refresh token is refused too, and the client is told its grant
        // was revoked, not that it used the token twice.
        $refreshed = $this->refresh('public-client', $tokens['refresh_token']);
        $this->assertSame(400, $refreshed->getStatusCode(), (string) $refreshed->getBody());
        $this->assertSame('invalid_grant', $this->json($refreshed)['error']);
        $this->assertSame('This grant has been revoked.', $this->json($refreshed)['hint']);

        // Another member's connection to the same client is untouched: their
        // consent and their tokens all survive.
        $this->assertSame(1, Consent::query()->where('user_id', 3)->count());
        $this->assertFalse((bool) AccessToken::query()->whereKey('at-bystander')->value('revoked'));
        $this->assertFalse((bool) RefreshToken::query()->whereKey('rt-bystander')->value('revoked'));
        $this->assertFalse((bool) AuthCode::query()->whereKey('grant-bystander')->value('family_revoked'));
    }

    #[Test]
    public function changing_password_revokes_the_token_family_against_an_in_flight_grant(): void
    {
        // A real grant leaves an auth code behind; the tokens a refresh or code
        // exchange issues descend from it (grant_id).
        $this->publicClientTokens('public-client');

        $grantId = AuthCode::query()->where('user_id', 2)->value('id');
        $this->assertNotNull($grantId, 'the authorize flow should leave an auth code');
        $this->assertFalse((bool) AuthCode::query()->whereKey($grantId)->value('family_revoked'));

        $response = $this->send(
            $this->request('PATCH', '/api/users/2', ['authenticatedAs' => 1])
                ->withParsedBody(['data' => ['type' => 'users', 'id' => '2', 'attributes' => ['password' => 'a-brand-new-password']]])
        );
        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());

        // Marking the family revoked is what closes the race: a grant that was
        // mid-flight when the password changed stores its new tokens into this
        // family, and assertFamilyNotRevoked() then revokes them and fails the
        // request (a_refresh_racing_a_disconnect_leaves_no_working_token below).
        // Without the flag, a token minted in that window would survive.
        $this->assertTrue((bool) AuthCode::query()->whereKey($grantId)->value('family_revoked'));

        // The bystander's family is left alone.
        $this->assertFalse((bool) AuthCode::query()->whereKey('grant-bystander')->value('family_revoked'));
    }

    #[Test]
    public function a_refresh_racing_a_disconnect_leaves_no_working_token(): void
    {
        $tokens = $this->publicClientTokens('public-client');

        // The member is disconnected (password change / logout everywhere) at
        // the worst moment: after the old refresh token is consumed but before
        // the new tokens are stored. The family must still be caught, or the
        // tokens minted in that window would outlive the disconnect.
        $this->app()->getContainer()->instance(RefreshTokenRepository::class, new class(function () {
            Consent::revokeAllFor(2);
        }, $this->access()) extends RefreshTokenRepository {
            public function __construct(protected ?\Closure $afterConsume, MemberAccess $access)
            {
                parent::__construct($access);
            }

            public function consumeRefreshToken(string $tokenId): bool
            {
                $consumed = parent::consumeRefreshToken($tokenId);
                $hook = $this->afterConsume;
                $this->afterConsume = null;
                $hook && $hook();

                return $consumed;
            }
        });

        $response = $this->refresh('public-client', $tokens['refresh_token']);

        $this->assertSame(400, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame('invalid_grant', $this->json($response)['error']);

        // The disconnected member has no valid token left; only the bystander's
        // seeded tokens (user 3) remain, which proves the sweep stayed scoped.
        $this->assertSame(1, AccessToken::query()->where('revoked', false)->count(), 'only the bystander token may stay valid: '.$response->getBody());
        $this->assertSame(1, RefreshToken::query()->where('revoked', false)->count());
        $this->assertFalse((bool) AccessToken::query()->whereKey('at-bystander')->value('revoked'));
    }

    #[Test]
    public function the_session_ends_before_applications_are_disconnected(): void
    {
        // AuthorizeController relies on this order: a session that is still
        // there after a code was stored means the disconnect has yet to run.
        // A listener added through Extend\Event, as this extension's is, runs
        // after core has deleted the member's access tokens. (It also relies
        // on the password change not running inside a database transaction,
        // so a concurrent request sees the deletion as soon as it happens.)
        $sessionsSeen = null;

        $this->extend(
            (new Extend\Event())->listen(PasswordChanged::class, function (PasswordChanged $event) use (&$sessionsSeen) {
                $sessionsSeen = SessionToken::query()->where('user_id', $event->user->id)->count();
            })
        );

        $this->loginNormalUser();
        $this->assertGreaterThan(0, SessionToken::query()->where('user_id', 2)->count());

        $this->changePassword();

        $this->assertSame(0, $sessionsSeen);
    }

    protected function changePassword(): void
    {
        $response = $this->send(
            $this->request('PATCH', '/api/users/2', ['authenticatedAs' => 1])
                ->withParsedBody(['data' => ['type' => 'users', 'id' => '2', 'attributes' => ['password' => 'a-brand-new-password']]])
        );

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
    }

    protected function access(): MemberAccess
    {
        return $this->app()->getContainer()->make(MemberAccess::class);
    }
}
