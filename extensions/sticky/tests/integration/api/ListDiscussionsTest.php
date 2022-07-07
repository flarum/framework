<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Sticky\tests\integration\api;

use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Illuminate\Support\Arr;

class ListDiscussionsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-sticky');

        $this->prepareDatabase([
            'users' => [
                ['id' => 1, 'username' => 'Muralf', 'email' => 'muralf@machine.local', 'is_email_confirmed' => 1],
                $this->normalUser()
            ],
            'discussions' => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'is_sticky' => true],
                ['id' => 2, 'title' => __CLASS__, 'created_at' => Carbon::now()->addMinute(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'is_sticky' => false],
                ['id' => 3, 'title' => __CLASS__, 'created_at' => Carbon::now()->addMinutes(2), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'is_sticky' => true],
                ['id' => 4, 'title' => __CLASS__, 'created_at' => Carbon::now()->addMinutes(3), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'is_sticky' => false],
            ],
            'discussion_user' => [
                ['discussion_id' => 1, 'user_id' => 1, 'last_read_post_number' => 1],
                ['discussion_id' => 3, 'user_id' => 1, 'last_read_post_number' => 1],
            ]
        ]);
    }

    /** @test */
    public function list_discussions_shows_sticky_first_as_guest()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEqualsCanonicalizing([1, 3, 2, 4], Arr::pluck($data['data'], 'id'));
    }

    /** @test */
    public function list_discussions_shows_sticky_unread_first_as_user()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', [
                'authenticatedAs' => 2
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEqualsCanonicalizing([1, 3, 2, 4], Arr::pluck($data['data'], 'id'));
    }

    /** @test */
    public function list_discussions_shows_normal_order_when_all_read_as_user()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', [
                'authenticatedAs' => 1
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEqualsCanonicalizing([1, 2, 3, 4], Arr::pluck($data['data'], 'id'));
    }
}
