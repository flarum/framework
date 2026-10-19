<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration\api\discussions;

use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Tags\Tag;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * A client that names its own includes (core's announcements fetch asks discuss
 * for `firstPost,user,tags`) replaces the endpoint's defaults, `tags.parent`
 * among them. Every serialized tag still asks TagPolicy about its parent, and
 * each discussion holds its own copies of its tags, so a parent was fetched
 * once per tag per discussion.
 */
class ListWithIncludedTagsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');

        $tags = [];
        $discussions = [];
        $posts = [];
        $discussionTags = [];

        // Each discussion is in a child tag of its own parent, as a page of
        // discussions across a forum's sub-tags is.
        foreach (range(1, 6) as $i) {
            $tags[] = ['id' => 100 + $i, 'name' => "Parent $i", 'slug' => "parent-$i", 'is_primary' => true, 'position' => $i, 'parent_id' => null];
            $tags[] = ['id' => 200 + $i, 'name' => "Child $i", 'slug' => "child-$i", 'is_primary' => true, 'position' => 0, 'parent_id' => 100 + $i];

            $discussions[] = ['id' => $i, 'title' => "Discussion $i", 'user_id' => 1, 'comment_count' => 1];
            $posts[] = ['id' => $i, 'discussion_id' => $i, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'];
            $discussionTags[] = ['discussion_id' => $i, 'tag_id' => 200 + $i];
        }

        $this->prepareDatabase([
            Tag::class => $tags,
            User::class => [$this->normalUser()],
            Discussion::class => $discussions,
            Post::class => $posts,
            'discussion_tag' => $discussionTags,
        ]);
    }

    #[Test]
    public function including_tags_without_their_parents_still_loads_the_parents_together()
    {
        $db = $this->database();
        $db->enableQueryLog();

        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 2])
                ->withQueryParams(['include' => 'tags'])
        );

        $log = $db->getQueryLog();
        $db->flushQueryLog();

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true);
        $children = array_filter($data['included'] ?? [], fn (array $record) => $record['type'] === 'tags' && $record['id'] > 200);

        // Answering this is what needs each child's parent.
        $this->assertCount(6, $children);
        foreach ($children as $child) {
            $this->assertTrue($child['attributes']['canStartDiscussion']);
        }

        // One parent at a time, as a lazy `$tag->parent` fetches it, whatever
        // the driver's quoting or the table prefix.
        $parentLookups = array_filter($log, fn (array $query) => preg_match(
            '/^select \* from \w*tags where \w*tags\.id = \? limit 1$/',
            str_replace(['`', '"'], '', $query['query'])
        ));

        $this->assertCount(0, $parentLookups);
    }
}
