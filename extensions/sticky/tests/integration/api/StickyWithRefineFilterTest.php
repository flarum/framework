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
use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Extend;
use Flarum\Search\Database\DatabaseSearchDriver;
use Flarum\Sticky\Tests\fixtures\RefiningWindowFilter;
use Flarum\Sticky\Tests\fixtures\WindowFilter;
use Flarum\Sticky\Tests\fixtures\WithoutDiscussionsFilter;
use Flarum\Tags\Tag;
use Flarum\Testing\integration\TestCase;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;

/**
 * A refine filter (`without`) narrows the list a person is looking at, as a
 * date range would. The list keeps its sticky discussions pinned: All
 * Discussions and tag pages alike.
 *
 * Each tag has a sticky discussion that was last posted in before the others.
 */
class StickyWithRefineFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags', 'flarum-sticky');

        $this->extend(
            (new Extend\SearchDriver(DatabaseSearchDriver::class))
                ->addFilter(DiscussionSearcher::class, WithoutDiscussionsFilter::class)
                ->addFilter(DiscussionSearcher::class, WindowFilter::class)
                ->addFilter(DiscussionSearcher::class, RefiningWindowFilter::class)
        );

        $at = fn (int $minutes) => Carbon::parse('2026-01-01 00:00:00')->addMinutes($minutes);
        $discussion = fn (int $id, bool $sticky, int $minutes) => ['id' => $id, 'title' => "Discussion $id", 'user_id' => 1, 'comment_count' => 1, 'last_post_number' => 1, 'is_sticky' => $sticky, 'created_at' => $at($minutes), 'last_posted_at' => $at($minutes)];

        $this->prepareDatabase([
            Tag::class => [
                ['id' => 1, 'name' => 'General', 'slug' => 'general', 'position' => 0],
                ['id' => 2, 'name' => 'Newest', 'slug' => 'newest-first', 'position' => 1, 'default_sort' => 'newest'],
            ],
            Discussion::class => [
                $discussion(1, true, 0),
                $discussion(2, false, 1),
                $discussion(3, false, 2),
                $discussion(4, false, 3),
                $discussion(11, true, -10),
                $discussion(12, false, -9),
                $discussion(13, false, -8),
            ],
            'discussion_tag' => [
                ['discussion_id' => 1, 'tag_id' => 1],
                ['discussion_id' => 2, 'tag_id' => 1],
                ['discussion_id' => 3, 'tag_id' => 1],
                ['discussion_id' => 4, 'tag_id' => 1],
                ['discussion_id' => 11, 'tag_id' => 2],
                ['discussion_id' => 12, 'tag_id' => 2],
                ['discussion_id' => 13, 'tag_id' => 2],
            ],
        ]);
    }

    private function ids(array $filter, ?string $sort = null): array
    {
        $params = ['filter' => $filter];

        if ($sort) {
            $params['sort'] = $sort;
        }

        $response = $this->send($this->request('GET', '/api/discussions')->withQueryParams($params));

        $body = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        return array_map('intval', Arr::pluck(json_decode($body, true)['data'], 'id'));
    }

    #[Test]
    public function all_discussions_refined_pins_sticky_discussions()
    {
        $this->setting('flarum-sticky.only_sticky_unread_discussions', false);

        $this->assertEquals([1, 11, 3, 2, 13, 12], $this->ids(['without' => '4']));
    }

    #[Test]
    public function all_discussions_refined_pins_unread_sticky_discussions_and_keeps_the_refinement()
    {
        // The unread-only mode marries a second query of sticky discussions
        // into the first: the refinement must hold in both, so it leaves out a
        // sticky discussion here.
        $this->setting('flarum-sticky.only_sticky_unread_discussions', true);

        $this->assertEquals([11, 4, 3, 2, 13, 12], $this->ids(['without' => '1']));
    }

    #[Test]
    public function all_discussions_refined_does_not_pin_when_the_admin_turned_pinning_off()
    {
        $this->setting('flarum-sticky.pin_sticky_on_all_discussions', false);

        $this->assertEquals([3, 2, 1, 13, 12, 11], $this->ids(['without' => '4']));
    }

    #[Test]
    public function a_tag_page_refined_pins_its_sticky_discussion()
    {
        $this->assertEquals([1, 3, 2], $this->ids(['tag' => 'general', 'without' => '4']));
    }

    #[Test]
    public function a_tag_page_in_its_own_default_sort_refined_pins_its_sticky_discussion()
    {
        $this->assertEquals([11, 12], $this->ids(['tag' => 'newest-first', 'without' => '13'], '-createdAt'));
    }

    #[Test]
    public function a_window_of_all_discussions_does_not_pin_even_when_refined()
    {
        $this->setting('flarum-sticky.only_sticky_unread_discussions', false);

        $this->assertEquals([4, 3, 2, 1, 13, 12, 11], $this->ids(['window' => '1']));
        $this->assertEquals([3, 2, 1, 13, 12, 11], $this->ids(['window' => '1', 'without' => '4']));
    }

    #[Test]
    public function a_filter_that_is_both_a_window_and_a_refinement_does_not_pin()
    {
        $this->setting('flarum-sticky.only_sticky_unread_discussions', false);

        $this->assertEquals([4, 3, 2, 1, 13, 12, 11], $this->ids(['refiningWindow' => '1']));
    }
}
