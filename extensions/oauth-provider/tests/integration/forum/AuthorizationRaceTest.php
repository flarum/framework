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
use Flarum\OAuthProvider\Event\AuthorizationWithdrawn;
use Flarum\OAuthProvider\Models\AccessToken;
use Flarum\OAuthProvider\Models\AuthCode;
use Flarum\OAuthProvider\Models\Consent;
use Flarum\OAuthProvider\Server\Repository\AuthCodeRepository;
use Flarum\OAuthProvider\Tests\integration\InteractsWithOAuth;
use Flarum\OAuthProvider\Tests\integration\SignsInWithoutSession;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Events\TransactionRolledBack;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

/**
 * An authorization that is cut off while it runs, by a password change, a
 * logout or a disconnect, must not hand out a code, nor leave a connection
 * the member can't see in their settings.
 */
class AuthorizationRaceTest extends TestCase
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
    public function an_authorization_racing_a_password_change_hands_out_no_code(): void
    {
        // The member has already connected the client, so authorizing it again
        // needs no prompt: an attacker holding their session can do it at will.
        $this->publicClientTokens('public-client');

        // The password changes after this request passed its session and
        // consent checks, but before it stores the new code.
        $this->beforeTheNextCodeIsStored(fn () => $this->changePassword());

        $this->assertDisconnected($this->authorize($this->codeQuery(), approve: null), 'login_required');
    }

    #[Test]
    public function an_authorization_racing_a_global_logout_hands_out_no_code(): void
    {
        $this->publicClientTokens('public-client');

        $this->beforeTheNextCodeIsStored(function () {
            $response = $this->send($this->request('POST', '/global-logout', ['authenticatedAs' => 2])->withAttribute('bypassCsrfToken', true));
            $this->assertSame(204, $response->getStatusCode(), (string) $response->getBody());
        });

        $this->assertDisconnected($this->authorize($this->codeQuery(), approve: null), 'login_required');
    }

    #[Test]
    public function an_authorization_racing_a_disconnect_from_settings_hands_out_no_code(): void
    {
        $this->publicClientTokens('public-client');

        // The member disconnects the client in another tab: their session
        // stays, so only the consent being gone can tell the request.
        $this->beforeTheNextCodeIsStored(function () {
            $id = Consent::query()->where('user_id', 2)->value('id');
            $response = $this->send($this->request('DELETE', "/api/oauth-consents/$id", ['authenticatedAs' => 2]));
            $this->assertSame(204, $response->getStatusCode(), (string) $response->getBody());
        });

        $this->assertDisconnected($this->authorize($this->codeQuery(), approve: null), 'access_denied');
    }

    #[Test]
    public function an_authorization_racing_disconnect_all_hands_out_no_code(): void
    {
        $this->publicClientTokens('public-client');

        $this->beforeTheNextCodeIsStored(function () {
            $response = $this->send($this->request('DELETE', '/api/oauth-consents', ['authenticatedAs' => 2]));
            $this->assertSame(204, $response->getStatusCode(), (string) $response->getBody());
        });

        $this->assertDisconnected($this->authorize($this->codeQuery(), approve: null), 'access_denied');
    }

    #[Test]
    public function a_consent_racing_a_password_change_is_not_written_back(): void
    {
        // The password changes just before the consent form's approval is
        // recorded, so the consent row is written after the revocation.
        $this->onNextConsentSave(fn () => $this->changePassword());

        $this->assertDisconnected($this->authorize($this->codeQuery()), 'login_required');
    }

    #[Test]
    public function an_authorization_whose_session_ends_leaves_an_existing_connection_alone(): void
    {
        $tokens = $this->publicClientTokens('public-client');

        // Only the browser session ends (as on logging out of one browser): no
        // password change, no disconnect. The new authorization is refused,
        // but the connection the member already had must survive it.
        $this->onNextConsentSave(fn () => SessionToken::query()->where('user_id', 2)->delete());

        $this->assertRefused($this->authorize($this->codeQuery()), 'login_required');
        $this->assertSame(1, Consent::query()->where('user_id', 2)->count());
        $this->assertSame(200, $this->send($this->request('GET', '/oauth/userinfo')->withHeader('Authorization', 'Bearer '.$tokens['access_token']))->getStatusCode());
    }

    #[Test]
    public function an_authorization_without_the_consent_form_keeps_no_approval_when_withdrawn(): void
    {
        $this->publicClientTokens('public-client');

        $withdrawn = [];
        $this->app()->getContainer()->make(Dispatcher::class)->listen(AuthorizationWithdrawn::class, function (AuthorizationWithdrawn $event) use (&$withdrawn) {
            $withdrawn[] = $event->approvalKept;
        });

        // An earlier approval covers the request, so the member approves
        // nothing now; their session ends while it runs.
        $this->beforeTheNextCodeIsStored(fn () => SessionToken::query()->where('user_id', 2)->delete());

        $this->assertRefused($this->authorize($this->codeQuery(), approve: null), 'login_required');
        $this->assertSame([false], $withdrawn);
    }

    #[Test]
    public function a_refused_authorization_keeps_a_consent_another_code_relies_on(): void
    {
        // An approved code the client has yet to exchange: the consent behind
        // it is the only trace of the connection so far.
        $firstCode = $this->publicClientCode('public-client');

        // A second approval, asking for more, has its session end as the
        // approval is recorded.
        $this->onNextConsentSave(fn () => SessionToken::query()->where('user_id', 2)->delete());

        $this->assertRefused($this->authorize(['scope' => 'openid profile'] + $this->codeQuery()), 'login_required');

        // The consent predates the refused request, so it stays as that request
        // merged it: narrowing it again could leave a code that relied on the
        // wider scopes holding more than the member's settings show.
        $this->assertSame([['openid', 'profile']], Consent::query()->where('user_id', 2)->get()->pluck('scopes')->all());

        // It still stands behind the first code, which keeps working.
        $this->assertSame(200, $this->exchangeCode('public-client', $firstCode)->getStatusCode());
    }

    #[Test]
    public function a_refused_authorization_takes_down_a_code_that_relied_on_the_consent_it_created(): void
    {
        // Browser A signs in and approves the client for the first time.
        $loginA = $this->loginNormalUser();
        $sessionA = SessionToken::query()->where('user_id', 2)->orderByDesc('id')->value('token');

        // As soon as A's approval is recorded, browser B authorizes the client
        // on the strength of it, and only then is browser A logged out.
        $codeB = null;
        $this->app();
        Consent::created(function () use (&$codeB, $sessionA) {
            if ($codeB !== null) {
                return;
            }

            $codeB = false;
            $codeB = $this->redirectParams($this->authorize($this->codeQuery(), approve: null))['code'] ?? false;
            SessionToken::query()->where('token', $sessionA)->delete();
        });

        $responseA = $this->authorize($this->codeQuery(), $loginA);

        $this->assertIsString($codeB, 'browser B should have been handed a code');

        // No code may work without a consent the member can see and revoke.
        $this->assertSame(0, Consent::query()->where('user_id', 2)->count());
        $this->assertSame(400, $this->exchangeCode('public-client', $codeB)->getStatusCode());
        $this->assertRefused($responseA, 'login_required');
    }

    #[Test]
    public function two_first_approvals_at_once_both_connect_the_application(): void
    {
        $this->anotherTabApprovesFirst(['openid', 'email']);

        // The insert runs in a savepoint, so the conflict leaves the
        // surrounding transaction usable (PostgreSQL aborts it otherwise, and
        // the tests always run in one).
        $levels = [];
        Consent::creating(function () use (&$levels) {
            $levels[] = $this->db()->transactionLevel();
        });
        $outer = $this->db()->transactionLevel();

        $response = $this->authorize(['scope' => 'openid profile'] + $this->codeQuery());

        // This approval merges into the other tab's.
        $this->assertArrayHasKey('code', $this->redirectParams($response));
        $consents = Consent::query()->where('user_id', 2)->get();
        $this->assertCount(1, $consents);
        $this->assertEqualsCanonicalizing(['openid', 'email', 'profile'], $consents[0]->scopes);
        $this->assertSame([$outer + 1], $levels);
    }

    #[Test]
    public function a_refused_approval_merged_into_another_tabs_leaves_that_connection(): void
    {
        $this->anotherTabApprovesFirst(['openid', 'email']);

        // This request's session ends after it merged into the other tab's
        // consent: refusing it must not take that consent down.
        $this->beforeTheNextCodeIsStored(fn () => SessionToken::query()->where('user_id', 2)->delete());

        $this->assertRefused($this->authorize(['scope' => 'openid profile'] + $this->codeQuery()), 'login_required');

        $consents = Consent::query()->where('user_id', 2)->get();
        $this->assertCount(1, $consents);
        $this->assertEqualsCanonicalizing(['openid', 'email', 'profile'], $consents[0]->scopes);
    }

    #[Test]
    public function an_approval_whose_racing_consent_is_gone_again_hands_out_no_code(): void
    {
        $this->anotherTabApprovesFirst(['openid']);

        // The other tab's consent is disconnected again before this request,
        // whose own insert failed on it, can merge into it.
        $pending = true;
        $this->db()->getEventDispatcher()->listen(TransactionRolledBack::class, function () use (&$pending) {
            if ($pending) {
                $pending = false;
                Consent::query()->where('user_id', 2)->delete();
            }
        });

        $this->assertDisconnected($this->authorize($this->codeQuery()), 'access_denied');
    }

    #[Test]
    public function an_approval_replaced_by_a_narrower_one_hands_out_no_code(): void
    {
        $this->publicClientTokens('public-client');

        // As this approval for more is recorded, the member disconnects the
        // client and approves it again for less in other tabs: the consent
        // that stands doesn't cover what this request would hand out.
        $this->onNextConsentSave(function () {
            Consent::query()->where('user_id', 2)->delete();
            Consent::query()->insert(['user_id' => 2, 'client_id' => 'public-client', 'scopes' => json_encode(['openid']), 'redirect_uris_hash' => Consent::hashRedirectUris(['https://testapp.example.com/callback']), 'revoked' => false, 'created_at' => Carbon::now()]);
        });

        $this->assertRefused($this->authorize(['scope' => 'openid profile'] + $this->codeQuery()), 'access_denied');
        $this->assertSame([['openid']], Consent::query()->where('user_id', 2)->get()->pluck('scopes')->all());
    }

    #[Test]
    public function a_member_signed_in_without_a_core_session_can_still_authorize(): void
    {
        // Another extension's authentication (single sign-on, say) can sign a
        // member in without a core session; there is then no session to end.
        $this->extend((new Extend\Middleware('forum'))->add(SignsInWithoutSession::class));

        $query = $this->codeQuery();
        $response = $this->send(
            $this->request('POST', '/oauth/authorize?'.http_build_query($query), ['json' => ['oauth_consent_approved' => '1']])
                ->withQueryParams($query)
                ->withHeader('X-Test-Actor', '2')
                ->withAttribute('bypassCsrfToken', true)
        );

        $this->assertArrayHasKey('code', $this->redirectParams($response));
    }

    #[Test]
    public function a_disconnect_revokes_codes_and_tokens_on_both_sides_of_deleting_the_consent(): void
    {
        // AuthorizeController relies on this order: an authorization that still
        // finds the consent once its code is stored can count on the disconnect
        // revoking that code afterwards. A grant issuing tokens counts on its
        // family being marked before the tokens are revoked. And revoking
        // before the consent goes too means a disconnect that fails halfway
        // leaves the connection listed, to be disconnected again.
        $this->publicClientTokens('public-client');

        /** @var ConnectionInterface $db */
        $db = $this->app()->getContainer()->make(ConnectionInterface::class);
        $db->enableQueryLog();
        $db->flushQueryLog();

        Consent::revokeAllFor(2);

        $statements = array_map(function (string $query): string {
            preg_match('/^(update|delete from)\s+\W?\w*?(oauth_provider_\w+)/i', $query, $matches);

            return $matches ? strtolower($matches[1]).' '.$matches[2] : 'other';
        }, array_column($db->getQueryLog(), 'query'));

        $statements = array_values(array_filter($statements, fn (string $statement) => $statement !== 'other'));
        $pass = [
            'update oauth_provider_auth_codes',
            'update oauth_provider_refresh_tokens',
            'update oauth_provider_access_tokens',
        ];

        $this->assertSame([...$pass, 'delete from oauth_provider_user_consents', ...$pass], $statements);
    }

    /**
     * The authorization was cut off by a disconnect: no code reached the
     * client, and the member has neither a usable code, a live token nor a
     * consent for the client.
     */
    protected function assertDisconnected(ResponseInterface $response, string $error): void
    {
        $this->assertRefused($response, $error);
        $this->assertSame(0, AuthCode::query()->where('user_id', 2)->where('family_revoked', false)->count(), 'every code the member holds must be unusable');
        $this->assertSame(0, AccessToken::query()->where('user_id', 2)->where('revoked', false)->count());
        $this->assertSame(0, Consent::query()->where('user_id', 2)->count(), 'no consent may be left behind');
    }

    protected function assertRefused(ResponseInterface $response, string $error): void
    {
        $params = $this->redirectParams($response);

        $this->assertArrayNotHasKey('code', $params, 'a code must not reach the client once its authorization was cut off');
        $this->assertSame($error, $params['error'] ?? null);
    }

    /**
     * Runs $hook once, inside the next authorization, after its session and
     * consent checks but before its code is stored.
     */
    protected function beforeTheNextCodeIsStored(\Closure $hook): void
    {
        $this->app()->getContainer()->instance(AuthCodeRepository::class, new class($hook, $this->app()->getContainer()->make(MemberAccess::class)) extends AuthCodeRepository {
            public function __construct(protected ?\Closure $beforePersist, MemberAccess $access)
            {
                parent::__construct($access);
            }

            public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void
            {
                $hook = $this->beforePersist;
                $this->beforePersist = null;
                $hook && $hook();

                parent::persistNewAuthCode($authCodeEntity);
            }
        });
    }

    /**
     * Runs $hook once, just before the consent form's approval is saved.
     */
    protected function onNextConsentSave(\Closure $hook): void
    {
        $this->app();

        Consent::saving(function () use (&$hook) {
            $run = $hook;
            $hook = null;
            $run && $run();
        });
    }

    protected function changePassword(): void
    {
        $response = $this->send(
            $this->request('PATCH', '/api/users/2', ['authenticatedAs' => 1])
                ->withParsedBody(['data' => ['type' => 'users', 'id' => '2', 'attributes' => ['password' => 'a-brand-new-password']]])
        );

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
    }

    /**
     * @return array<string, string>
     */
    protected function codeQuery(): array
    {
        return [
            'client_id' => 'public-client',
            'response_type' => 'code',
            'redirect_uri' => 'https://testapp.example.com/callback',
            'scope' => 'openid',
            'state' => 'xyz',
            'code_challenge' => $this->challenge(),
            'code_challenge_method' => 'S256',
        ];
    }

    /**
     * Stores another tab's first approval of the client right after the next
     * request looked for one and found none, so its own insert conflicts.
     *
     * @param list<string> $scopes
     */
    protected function anotherTabApprovesFirst(array $scopes): void
    {
        $pending = true;

        $this->db()->listen(function (QueryExecuted $query) use (&$pending, $scopes) {
            if ($pending && str_starts_with(strtolower($query->sql), 'select') && str_contains($query->sql, 'oauth_provider_user_consents')) {
                $pending = false;
                Consent::query()->insert(['user_id' => 2, 'client_id' => 'public-client', 'scopes' => json_encode($scopes), 'redirect_uris_hash' => Consent::hashRedirectUris(['https://testapp.example.com/callback']), 'revoked' => false, 'created_at' => Carbon::now()]);
            }
        });
    }

    protected function db(): ConnectionInterface
    {
        return $this->app()->getContainer()->make(ConnectionInterface::class);
    }
}
