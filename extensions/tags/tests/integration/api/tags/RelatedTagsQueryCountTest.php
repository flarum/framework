<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration\api\tags;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Tags\Tag;
use Flarum\Tags\Tests\integration\RetrievesRepresentativeTags;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Serializing a tag asks TagPolicy about its parent, and serializing its last
 * posted discussion asks about that discussion's tags and reads the actor's
 * read state. Those relations have to be loaded together, not looked up once
 * per row.
 */
class RelatedTagsQueryCountTest extends TestCase
{
    use RetrievesRepresentativeTags;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');

        // Tags 1, 3, 4 and 9 each have a different last posted discussion.
        $lastPosted = [1 => 1, 3 => 2, 4 => 3, 9 => 4];

        $this->prepareDatabase([
            Tag::class => array_map(
                fn (array $tag) => $tag + ['last_posted_discussion_id' => $lastPosted[$tag['id']] ?? null],
                $this->tags()
            ),
            Discussion::class => array_map(fn (int $id) => [
                'id' => $id, 'title' => "Discussion $id", 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(),
                'user_id' => 1, 'first_post_id' => $id, 'comment_count' => 1,
            ], [1, 2, 3, 4]),
            Post::class => array_map(fn (int $id) => [
                'id' => $id, 'number' => 1, 'discussion_id' => $id, 'created_at' => Carbon::now(),
                'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Text</p></t>',
            ], [1, 2, 3, 4]),
            'discussion_tag' => [
                ['discussion_id' => 1, 'tag_id' => 1],
                ['discussion_id' => 2, 'tag_id' => 2], ['discussion_id' => 2, 'tag_id' => 3],
                ['discussion_id' => 3, 'tag_id' => 2], ['discussion_id' => 3, 'tag_id' => 4],
                ['discussion_id' => 4, 'tag_id' => 9],
            ],
        ]);
    }

    #[Test]
    public function the_tags_page_loads_related_tags_and_discussion_state_together(): void
    {
        // The request the forum's /tags page makes.
        $queries = $this->queriesFor('/api/tags', ['include' => 'children,lastPostedDiscussion,parent']);

        $this->assertSame([], $this->tagLookups($queries), 'Tags looked up one at a time.');
        $this->assertSame([], $this->discussionTagLookups($queries), 'A discussion\'s tags looked up one at a time.');
        $this->assertSame([], $this->discussionStateLookups($queries), 'A discussion\'s read state looked up one at a time.');
    }

    #[Test]
    public function a_tag_page_loads_parents_together(): void
    {
        // The request the forum's tag page makes.
        $queries = $this->queriesFor('/api/tags/primary-2', ['include' => 'children,children.parent,parent,parent.children.parent']);

        $this->assertSame([], $this->tagLookups($queries), 'Tags looked up one at a time.');
    }

    /** @return string[] */
    protected function queriesFor(string $path, array $query): array
    {
        $db = $this->database();
        $db->enableQueryLog();

        $response = $this->send(
            $this->request('GET', $path, ['authenticatedAs' => 1])->withQueryParams($query)
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $queries = array_column($db->getQueryLog(), 'query');
        $db->flushQueryLog();

        return $queries;
    }

    /** Single tags fetched by id, as a lazily loaded `parent` does. */
    protected function tagLookups(array $queries): array
    {
        return array_values(preg_grep('/from ["`]?(\w+_)?tags["`]? where ["`]?(\w+_)?tags["`]?\.["`]?id["`]? = \?/i', $queries));
    }

    /** One discussion's read state, as a lazily loaded `state` relation does. */
    protected function discussionStateLookups(array $queries): array
    {
        return array_values(preg_grep('/["`]?(\w+_)?discussion_user["`]?\.["`]?discussion_id["`]? = \?/i', $queries));
    }

    /** One discussion's tags, as a lazily loaded `tags` relation does. */
    protected function discussionTagLookups(array $queries): array
    {
        return array_values(preg_grep('/["`]?(\w+_)?discussion_tag["`]?\.["`]?discussion_id["`]? = \?/i', $queries));
    }
}
