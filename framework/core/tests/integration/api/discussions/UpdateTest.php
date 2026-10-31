<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\discussions;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class UpdateTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Original title', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1, 'last_post_number' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>first post</p></t>', 'number' => 1],
            ],
        ]);
    }

    #[Test]
    public function renaming_creates_discussion_renamed_event_post(): void
    {
        $response = $this->send(
            $this->request('PATCH', '/api/discussions/1', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'title' => 'Renamed title',
                        ],
                    ],
                ],
            ])
        );

        $body = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode(), $body);

        // Server-side: a `discussionRenamed` post row was inserted.
        $renamedPost = Post::query()
            ->where('discussion_id', 1)
            ->where('type', 'discussionRenamed')
            ->first();

        $this->assertNotNull($renamedPost, 'A discussionRenamed event post should be created when the title changes');

        // Client-visible: the PATCH response carries the refreshed `posts`
        // linkage with the new event post's id. Without this, frontend
        // `stream.update()` can't surface the renamed event post.
        $json = json_decode($body, true);

        $linkage = $json['data']['relationships']['posts']['data'] ?? null;
        $this->assertIsArray($linkage, 'PATCH response must include the discussion posts relationship linkage');

        $linkageIds = array_map(fn (array $entry) => $entry['id'], $linkage);

        $this->assertContains('1', $linkageIds, 'Linkage should still contain the original comment post id');
        $this->assertContains((string) $renamedPost->id, $linkageIds, 'Linkage should contain the new event post id');
    }

    #[Test]
    public function setting_title_to_same_value_creates_no_event_post(): void
    {
        $response = $this->send(
            $this->request('PATCH', '/api/discussions/1', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'title' => 'Original title',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertNull(
            Post::query()->where('discussion_id', 1)->where('type', 'discussionRenamed')->first(),
            'No discussionRenamed event post should be created when the title is unchanged'
        );
    }
}
