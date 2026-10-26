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
use Flarum\Post\Post;
use Flarum\Tags\Tag;
use Flarum\Testing\integration\TestCase;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tag pages pin sticky discussions to the top. A tag can also have a default
 * sort, which the page asks the API for explicitly: the client sends it as
 * `sort` (flarum/tags addTagFilter), and so does the server-rendered page
 * (Flarum\Tags\Content\Tag).
 *
 * Each tag holds a sticky discussion that is older, and was last posted in
 * earlier, than its two other discussions.
 */
class StickyOnTagWithDefaultSortTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags', 'flarum-sticky');

        $at = fn (int $minutes) => Carbon::parse('2026-01-01 00:00:00')->addMinutes($minutes);

        $discussions = [];
        $posts = [];
        $discussionTags = [];

        foreach ([1 => 'newest', 2 => 'latest', 3 => 'none'] as $tagId => $name) {
            foreach ([['Sticky', true, 0], ['Second', false, 1], ['Third', false, 2]] as $i => [$label, $sticky, $minutes]) {
                $id = $tagId * 10 + $i;
                $discussions[] = ['id' => $id, 'title' => "$label in $name", 'user_id' => 1, 'first_post_id' => $id, 'comment_count' => 1, 'last_post_number' => 1, 'is_sticky' => $sticky, 'created_at' => $at($minutes), 'last_posted_at' => $at($minutes)];
                $posts[] = ['id' => $id, 'discussion_id' => $id, 'number' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'created_at' => $at($minutes)];
                $discussionTags[] = ['discussion_id' => $id, 'tag_id' => $tagId];
            }
        }

        $this->prepareDatabase([
            Tag::class => [
                ['id' => 1, 'name' => 'Newest', 'slug' => 'newest-first', 'position' => 0, 'default_sort' => 'newest'],
                ['id' => 2, 'name' => 'Latest', 'slug' => 'latest-first', 'position' => 1, 'default_sort' => 'latest'],
                ['id' => 3, 'name' => 'Plain', 'slug' => 'plain', 'position' => 2],
            ],
            Discussion::class => $discussions,
            Post::class => $posts,
            'discussion_tag' => $discussionTags,
        ]);
    }

    /**
     * The discussion ids, in order, as the client asks for a tag's page.
     */
    private function listAsTheClientDoes(string $slug, ?string $sort): array
    {
        $params = ['filter' => ['tag' => $slug]];

        if ($sort) {
            $params['sort'] = $sort;
        }

        $response = $this->send($this->request('GET', '/api/discussions')->withQueryParams($params));

        $this->assertEquals(200, $response->getStatusCode());

        return Arr::pluck(json_decode($response->getBody()->getContents(), true)['data'], 'id');
    }

    /**
     * The titles, in order, on the server-rendered tag page.
     */
    private function titlesOnTheServerRenderedPage(string $slug, ?string $sort = null): array
    {
        $response = $this->send($this->request('GET', "/t/$slug")->withQueryParams($sort ? ['sort' => $sort] : []));

        $this->assertEquals(200, $response->getStatusCode());

        $html = $response->getBody()->getContents();

        preg_match_all('/(Sticky|Second|Third) in [a-z]+/', $html, $matches);

        return array_values(array_unique($matches[0]));
    }

    #[Test]
    public function control_a_tag_without_a_default_sort_pins_its_sticky_discussion()
    {
        // No default sort: the client sends no sort at all.
        $this->assertEquals(30, $this->listAsTheClientDoes('plain', null)[0]);
        $this->assertEquals('Sticky in none', $this->titlesOnTheServerRenderedPage('plain')[0]);
    }

    #[Test]
    public function a_tag_sorted_newest_first_pins_its_sticky_discussion_in_the_client_request()
    {
        // DiscussionListState maps `newest` to `-createdAt`.
        $this->assertEquals(10, $this->listAsTheClientDoes('newest-first', '-createdAt')[0]);
    }

    #[Test]
    public function a_tag_sorted_newest_first_pins_its_sticky_discussion_on_the_server_rendered_page()
    {
        $this->assertEquals('Sticky in newest', $this->titlesOnTheServerRenderedPage('newest-first')[0]);
    }

    #[Test]
    public function a_tag_whose_default_is_latest_pins_its_sticky_discussion_in_the_client_request()
    {
        // `latest` is the forum's own default order, sent explicitly.
        $this->assertEquals(20, $this->listAsTheClientDoes('latest-first', '-lastPostedAt')[0]);
    }

    #[Test]
    public function a_tag_whose_default_is_latest_pins_its_sticky_discussion_on_the_server_rendered_page()
    {
        $this->assertEquals('Sticky in latest', $this->titlesOnTheServerRenderedPage('latest-first')[0]);
    }

    #[Test]
    public function a_sort_other_than_the_tags_default_still_lists_sticky_discussions_in_place()
    {
        // The reader picked `latest` on a tag that opens newest first.
        $this->assertEquals([12, 11, 10], $this->listAsTheClientDoes('newest-first', '-lastPostedAt'));
        $this->assertEquals(['Third in newest', 'Second in newest', 'Sticky in newest'], $this->titlesOnTheServerRenderedPage('newest-first', 'latest'));
    }
}
