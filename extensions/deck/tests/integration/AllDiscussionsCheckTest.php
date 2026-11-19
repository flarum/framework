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
use Flarum\Tags\Tag;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

/**
 * The All Discussions column asks "anything since?" with nothing but
 * `lastPostedAfter`. It must leave out what All Discussions leaves out.
 */
class AllDiscussionsCheckTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags', 'flarum-subscriptions', 'flarum-deck');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Tag::class => [
                ['id' => 1, 'name' => 'General', 'slug' => 'general', 'position' => 0, 'is_hidden' => false],
                ['id' => 2, 'name' => 'Secret', 'slug' => 'secret', 'position' => 1, 'is_hidden' => true],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Normal', 'created_at' => '2026-01-02 10:00:00', 'last_posted_at' => '2026-01-02 10:00:00', 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
                ['id' => 2, 'title' => 'In a hidden tag', 'created_at' => '2026-01-02 10:00:00', 'last_posted_at' => '2026-01-02 10:00:00', 'user_id' => 1, 'first_post_id' => 2, 'comment_count' => 1],
                ['id' => 3, 'title' => 'Ignored', 'created_at' => '2026-01-02 10:00:00', 'last_posted_at' => '2026-01-02 10:00:00', 'user_id' => 1, 'first_post_id' => 3, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'created_at' => '2026-01-02 10:00:00', 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Hello</p></t>'],
                ['id' => 2, 'discussion_id' => 2, 'number' => 1, 'created_at' => '2026-01-02 10:00:00', 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Hello</p></t>'],
                ['id' => 3, 'discussion_id' => 3, 'number' => 1, 'created_at' => '2026-01-02 10:00:00', 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Hello</p></t>'],
            ],
            'discussion_tag' => [
                ['discussion_id' => 1, 'tag_id' => 1],
                ['discussion_id' => 2, 'tag_id' => 2],
                ['discussion_id' => 3, 'tag_id' => 1],
            ],
            'discussion_user' => [
                ['discussion_id' => 3, 'user_id' => 2, 'subscription' => 'ignore'],
            ],
        ]);
    }

    public function test_all_discussions_leaves_out_hidden_tags_and_ignored_discussions(): void
    {
        $this->assertEquals(['1'], $this->discussionIds([]));
    }

    public function test_the_check_for_new_activity_leaves_them_out_too(): void
    {
        $this->assertEquals(['1'], $this->discussionIds(['lastPostedAfter' => '2026-01-01T00:00:00.000Z']));
    }

    public function test_a_column_filtered_by_a_hidden_tag_still_sees_its_activity(): void
    {
        $this->assertEquals(['2'], $this->discussionIds(['tag' => 'secret', 'lastPostedAfter' => '2026-01-01T00:00:00.000Z']));
    }

    protected function discussionIds(array $filter): array
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 2])
                ->withQueryParams($filter ? ['filter' => $filter] : [])
        );

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        $ids = array_column(json_decode($body, true)['data'], 'id');
        sort($ids);

        return $ids;
    }
}
