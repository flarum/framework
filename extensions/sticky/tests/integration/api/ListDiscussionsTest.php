<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Sticky\tests\integration\api;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Tags\Tag;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Support\Arr;

class ListDiscussionsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags', 'flarum-sticky');

        $this->prepareDatabase([
            User::class => [
                ['id' => 1, 'username' => 'Muralf', 'email' => 'muralf@machine.local', 'is_email_confirmed' => 1],
                $this->normalUser(),
                ['id' => 3, 'username' => 'Muralf_', 'email' => 'muralf_@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'is_sticky' => true, 'last_post_number' => 1],
                ['id' => 2, 'title' => __CLASS__, 'created_at' => Carbon::now()->addMinutes(2), 'last_posted_at' => Carbon::now()->addMinutes(5), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'is_sticky' => false, 'last_post_number' => 1],
                ['id' => 3, 'title' => __CLASS__, 'created_at' => Carbon::now()->addMinutes(3), 'last_posted_at' => Carbon::now()->addMinute(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'is_sticky' => true, 'last_post_number' => 1],
                ['id' => 4, 'title' => __CLASS__, 'created_at' => Carbon::now()->addMinutes(4), 'last_posted_at' => Carbon::now()->addMinutes(2), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'is_sticky' => false, 'last_post_number' => 1],
            ],
            'discussion_user' => [
                ['discussion_id' => 1, 'user_id' => 3, 'last_read_post_number' => 1],
                ['discussion_id' => 3, 'user_id' => 3, 'last_read_post_number' => 1],
            ],
            Tag::class => [
                ['id' => 1, 'slug' => 'general', 'position' => 0, 'parent_id' => null]
            ],
            'discussion_tag' => [
                ['discussion_id' => 1, 'tag_id' => 1],
                ['discussion_id' => 2, 'tag_id' => 1],
                ['discussion_id' => 3, 'tag_id' => 1],
                ['discussion_id' => 4, 'tag_id' => 1],
            ]
        ]);
    }

    /** @test */
    public function list_discussions_shows_sticky_first_as_guest()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        $data = json_decode($body, true);

        $this->assertEquals([3, 1, 2, 4], Arr::pluck($data['data'], 'id'));
    }

    /** @test */
    public function list_discussions_shows_sticky_unread_first_as_user()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', [
                'authenticatedAs' => 2
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), $body = $response->getBody()->getContents());

        $data = json_decode($body, true);

        $this->assertEqualsCanonicalizing([3, 1, 2, 4], Arr::pluck($data['data'], 'id'));
    }

    /** @test */
    public function list_discussions_shows_normal_order_when_all_read_as_user()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', [
                'authenticatedAs' => 3
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), $body = $response->getBody()->getContents());

        $data = json_decode($body, true);

        $this->assertEqualsCanonicalizing([2, 4, 3, 1], Arr::pluck($data['data'], 'id'));
    }

    /** @test */
    public function list_discussions_shows_stick_first_on_a_tag()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', [
                'authenticatedAs' => 3
            ])->withQueryParams([
                'filter' => [
                    'tag' => 'general'
                ]
            ])
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        $data = json_decode($body, true);

        $this->assertEquals([3, 1, 2, 4], Arr::pluck($data['data'], 'id'));
    }
}
