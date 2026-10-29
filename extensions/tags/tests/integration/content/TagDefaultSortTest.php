<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration\content;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Tags\Tag;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * A tag's default sort decides the order its page opens with.
 *
 * Applied while the page is rendered rather than after it loads, so that the
 * discussions arrive in the right order to begin with instead of being
 * reordered in front of the reader.
 *
 * An explicit `?sort=` in the URL always wins: the default answers "what should
 * this tag look like when nobody has asked for anything", not "how must this
 * tag always look".
 */
class TagDefaultSortTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Tag::class => [
                ['id' => 1, 'name' => 'Plain', 'slug' => 'plain', 'position' => 0],
                ['id' => 2, 'name' => 'Oldest First', 'slug' => 'oldest-first', 'position' => 1, 'default_sort' => 'oldest'],
                ['id' => 3, 'name' => 'Gone', 'slug' => 'gone', 'position' => 2, 'default_sort' => 'sort_from_a_removed_extension'],
            ],
            Discussion::class => [
                $this->discussion(1, 'First', '2020-01-01'),
                $this->discussion(2, 'Second', '2021-01-01'),
                $this->discussion(3, 'Third', '2022-01-01'),
            ],
            'discussion_tag' => [
                ['discussion_id' => 1, 'tag_id' => 1],
                ['discussion_id' => 2, 'tag_id' => 1],
                ['discussion_id' => 3, 'tag_id' => 1],
                ['discussion_id' => 1, 'tag_id' => 2],
                ['discussion_id' => 2, 'tag_id' => 2],
                ['discussion_id' => 3, 'tag_id' => 2],
                ['discussion_id' => 1, 'tag_id' => 3],
                ['discussion_id' => 2, 'tag_id' => 3],
                ['discussion_id' => 3, 'tag_id' => 3],
            ],
        ]);
    }

    protected function discussion(int $id, string $title, string $date): array
    {
        return [
            'id' => $id,
            'title' => $title,
            'created_at' => Carbon::parse($date)->toDateTimeString(),
            'last_posted_at' => Carbon::parse($date)->toDateTimeString(),
            'user_id' => 1,
            'comment_count' => 1,
            'is_private' => 0,
        ];
    }

    /**
     * @return string[] Discussion titles in the order the page rendered them.
     */
    protected function titles(string $slug, array $query = []): array
    {
        $response = $this->send(
            $this->request('GET', "/t/$slug")->withQueryParams($query)
        );

        $this->assertEquals(200, $response->getStatusCode());

        preg_match('/<script id="flarum-json-payload" type="application\/json">(.+?)<\/script>/s', (string) $response->getBody(), $matches);

        $payload = json_decode(html_entity_decode($matches[1]), true);

        return array_map(
            fn (array $d) => $d['attributes']['title'],
            array_filter(
                $payload['apiDocument']['data'] ?? [],
                fn (array $d) => $d['type'] === 'discussions'
            )
        );
    }

    #[Test]
    public function a_tag_without_a_default_sort_uses_the_forum_order()
    {
        // Newest activity first, which is what the discussion list does when
        // nothing else is asked of it.
        $this->assertEquals(['Third', 'Second', 'First'], $this->titles('plain'));
    }

    #[Test]
    public function a_tag_with_a_default_sort_opens_in_that_order()
    {
        $this->assertEquals(['First', 'Second', 'Third'], $this->titles('oldest-first'));
    }

    #[Test]
    public function an_explicit_sort_in_the_url_beats_the_tags_default()
    {
        $this->assertEquals(['Third', 'Second', 'First'], $this->titles('oldest-first', ['sort' => 'newest']));
    }

    #[Test]
    public function a_default_sort_that_no_longer_exists_falls_back_quietly()
    {
        // The extension that registered this sort has gone. The page should
        // render in the forum's usual order rather than erroring, and the tag
        // keeps the setting in case the extension comes back.
        $this->assertEquals(['Third', 'Second', 'First'], $this->titles('gone'));

        $this->assertEquals('sort_from_a_removed_extension', Tag::find(3)->default_sort);
    }
}
