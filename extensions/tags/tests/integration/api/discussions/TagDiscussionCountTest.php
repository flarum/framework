<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration\api\discussions;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Tags\Tag;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TagDiscussionCountTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');

        $dayAgo = Carbon::now()->subDay();

        $this->prepareDatabase([
            Tag::class => [
                ['id' => 1, 'name' => 'Primary 1', 'slug' => 'primary-1', 'is_primary' => true, 'position' => 0, 'parent_id' => null, 'discussion_count' => 1, 'last_posted_at' => $dayAgo, 'last_posted_discussion_id' => 1, 'last_posted_user_id' => 1],
                ['id' => 2, 'name' => 'Primary 2', 'slug' => 'primary-2', 'is_primary' => true, 'position' => 1, 'parent_id' => null, 'discussion_count' => 1, 'last_posted_at' => $dayAgo, 'last_posted_discussion_id' => 3, 'last_posted_user_id' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Visible in tag 1', 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'created_at' => $dayAgo, 'last_posted_at' => $dayAgo, 'last_posted_user_id' => 1],
                ['id' => 2, 'title' => 'Hidden in tag 1', 'user_id' => 1, 'first_post_id' => 2, 'comment_count' => 1, 'created_at' => $dayAgo, 'last_posted_at' => Carbon::now(), 'last_posted_user_id' => 1, 'hidden_at' => Carbon::now(), 'hidden_user_id' => 1],
                ['id' => 3, 'title' => 'Visible in tag 2', 'user_id' => 1, 'first_post_id' => 3, 'comment_count' => 1, 'created_at' => $dayAgo, 'last_posted_at' => $dayAgo, 'last_posted_user_id' => 1],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Text</p></t>'],
                ['id' => 2, 'number' => 1, 'discussion_id' => 2, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Text</p></t>'],
                ['id' => 3, 'number' => 1, 'discussion_id' => 3, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Text</p></t>'],
            ],
            'discussion_tag' => [
                ['discussion_id' => 1, 'tag_id' => 1],
                ['discussion_id' => 2, 'tag_id' => 1],
                ['discussion_id' => 3, 'tag_id' => 2],
            ],
        ]);
    }

    #[Test]
    public function retagging_a_visible_discussion_moves_it_to_the_new_tag_count()
    {
        $this->retagDiscussion(1, [2]);

        $this->assertDiscussionCounts([1 => 0, 2 => 2]);
    }

    #[Test]
    public function retagging_a_hidden_discussion_does_not_change_tag_counts()
    {
        $this->retagDiscussion(2, [2]);

        $this->assertDiscussionCounts([1 => 1, 2 => 1]);
    }

    #[Test]
    public function hiding_and_retagging_a_discussion_in_one_request_only_removes_it_from_the_old_tag_count()
    {
        $this->retagDiscussion(1, [2], ['isHidden' => true]);

        $this->assertDiscussionCounts([1 => 0, 2 => 1]);
    }

    #[Test]
    public function restoring_and_retagging_a_discussion_in_one_request_only_adds_it_to_the_new_tag_count()
    {
        $this->retagDiscussion(2, [2], ['isHidden' => false]);

        $this->assertDiscussionCounts([1 => 1, 2 => 2]);
    }

    #[Test]
    public function restoring_and_retagging_a_discussion_in_one_request_does_not_make_it_the_old_tag_last_posted_discussion()
    {
        $this->retagDiscussion(2, [2], ['isHidden' => false]);

        $this->assertEquals(1, Tag::query()->findOrFail(1)->last_posted_discussion_id);
        $this->assertEquals(2, Tag::query()->findOrFail(2)->last_posted_discussion_id);
    }

    private function retagDiscussion(int $discussionId, array $tagIds, array $attributes = []): void
    {
        $data = [
            'relationships' => [
                'tags' => [
                    'data' => array_map(fn (int $id) => ['type' => 'tags', 'id' => $id], $tagIds),
                ],
            ],
        ];

        if ($attributes) {
            $data['attributes'] = $attributes;
        }

        $response = $this->send(
            $this->request('PATCH', "/api/discussions/$discussionId", [
                'authenticatedAs' => 1,
                'json' => ['data' => $data],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }

    /**
     * @param array<int, int> $expected tag id => discussion count
     */
    private function assertDiscussionCounts(array $expected): void
    {
        $actual = Tag::query()
            ->whereIn('id', array_keys($expected))
            ->pluck('discussion_count', 'id')
            ->map(fn ($count) => (int) $count)
            ->all();

        ksort($actual);

        $this->assertSame($expected, $actual);
    }
}
