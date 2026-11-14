<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Deck\Tests\integration;

use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

class LastPostedAfterFilterTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-deck');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Quiet', 'created_at' => '2026-01-01 10:00:00', 'last_posted_at' => '2026-01-01 10:00:00', 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
                ['id' => 2, 'title' => 'Busy', 'created_at' => '2026-01-01 10:00:00', 'last_posted_at' => '2026-01-02 12:00:00', 'user_id' => 1, 'first_post_id' => 2, 'comment_count' => 2],
                ['id' => 3, 'title' => 'Hidden', 'created_at' => '2026-01-03 10:00:00', 'last_posted_at' => '2026-01-03 10:00:00', 'hidden_at' => '2026-01-03 11:00:00', 'user_id' => 1, 'first_post_id' => 4, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'created_at' => '2026-01-01 10:00:00', 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Hello</p></t>'],
                ['id' => 2, 'discussion_id' => 2, 'number' => 1, 'created_at' => '2026-01-01 10:00:00', 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Hello</p></t>'],
                ['id' => 3, 'discussion_id' => 2, 'number' => 2, 'created_at' => '2026-01-02 12:00:00', 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Reply</p></t>'],
                ['id' => 4, 'discussion_id' => 3, 'number' => 1, 'created_at' => '2026-01-03 10:00:00', 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Hello</p></t>'],
            ],
        ]);
    }

    public function test_only_discussions_with_later_activity_are_returned(): void
    {
        $this->assertEquals(['2'], $this->discussionIds('2026-01-01T12:00:00.000Z'));
    }

    public function test_activity_exactly_at_the_key_is_not_new(): void
    {
        $this->assertEquals([], $this->discussionIds('2026-01-02T12:00:00.000Z'));
    }

    public function test_it_still_respects_visibility(): void
    {
        $this->assertEquals(['2'], $this->discussionIds('2026-01-01T12:00:00.000Z', 2));
    }

    public function test_an_unreadable_key_matches_nothing(): void
    {
        $this->assertEquals([], $this->discussionIds('not a date'));
    }

    protected function discussionIds(string $after, ?int $userId = null): array
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', $userId ? ['authenticatedAs' => $userId] : [])
                ->withQueryParams(['filter' => ['lastPostedAfter' => $after]])
        );

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        return array_column(json_decode($body, true)['data'], 'id');
    }
}
