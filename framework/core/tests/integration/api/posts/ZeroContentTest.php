<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\posts;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\CommentPost;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class ZeroContentTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 1, 'title' => 'Discussion', 'created_at' => Carbon::now()->subDay()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1, 'last_post_number' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'created_at' => Carbon::now()->subDay()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Original</p></t>'],
            ],
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    #[Test]
    public function reply_with_content_zero_is_saved(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'attributes' => ['content' => '0'],
                        'relationships' => [
                            'discussion' => ['data' => ['type' => 'discussions', 'id' => '1']],
                        ],
                    ],
                ],
            ])
        );

        $body = (string) $response->getBody();
        $this->assertEquals(201, $response->getStatusCode(), $body);

        $attributes = json_decode($body, true)['data']['attributes'];
        $this->assertSame('0', $attributes['content']);
        $this->assertSame('0', trim(strip_tags($attributes['contentHtml'])));

        $post = CommentPost::query()->findOrFail(json_decode($body, true)['data']['id']);
        $this->assertNotNull($post->parsed_content);
        $this->assertSame('0', $post->content);
    }

    #[Test]
    public function discussion_with_content_zero_is_saved(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/discussions', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'attributes' => ['title' => 'Zero', 'content' => '0'],
                    ],
                ],
            ])
        );

        $body = (string) $response->getBody();
        $this->assertEquals(201, $response->getStatusCode(), $body);

        $discussion = Discussion::query()->findOrFail(json_decode($body, true)['data']['id']);
        $post = CommentPost::query()->findOrFail($discussion->first_post_id);
        $this->assertNotNull($post->parsed_content);
        $this->assertSame('0', $post->content);
        $this->assertSame('0', trim(strip_tags($post->formatContent())));
    }

    #[Test]
    public function editing_content_to_zero_is_saved(): void
    {
        $response = $this->send(
            $this->request('PATCH', '/api/posts/1', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => ['content' => '0'],
                    ],
                ],
            ])
        );

        $body = (string) $response->getBody();
        $this->assertEquals(200, $response->getStatusCode(), $body);

        $attributes = json_decode($body, true)['data']['attributes'];
        $this->assertSame('0', $attributes['content']);
        $this->assertSame('0', trim(strip_tags($attributes['contentHtml'])));

        $post = CommentPost::query()->findOrFail(1);
        $this->assertNotNull($post->parsed_content);
        $this->assertSame('0', $post->content);
    }
}
