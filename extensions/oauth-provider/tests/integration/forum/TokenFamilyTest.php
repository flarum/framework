<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Tests\integration\forum;

use Flarum\Extend;
use Flarum\OAuthProvider\Access\MemberAccess;
use Flarum\OAuthProvider\Models\AccessToken;
use Flarum\OAuthProvider\Models\RefreshToken;
use Flarum\OAuthProvider\Server\Repository\AuthCodeRepository;
use Flarum\OAuthProvider\Server\Repository\RefreshTokenRepository;
use Flarum\OAuthProvider\Tests\integration\InteractsWithOAuth;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * The tokens issued from one authorization code, through every refresh, form
 * a family: one-time use of codes and refresh tokens is enforced atomically,
 * and a replay revokes that family and nothing else.
 */
class TokenFamilyTest extends TestCase
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

    protected function accessTokenRow(string $jwt): AccessToken
    {
        return AccessToken::query()->findOrFail($this->jwtClaims($jwt)['jti']);
    }

    protected function assertFamilyRevoked(string $accessToken): void
    {
        $row = $this->accessTokenRow($accessToken);

        $this->assertTrue($row->revoked, 'The access token must be revoked');
        $this->assertTrue(RefreshToken::query()->where('access_token_id', $row->id)->firstOrFail()->revoked, 'The refresh token must be revoked');
    }

    /**
     * Both requests checked the code before either had used it up: the check
     * reads a stale "not yet used".
     */
    protected function codeChecksAreStale(): void
    {
        $this->app()->getContainer()->instance(AuthCodeRepository::class, new class($this->access()) extends AuthCodeRepository {
            public function isAuthCodeRevoked($codeId): bool
            {
                return false;
            }
        });
    }

    /**
     * Both requests checked the refresh token before either had rotated it.
     */
    protected function refreshChecksAreStale(): void
    {
        $this->app()->getContainer()->instance(RefreshTokenRepository::class, new class($this->access()) extends RefreshTokenRepository {
            public function isRefreshTokenRevoked($tokenId): bool
            {
                return false;
            }
        });
    }

    protected function access(): MemberAccess
    {
        return $this->app()->getContainer()->make(MemberAccess::class);
    }

    #[Test]
    public function racing_code_exchanges_issue_tokens_only_once(): void
    {
        $code = $this->publicClientCode('public-client');
        $this->codeChecksAreStale();

        $first = $this->exchangeCode('public-client', $code);
        $second = $this->exchangeCode('public-client', $code);

        $this->assertSame(200, $first->getStatusCode(), (string) $first->getBody());
        $this->assertSame(400, $second->getStatusCode(), 'The second exchange of one code must fail: '.$second->getBody());
        $this->assertSame('invalid_grant', $this->json($second)['error']);
        $this->assertSame(1, AccessToken::query()->count(), 'Only one exchange may issue tokens');

        // The code was presented twice: what it was exchanged for is revoked.
        $this->assertFamilyRevoked($this->json($first)['access_token']);
    }

    #[Test]
    public function a_replayed_code_revokes_the_tokens_issued_from_it(): void
    {
        $code = $this->publicClientCode('public-client');
        $issued = $this->json($this->exchangeCode('public-client', $code));
        $refreshed = $this->refresh('public-client', $issued['refresh_token']);
        $this->assertSame(200, $refreshed->getStatusCode(), (string) $refreshed->getBody());
        $current = $this->json($refreshed);
        $unrelated = $this->publicClientTokens('public-client');

        $replay = $this->exchangeCode('public-client', $code);

        $this->assertSame(400, $replay->getStatusCode());
        $this->assertSame('invalid_grant', $this->json($replay)['error']);
        $this->assertFamilyRevoked($issued['access_token']);
        $this->assertFamilyRevoked($current['access_token']);
        $this->assertFalse($this->accessTokenRow($unrelated['access_token'])->revoked, 'Tokens from another authorization are left alone');
    }

    #[Test]
    public function racing_refreshes_of_one_token_succeed_only_once(): void
    {
        $tokens = $this->publicClientTokens('public-client');
        $this->refreshChecksAreStale();

        $first = $this->refresh('public-client', $tokens['refresh_token']);
        $second = $this->refresh('public-client', $tokens['refresh_token']);

        $this->assertSame(200, $first->getStatusCode(), (string) $first->getBody());
        $this->assertSame(400, $second->getStatusCode(), 'The second refresh with one token must fail: '.$second->getBody());
        $this->assertSame('invalid_grant', $this->json($second)['error']);
        $this->assertFamilyRevoked($this->json($first)['access_token']);
    }

    #[Test]
    public function a_code_replayed_while_its_tokens_are_being_issued_still_revokes_them(): void
    {
        $code = $this->publicClientCode('public-client');
        $replay = null;

        // B presents the code after A used it up but before A stored its tokens.
        $this->app()->getContainer()->instance(AuthCodeRepository::class, new class(function () use ($code, &$replay) {
            $replay = $this->exchangeCode('public-client', $code);
        }, $this->access()) extends AuthCodeRepository {
            public function __construct(protected ?\Closure $afterConsume, MemberAccess $access)
            {
                parent::__construct($access);
            }

            public function consumeAuthCode(string $codeId): bool
            {
                $consumed = parent::consumeAuthCode($codeId);
                $hook = $this->afterConsume;
                $this->afterConsume = null;
                $hook && $hook();

                return $consumed;
            }
        });

        $first = $this->exchangeCode('public-client', $code);

        $this->assertSame(400, $replay?->getStatusCode());
        $this->assertSame(0, AccessToken::query()->where('revoked', false)->count(), 'No token issued from a replayed code may stay valid: '.$first->getBody());
        $this->assertSame(0, RefreshToken::query()->where('revoked', false)->count());
        $this->assertSame(400, $first->getStatusCode());
    }

    #[Test]
    public function a_refresh_token_reused_while_its_successor_is_being_issued_still_revokes_it(): void
    {
        $tokens = $this->publicClientTokens('public-client');
        $reuse = null;

        $this->app()->getContainer()->instance(RefreshTokenRepository::class, new class(function () use ($tokens, &$reuse) {
            $reuse = $this->refresh('public-client', $tokens['refresh_token']);
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

        $first = $this->refresh('public-client', $tokens['refresh_token']);

        $this->assertSame(400, $reuse?->getStatusCode());
        $this->assertSame(0, AccessToken::query()->where('revoked', false)->count(), 'No token of a reused grant may stay valid: '.$first->getBody());
        $this->assertSame(0, RefreshToken::query()->where('revoked', false)->count());
        $this->assertSame(400, $first->getStatusCode());
    }

    #[Test]
    public function refresh_token_reuse_only_revokes_its_own_grant(): void
    {
        $reused = $this->publicClientTokens('public-client');
        $otherSession = $this->publicClientTokens('public-client');

        $this->assertSame(200, $this->refresh('public-client', $reused['refresh_token'])->getStatusCode());
        $reuse = $this->refresh('public-client', $reused['refresh_token']);
        $this->assertSame('invalid_grant', $this->json($reuse)['error']);

        $this->assertFalse($this->accessTokenRow($otherSession['access_token'])->revoked, 'Another sign-in of the same user and client must survive');
        $this->assertSame(200, $this->refresh('public-client', $otherSession['refresh_token'])->getStatusCode());
    }
}
