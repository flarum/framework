<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Post\Event\Saving;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Regression tests for https://github.com/flarum/framework/issues/4416 (edit path).
 */
class EditPostWithEmptyContentTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-mentions');

        $this->extend(
            (new Extend\Event())
                ->listen(Saving::class, function ($event) {
                    $event->post->content;
                })
        );

        $this->prepareDatabase([
            \Flarum\User\User::class => [
                $this->normalUser(),
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Test discussion', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Original content</p></t>'],
            ],
        ]);
    }

    #[Test]
    public function editing_post_with_empty_content_returns_validation_error(): void
    {
        $response = $this->send(
            $this->request('PATCH', '/api/posts/1', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => '',
                        ],
                    ],
                ],
            ])
        );

        // Must not be a 500 — reading $post->content in the Saving listener must not
        // throw a TypeError when content is null (the empty string is stored as null).
        $this->assertNotEquals(500, $response->getStatusCode());
    }

    #[Test]
    public function editing_post_with_null_content_returns_validation_error(): void
    {
        $response = $this->send(
            $this->request('PATCH', '/api/posts/1', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => null,
                        ],
                    ],
                ],
            ])
        );

        $this->assertNotEquals(500, $response->getStatusCode());
    }
}
