<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Tests\integration\api;

use Flarum\Tags\Tag;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * The profile post list with tags enabled, every discussion tagged; it also
 * runs every test of the untagged profile.
 *
 * Serializing a mentioning reply checks whether the actor can edit it, and
 * tags' DiscussionPolicy answers that from the reply's discussion's tags. That
 * discussion is the instance `mentionedBy` loaded, which has no tags, so each
 * one used to load its tags on its own.
 */
class ProfilePostListWithTagsQueryCountTest extends ProfilePostListQueryCountTest
{
    protected function setUp(): void
    {
        // Before mentions, which integrates with tags when tags is enabled.
        $this->extension('flarum-tags');

        parent::setUp();

        $discussionTags = [];

        for ($k = 1; $k <= self::DISCUSSIONS; $k++) {
            $discussionTags[] = ['discussion_id' => $k, 'tag_id' => 1];
        }

        $this->prepareDatabase([
            Tag::class => [
                ['id' => 1, 'name' => 'General', 'slug' => 'general', 'position' => 0, 'parent_id' => null, 'discussion_count' => self::DISCUSSIONS],
            ],
            'discussion_tag' => $discussionTags,
        ]);
    }

    #[Test]
    #[DataProvider('viewers')]
    public function a_profile_does_not_look_up_tags_per_discussion(?int $viewer): void
    {
        [$document, $queries] = $this->listProfilePosts($viewer, self::DISCUSSIONS);

        $this->assertSame([], $this->discussionTagLookups($queries), 'A discussion\'s tags were looked up one at a time.');

        // The replies that mention each post are still there.
        foreach ($document['data'] as $post) {
            $this->assertCount(1, $post['relationships']['mentionedBy']['data'] ?? [], "Post {$post['id']} lost the reply that mentions it.");
        }
    }

    /**
     * One discussion's tags, as a lazily loaded `tags` relation does.
     *
     * @return string[]
     */
    protected function discussionTagLookups(array $queries): array
    {
        return array_values(preg_grep(
            '/["`]?(\w+_)?discussion_tag["`]?\.["`]?discussion_id["`]? = \?/i',
            array_column($queries, 'query')
        ));
    }
}
