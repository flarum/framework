<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\middleware;

use Carbon\Carbon;
use Flarum\Extend;
use Flarum\Http\AccessToken;
use Flarum\Http\Middleware\AuthenticateWithSession;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;

class SessionRevocationTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        RevokingAuthenticateWithSession::$revoked = [];

        $this->extend(
            (new Extend\Middleware('api'))->replace(AuthenticateWithSession::class, RevokingAuthenticateWithSession::class)
        );

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            AccessToken::class => [
                ['token' => 'remembered', 'user_id' => 2, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'session_remember'],
            ],
        ]);
    }

    /** The status of an endpoint that is 401 for a guest. */
    protected function requestWithRememberCookie(): int
    {
        return $this->send(
            $this->request('GET', '/api/notifications')->withCookieParams(['flarum_remember' => 'remembered'])
        )->getStatusCode();
    }

    #[Test]
    public function a_remembered_session_revoked_before_the_next_request_is_a_guest()
    {
        Carbon::setTestNow('2021-01-01 02:30:00');

        $this->assertEquals(200, $this->requestWithRememberCookie());

        $lastSeen = User::query()->find(2)->last_seen_at;

        RevokingAuthenticateWithSession::$revoked = ['remembered'];
        Carbon::setTestNow('2021-01-01 03:30:00');

        $status = $this->requestWithRememberCookie();

        Carbon::setTestNow();

        $this->assertEquals(401, $status);
        $this->assertNull(AccessToken::query()->where('token', 'remembered')->first());
        $this->assertEquals($lastSeen, User::query()->find(2)->last_seen_at);

        // The cookie cannot sign the session back in once the token is gone.
        RevokingAuthenticateWithSession::$revoked = [];
        $this->assertEquals(401, $this->requestWithRememberCookie());
    }
}

class RevokingAuthenticateWithSession extends AuthenticateWithSession
{
    public static array $revoked = [];

    protected function acceptsToken(AccessToken $token, ServerRequestInterface $request): bool
    {
        return ! in_array($token->token, static::$revoked, true);
    }
}
