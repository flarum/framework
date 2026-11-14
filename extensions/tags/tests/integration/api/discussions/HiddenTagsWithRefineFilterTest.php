<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration\api\discussions;

use Flarum\Discussion\Discussion;
use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Extend;
use Flarum\Search\Database\DatabaseSearchDriver;
use Flarum\Tags\Tag;
use Flarum\Tags\Tests\fixtures\WithoutDiscussionsFilter;
use Flarum\Testing\integration\TestCase;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;

/**
 * A refine filter (`without`) narrows the list a person is looking at, as a
 * date range would. All Discussions refined is still All Discussions, so it
 * still leaves out discussions in hidden tags.
 */
class HiddenTagsWithRefineFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');

        $this->extend(
            (new Extend\SearchDriver(DatabaseSearchDriver::class))
                ->addFilter(DiscussionSearcher::class, WithoutDiscussionsFilter::class)
        );

        $this->prepareDatabase([
            Tag::class => [
                ['id' => 1, 'name' => 'General', 'slug' => 'general', 'position' => 0],
                ['id' => 2, 'name' => 'Secret', 'slug' => 'secret', 'position' => 1, 'is_hidden' => true],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Normal', 'created_at' => '2026-01-02 10:00:00', 'last_posted_at' => '2026-01-02 10:00:00', 'user_id' => 1, 'comment_count' => 1],
                ['id' => 2, 'title' => 'Also normal', 'created_at' => '2026-01-02 11:00:00', 'last_posted_at' => '2026-01-02 11:00:00', 'user_id' => 1, 'comment_count' => 1],
                ['id' => 3, 'title' => 'In a hidden tag', 'created_at' => '2026-01-02 12:00:00', 'last_posted_at' => '2026-01-02 12:00:00', 'user_id' => 1, 'comment_count' => 1],
                ['id' => 4, 'title' => 'Also in a hidden tag', 'created_at' => '2026-01-02 13:00:00', 'last_posted_at' => '2026-01-02 13:00:00', 'user_id' => 1, 'comment_count' => 1],
            ],
            'discussion_tag' => [
                ['discussion_id' => 1, 'tag_id' => 1],
                ['discussion_id' => 2, 'tag_id' => 1],
                ['discussion_id' => 3, 'tag_id' => 2],
                ['discussion_id' => 4, 'tag_id' => 2],
            ],
        ]);
    }

    private function ids(array $filter): array
    {
        $response = $this->send($this->request('GET', '/api/discussions')->withQueryParams(['filter' => $filter]));

        $body = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        return array_map('intval', Arr::pluck(json_decode($body, true)['data'], 'id'));
    }

    #[Test]
    public function all_discussions_refined_still_leaves_out_hidden_tags()
    {
        $this->assertEquals([2], $this->ids(['without' => '1']));
    }

    #[Test]
    public function a_hidden_tag_page_refined_still_lists_its_discussions()
    {
        $this->assertEquals([3], $this->ids(['tag' => 'secret', 'without' => '4']));
    }
}
