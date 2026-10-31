<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Approval\Tests\integration;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Tags\Tag;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TagMetadataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags', 'flarum-flags', 'flarum-approval');

        $date = Carbon::parse('2021-01-01T12:00:00+00:00');

        $this->prepareDatabase([
            Tag::class => [
                ['id' => 1, 'name' => 'Pending', 'slug' => 'pending', 'discussion_count' => 0, 'last_posted_discussion_id' => null],
            ],
            Discussion::class => [
                // A discussion that is still pending approval is private, so flarum/tags
                // deliberately leaves it out of the tag's counters.
                ['id' => 1, 'title' => 'A', 'created_at' => $date, 'last_posted_at' => $date, 'user_id' => 1, 'first_post_id' => 2, 'last_post_id' => 2, 'last_post_number' => 1, 'comment_count' => 1, 'is_approved' => false, 'is_private' => true],
            ],
            Post::class => [
                ['id' => 2, 'number' => 1, 'discussion_id' => 1, 'created_at' => $date, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>A</p></t>', 'is_approved' => false, 'is_private' => true],
            ],
            'discussion_tag' => [
                ['discussion_id' => 1, 'tag_id' => 1],
            ],
        ]);
    }

    #[Test]
    public function approving_a_pending_discussion_refreshes_tag_metadata()
    {
        // While pending, the tag has not counted the discussion.
        $tag = $this->database()->table('tags')->where('id', 1)->first();
        $this->assertEquals(0, $tag->discussion_count);
        $this->assertNull($tag->last_posted_discussion_id);

        // Approving the first post (as the admin) makes the discussion visible.
        $response = $this->send(
            $this->request('PATCH', '/api/posts/2', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'isApproved' => true,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        // The tag now counts the newly visible discussion and points at it as the last posted.
        $tag = $this->database()->table('tags')->where('id', 1)->first();
        $this->assertEquals(1, $tag->discussion_count);
        $this->assertEquals(1, $tag->last_posted_discussion_id);
    }
}
