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
use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\Tests\integration\TestCase;

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
        $this->assertEquals(['a', 'b'], AccessToken::whereExpired(Carbon::parse('2027-01-01 01:00:00'))->pluck('token')->all());
    }

    /**
     * @test
     */
    public function tokens_valid()
    {
        $this->populateDatabase();

        // 30 minutes after last activity
        $this->assertEquals(['a', 'b', 'c'], AccessToken::whereValid(Carbon::parse('2021-01-01 02:30:00'))->pluck('token')->all());

        // 1h30 after last activity
        $this->assertEquals(['b', 'c'], AccessToken::whereValid(Carbon::parse('2021-01-01 03:30:00'))->pluck('token')->all());

        // 6 years after last activity
        $this->assertEquals(['c'], AccessToken::whereValid(Carbon::parse('2027-01-01 01:00:00'))->pluck('token')->all());
    }
}
