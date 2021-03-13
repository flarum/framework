<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\access_tokens;

use Carbon\Carbon;
use Flarum\Http\AccessToken;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Laminas\Diactoros\ServerRequest;

class AccessTokenLifecycleTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'access_tokens' => [
                ['token' => 'a', 'user_id' => 1, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'session'],
                ['token' => 'b', 'user_id' => 1, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'session_remember'],
                ['token' => 'c', 'user_id' => 1, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'developer'],
            ],
        ]);
    }

    /**
     * @test
     */
    public function tokens_expire()
    {
        $this->populateDatabase();

        // 30 minutes after last activity
        $this->assertEquals([], AccessToken::whereExpired(Carbon::parse('2021-01-01 02:30:00'))->pluck('token')->all());

        // 1h30 after last activity
        $this->assertEquals(['a'], AccessToken::whereExpired(Carbon::parse('2021-01-01 03:30:00'))->pluck('token')->all());

        // 6 years after last activity
        $this->assertEquals(['a', 'b'], AccessToken::whereExpired(Carbon::parse('2027-01-01 01:00:00'))->pluck('token')->sort()->values()->all());
    }

    /**
     * @test
     */
    public function tokens_valid()
    {
        $this->populateDatabase();

        // 30 minutes after last activity
        $this->assertEquals(['a', 'b', 'c'], AccessToken::whereValid(Carbon::parse('2021-01-01 02:30:00'))->pluck('token')->sort()->values()->all());

        // 1h30 after last activity
        $this->assertEquals(['b', 'c'], AccessToken::whereValid(Carbon::parse('2021-01-01 03:30:00'))->pluck('token')->sort()->values()->all());

        // 6 years after last activity
        $this->assertEquals(['c'], AccessToken::whereValid(Carbon::parse('2027-01-01 01:00:00'))->pluck('token')->all());
    }

    /**
     * @test
     */
    public function touch_updates_lifetime()
    {
        $this->populateDatabase();

        // 45 minutes after last activity
        Carbon::setTestNow('2021-01-01 02:45:00');
        $token = AccessToken::findValid('a');
        $this->assertNotNull($token);
        $token->touch();
        Carbon::setTestNow();

        // 1h30 after original last activity, 45 minutes after touch
        $this->assertTrue(AccessToken::whereValid(Carbon::parse('2021-01-01 03:30:00'))->whereToken('a')->exists());
    }

    /**
     * @test
     */
    public function touch_without_request()
    {
        $this->populateDatabase();

        /** @var AccessToken $token */
        $token = AccessToken::whereToken('a')->firstOrFail();
        $token->touch();

        /** @var AccessToken $token */
        $token = AccessToken::whereToken('a')->firstOrFail();
        $this->assertNull($token->last_ip_address);
        $this->assertNull($token->last_user_agent);
    }

    /**
     * @test
     */
    public function touch_with_request()
    {
        $this->populateDatabase();

        /** @var AccessToken $token */
        $token = AccessToken::whereToken('a')->firstOrFail();
        $token->touch((new ServerRequest([
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36',
        ]))->withAttribute('ipAddress', '8.8.8.8'));

        /** @var AccessToken $token */
        $token = AccessToken::whereToken('a')->firstOrFail();
        $this->assertEquals('8.8.8.8', $token->last_ip_address);
        $this->assertEquals('Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36', $token->last_user_agent);
    }

    /**
     * @test
     */
    public function long_user_agent_id_truncated()
    {
        $this->populateDatabase();

        /** @var AccessToken $token */
        $token = AccessToken::whereToken('a')->firstOrFail();
        $token->touch(new ServerRequest([
            'HTTP_USER_AGENT' => str_repeat('a', 500),
        ]));

        /** @var AccessToken $token */
        $token = AccessToken::whereToken('a')->firstOrFail();
        $this->assertEquals(255, strlen($token->last_user_agent));
    }
}
