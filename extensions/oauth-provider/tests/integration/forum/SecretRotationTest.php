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
use Psr\Http\Message\ResponseInterface;

/**
 * Rotating a confidential client's secret locks out whoever held the old one:
 * no token may survive it, not even one being issued while it runs.
 */
class SecretRotationTest extends TestCase
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
            'oauth_provider_clients' => [
                $this->clientRow('confidential-client', true),
                $this->clientRow('other-client', false),
            ],
        ]);
    }

    #[Test]
    public function a_code_exchange_racing_a_rotation_leaves_no_working_token(): void
    {
        $code = $this->publicClientCode('confidential-client');

        // The old secret was accepted and the code used up, but the secret is
        // rotated before the new tokens are stored.
        $this->app()->getContainer()->instance(AuthCodeRepository::class, new class(fn () => $this->rotate(), $this->access()) extends AuthCodeRepository {
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

        $this->assertGrantRevoked($this->exchangeCode('confidential-client', $code, tokenParams: ['client_secret' => 'test-secret']));
        $this->assertNoWorkingToken();
    }

    #[Test]
    public function a_refresh_racing_a_rotation_leaves_no_working_token(): void
    {
        $tokens = $this->publicClientTokens('confidential-client', tokenParams: ['client_secret' => 'test-secret']);
        $other = $this->publicClientTokens('other-client');

        $this->app()->getContainer()->instance(RefreshTokenRepository::class, new class(fn () => $this->rotate(), $this->access()) extends RefreshTokenRepository {
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

        $this->assertGrantRevoked($this->refreshWith('test-secret', $tokens['refresh_token']));
        $this->assertNoWorkingToken();

        // Another client's grant is untouched.
        $this->assertSame(200, $this->send($this->request('GET', '/oauth/userinfo')->withHeader('Authorization', 'Bearer '.$other['access_token']))->getStatusCode());
    }

    #[Test]
    public function a_code_presented_after_a_rotation_is_told_its_grant_was_revoked(): void
    {
        $code = $this->publicClientCode('confidential-client');
        $secret = $this->rotate();

        // The client itself, now holding the new secret, is not told it used
        // the code twice.
        $this->assertGrantRevoked($this->exchangeCode('confidential-client', $code, tokenParams: ['client_secret' => $secret]));
    }

    #[Test]
    public function a_refresh_after_a_rotation_is_told_its_grant_was_revoked(): void
    {
        $tokens = $this->publicClientTokens('confidential-client', tokenParams: ['client_secret' => 'test-secret']);
        $secret = $this->rotate();

        $this->assertGrantRevoked($this->refreshWith($secret, $tokens['refresh_token']));
    }

    /**
     * Rotates the confidential client's secret as an admin; returns the new one.
     */
    protected function rotate(): string
    {
        $response = $this->send(
            $this->request('POST', '/api/oauth-provider-clients/confidential-client/rotate-secret', ['authenticatedAs' => 1])
                ->withAttribute('bypassCsrfToken', true)
        );

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());

        return $this->json($response)['data']['attributes']['plainSecret'];
    }

    protected function refreshWith(string $secret, string $refreshToken): ResponseInterface
    {
        return $this->token(['grant_type' => 'refresh_token', 'client_id' => 'confidential-client', 'client_secret' => $secret, 'refresh_token' => $refreshToken]);
    }

    protected function assertGrantRevoked(ResponseInterface $response): void
    {
        $this->assertSame(400, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame('invalid_grant', $this->json($response)['error']);
        $this->assertSame('This grant has been revoked.', $this->json($response)['hint']);
    }

    protected function assertNoWorkingToken(): void
    {
        $this->assertSame(0, AccessToken::query()->where('client_id', 'confidential-client')->where('revoked', false)->count(), 'no access token may outlive the rotation');
        $this->assertSame(0, RefreshToken::query()->whereIn('access_token_id', AccessToken::query()->select('id')->where('client_id', 'confidential-client'))->where('revoked', false)->count(), 'no refresh token may outlive the rotation');
    }

    protected function access(): MemberAccess
    {
        return $this->app()->getContainer()->make(MemberAccess::class);
    }
}
