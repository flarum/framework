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
use PHPUnit\Framework\Attributes\Test;

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
                // Sticky discussion in a hidden tag — must not appear on the all-discussions page.
                ['id' => 5, 'title' => __CLASS__, 'created_at' => Carbon::now()->addMinutes(10), 'last_posted_at' => Carbon::now()->addMinutes(10), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'is_sticky' => true, 'last_post_number' => 1],
            ],
            'discussion_user' => [
                ['discussion_id' => 1, 'user_id' => 3, 'last_read_post_number' => 1],
                ['discussion_id' => 3, 'user_id' => 3, 'last_read_post_number' => 1],
            ],
            Tag::class => [
                ['id' => 1, 'slug' => 'general', 'position' => 0, 'parent_id' => null],
                ['id' => 2, 'slug' => 'hidden', 'position' => 1, 'parent_id' => null, 'is_hidden' => true],
            ],
            'discussion_tag' => [
                ['discussion_id' => 1, 'tag_id' => 1],
                ['discussion_id' => 2, 'tag_id' => 1],
                ['discussion_id' => 3, 'tag_id' => 1],
                ['discussion_id' => 4, 'tag_id' => 1],
                ['discussion_id' => 5, 'tag_id' => 2],
            ],
        ]);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function list_discussions_sticky_first_all_read_as_user_filter_read_off()
    {
        $this->setting('flarum-sticky.only_sticky_unread_discussions', false);
        $response = $this->send(
            $this->request('GET', '/api/discussions', [
                'authenticatedAs' => 3
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals([3, 1, 2, 4], Arr::pluck($data['data'], 'id'));
    }

    #[Test]
    public function list_discussions_sticky_first_all_read_as_user_filter_read_on()
    {
        $this->setting('flarum-sticky.only_sticky_unread_discussions', true);
        $response = $this->send(
            $this->request('GET', '/api/discussions', [
                'authenticatedAs' => 3
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals([2, 4, 3, 1], Arr::pluck($data['data'], 'id'));
    }

    #[Test]
    public function sticky_discussion_in_hidden_tag_excluded_from_all_discussions_as_guest()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
        );

        $this->assertEquals(200, $response->getStatusCode(), $body = $response->getBody()->getContents());

        $ids = Arr::pluck(json_decode($body, true)['data'], 'id');

        $this->assertNotContains('5', $ids);
    }

    #[Test]
    public function sticky_discussion_in_hidden_tag_excluded_from_all_discussions_with_only_unread_on()
    {
        $this->setting('flarum-sticky.only_sticky_unread_discussions', true);

        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 2])
        );

        $this->assertEquals(200, $response->getStatusCode(), $body = $response->getBody()->getContents());

        $ids = Arr::pluck(json_decode($body, true)['data'], 'id');

        $this->assertNotContains('5', $ids);
    }

    #[Test]
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

    #[Test]
    public function list_discussions_does_not_pin_sticky_on_all_when_pin_setting_disabled_as_guest()
    {
        $this->setting('flarum-sticky.pin_sticky_on_all_discussions', false);

        $response = $this->send(
            $this->request('GET', '/api/discussions')
        );

        $this->assertEquals(200, $response->getStatusCode(), $body = $response->getBody()->getContents());

        $data = json_decode($body, true);

        $this->assertEquals([2, 4, 3, 1], Arr::pluck($data['data'], 'id'));
    }

    #[Test]
    public function list_discussions_pin_setting_disabled_overrides_only_unread_setting_on_all()
    {
        // pin_sticky_on_all_discussions is the master gate for /all and must
        // override only_sticky_unread_discussions when both are flipped.
        $this->setting('flarum-sticky.pin_sticky_on_all_discussions', false);
        $this->setting('flarum-sticky.only_sticky_unread_discussions', false);

        $response = $this->send(
            $this->request('GET', '/api/discussions', [
                'authenticatedAs' => 2
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), $body = $response->getBody()->getContents());

        $data = json_decode($body, true);

        $this->assertEquals([2, 4, 3, 1], Arr::pluck($data['data'], 'id'));
    }

    #[Test]
    public function list_discussions_pin_setting_disabled_does_not_affect_tag_pages()
    {
        $this->setting('flarum-sticky.pin_sticky_on_all_discussions', false);

        $response = $this->send(
            $this->request('GET', '/api/discussions', [
                'authenticatedAs' => 3
            ])->withQueryParams([
                'filter' => [
                    'tag' => 'general'
                ]
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), $body = $response->getBody()->getContents());

        $data = json_decode($body, true);

        $this->assertEquals([3, 1, 2, 4], Arr::pluck($data['data'], 'id'));
    }

    #[Test]
    public function list_discussions_only_unread_off_does_not_pin_sticky_on_a_non_tag_filter()
    {
        // With only_sticky_unread_discussions off, pinning applies to the
        // All Discussions page and to a single-tag page — but NOT to other
        // filtered listings (e.g. a by-author search). Previously the
        // "pin all when only_unread is off" branch ran ahead of the filter
        // check, so it pinned sticky on any filtered listing; pinning is now
        // scoped to /all and single-tag pages, which is the intended behaviour.
        $this->setting('flarum-sticky.only_sticky_unread_discussions', false);

        $response = $this->send(
            $this->request('GET', '/api/discussions', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'filter' => [
                    'author' => 'Muralf',
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), $body = $response->getBody()->getContents());

        $data = json_decode($body, true);

        // Natural last_posted_at order — no sticky pinning on a non-tag filter.
        // The sticky discussions (1 and 3) sit last, in their natural position,
        // rather than floating to the top. (5 is also sticky and appears here
        // because the author filter isn't tag-scoped and the actor is an admin;
        // it leads only because it is the most recently posted, not because it
        // is stickied.)
        $this->assertEquals([5, 2, 4, 3, 1], Arr::pluck($data['data'], 'id'));
    }
}
